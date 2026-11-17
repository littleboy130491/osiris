<?php

use App\Mcp\Servers\AnalyticsServer;
use App\Mcp\Tools\GetAnalyticsSummaryTool;
use App\Mcp\Tools\GetTrendsTool;
use App\Mcp\Tools\GetVisitorTool;
use App\Mcp\Tools\ListTagsTool;
use App\Mcp\Tools\SearchEventsTool;
use App\Mcp\Tools\SearchVisitorsTool;
use App\Models\Event;
use App\Models\Tag;
use App\Models\TrackingSession;
use App\Models\User;
use App\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Throwaway Passport signing keys for the OAuth tests.
    static $keys;
    $keys ??= (function () {
        // Pass an explicit openssl.cnf when PHP's bundled one is missing (e.g. Herd Lite).
        $options = ['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA];
        foreach ([getenv('OPENSSL_CONF'), '/usr/lib/ssl/openssl.cnf', '/etc/ssl/openssl.cnf'] as $conf) {
            if ($conf && is_file($conf)) {
                $options['config'] = $conf;
                break;
            }
        }
        $key = openssl_pkey_new($options);
        openssl_pkey_export($key, $private, null, $options);

        return [$private, openssl_pkey_get_details($key)['key']];
    })();
    config(['passport.private_key' => $keys[0], 'passport.public_key' => $keys[1]]);

    $this->lead = Visitor::factory()->create(['name' => 'Budi Lead', 'email' => 'budi@example.com', 'created_at' => now()->subDays(2)]);
    $this->anon = Visitor::factory()->create(['name' => null, 'email' => null, 'phone' => null, 'created_at' => now()->subDays(1)]);
    $this->lead->tags()->attach(Tag::create(['name' => 'hot']));

    $session = TrackingSession::factory()->for($this->lead)->create(['device' => 'Mobile']);
    Event::factory()->for($this->lead)->for($session, 'trackingSession')->create([
        'event_name' => 'page_view', 'url' => 'https://site.test/landing', 'gclid' => 'GCLID123', 'fbclid' => null,
        'utm_source' => 'google', 'utm_campaign' => 'promo', 'created_at' => now()->subDays(2),
    ]);
    Event::factory()->for($this->lead)->for($session, 'trackingSession')->create([
        'event_name' => 'form_submit', 'url' => 'https://site.test/contact', 'gclid' => null, 'fbclid' => null,
        'utm_source' => null, 'created_at' => now()->subDay(),
    ]);

    $anonSession = TrackingSession::factory()->for($this->anon)->create(['device' => 'Desktop']);
    Event::factory()->for($this->anon)->for($anonSession, 'trackingSession')->create([
        'event_name' => 'page_view', 'url' => 'https://site.test/', 'gclid' => null, 'fbclid' => 'FB1',
        'utm_source' => 'facebook', 'created_at' => now(),
    ]);
});

it('summarises analytics', function () {
    AnalyticsServer::tool(GetAnalyticsSummaryTool::class, ['days' => 7])
        ->assertOk()
        ->assertSee(['"events": 3', '"active_visitors": 2', '"visitors_with_gclid": 1', '"form_submit"', '"google"']);
});

it('returns trends by day and month', function () {
    AnalyticsServer::tool(GetTrendsTool::class, ['days' => 7, 'event_name' => 'page_view'])
        ->assertOk()
        ->assertSee(['"period": "'.now()->toDateString().'"', '"events": 2']);

    AnalyticsServer::tool(GetTrendsTool::class, ['interval' => 'week', 'days' => 14])->assertOk()->assertSee('-W');
    AnalyticsServer::tool(GetTrendsTool::class, ['interval' => 'month', 'days' => 90])->assertOk()->assertSee(now()->format('Y-m'));
});

