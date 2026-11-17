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

uses(RefreshDatabase::class);

beforeEach(function () {
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

it('requires a token for the http endpoint', function () {
    $payload = ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'];

    $this->postJson('/mcp', $payload)->assertUnauthorized();

    $token = User::factory()->create()->createToken('mcp')->plainTextToken;

    $this->withToken($token)->postJson('/mcp', $payload)
        ->assertOk()
        ->assertSee('get-analytics-summary');
});