it('searches visitors with filters', function () {
    AnalyticsServer::tool(SearchVisitorsTool::class, ['has_gclid' => true])
        ->assertOk()
        ->assertSee(['"total": 1', 'Budi Lead', 'GCLID123', 'https://site.test/landing']);

    AnalyticsServer::tool(SearchVisitorsTool::class, ['has_contact' => false])
        ->assertOk()
        ->assertSee(['"total": 1', 'FB1'])
        ->assertDontSee('Budi Lead');

    AnalyticsServer::tool(SearchVisitorsTool::class, ['tag' => 'hot'])->assertSee('Budi Lead');
});

it('shows a visitor journey', function () {
    AnalyticsServer::tool(GetVisitorTool::class, ['visitor_uuid' => $this->lead->visitor_uuid])
        ->assertOk()
        ->assertSee(['Budi Lead', '"hot"', 'form_submit', 'Mobile']);

    AnalyticsServer::tool(GetVisitorTool::class, ['id' => 999999])->assertHasErrors(['Visitor not found.']);
});

it('searches events', function () {
    AnalyticsServer::tool(SearchEventsTool::class, ['event_name' => 'page_view', 'utm_source' => 'facebook'])
        ->assertOk()
        ->assertSee(['"total": 1', $this->anon->visitor_uuid]);

    AnalyticsServer::tool(SearchEventsTool::class, ['url_contains' => 'contact'])->assertSee('"total": 1');
});

it('lists tags', function () {
    AnalyticsServer::tool(ListTagsTool::class)->assertOk()->assertSee(['hot', '"visitors_count": 1']);
});

it('rejects unauthenticated http requests and advertises oauth metadata', function () {
    $this->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])
        ->assertUnauthorized()
        ->assertHeader('WWW-Authenticate');

    $this->getJson('/.well-known/oauth-protected-resource/mcp')->assertOk()->assertJsonPath('resource', url('/mcp'));
    $this->getJson('/.well-known/oauth-authorization-server')->assertOk()
        ->assertJsonPath('registration_endpoint', url('/oauth/register'))
        ->assertJsonPath('token_endpoint', url('/oauth/token'));
});

it('lets an agent connect through the full oauth flow', function () {
    // 1. Agent registers itself dynamically.
    $redirect = 'http://localhost:33418/callback';
    $clientId = $this->postJson('/oauth/register', ['client_name' => 'Test Agent', 'redirect_uris' => [$redirect]])
        ->assertCreated()
        ->json('client_id');

    // 2. User is sent to authorize; guests are redirected to the Filament login.
    $verifier = Str::random(64);
    $query = http_build_query([
        'client_id' => $clientId,
        'redirect_uri' => $redirect,
        'response_type' => 'code',
        'scope' => 'mcp:use',
        'state' => 'xyz',
        'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '='),
        'code_challenge_method' => 'S256',
    ]);

    $this->get('/oauth/authorize?'.$query)->assertRedirect(route('filament.admin.auth.login'));

    // 3. Logged-in user sees the consent screen and approves.
    $user = User::factory()->create();
    $this->actingAs($user)->get('/oauth/authorize?'.$query)->assertOk()->assertSee('Test Agent');

    $approve = $this->actingAs($user)->post('/oauth/authorize', [
        'state' => 'xyz',
        'client_id' => $clientId,
        'auth_token' => session('authToken'),
    ]);
    $approve->assertRedirect();
    parse_str(parse_url($approve->headers->get('Location'), PHP_URL_QUERY), $params);
    expect($params)->toHaveKey('code');

    // 4. Agent exchanges the code (PKCE) for an access token.
    $accessToken = $this->postJson('/oauth/token', [
        'grant_type' => 'authorization_code',
        'client_id' => $clientId,
        'redirect_uri' => $redirect,
        'code_verifier' => $verifier,
        'code' => $params['code'],
    ])->assertOk()->json('access_token');

    // 5. Agent calls the MCP server.
    app('auth')->forgetGuards();
    $this->flushSession();

    $this->withToken($accessToken)
        ->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])
        ->assertOk()
        ->assertSee('get-analytics-summary');
});
