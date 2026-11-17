<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\InteractsWithAnalytics;
use App\Models\Event;
use App\Models\Visitor;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class SearchVisitorsTool extends Tool
{
    use InteractsWithAnalytics;

    protected string $name = 'search-visitors';

    protected string $description = <<<'MARKDOWN'
        Search and list visitors. Filter by text (name, email, phone or visitor UUID), tag, starred, contact info,
        GCLID/FBCLID presence, UTM source/campaign, and first-seen date range. Each visitor includes event/session
        counts, last seen time, landing page (first event) and earliest GCLID/FBCLID. Use `get-visitor` for full detail.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'tag' => ['nullable', 'string', 'max:255'],
            'starred' => ['nullable', 'boolean'],
            'has_contact' => ['nullable', 'boolean'],
            'has_gclid' => ['nullable', 'boolean'],
            'has_fbclid' => ['nullable', 'boolean'],
            'utm_source' => ['nullable', 'string', 'max:255'],
            'utm_campaign' => ['nullable', 'string', 'max:255'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'days' => ['nullable', 'integer', 'min:1'],
            'sort' => ['nullable', 'in:newest,oldest,most_events,last_seen'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'offset' => ['nullable', 'integer', 'min:0'],
        ]);

        $query = Visitor::query();

        if ($search = $request->get('search')) {
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('visitor_uuid', $search));
        }

        if ($tag = $request->get('tag')) {
            $query->whereHas('tags', fn ($q) => $q->where('name', $tag));
        }

        if ($request->get('starred') !== null) {
            $query->where('starred', (bool) $request->get('starred'));
        }

        if ($request->get('has_contact') !== null) {
            $request->get('has_contact')
                ? $query->where(fn ($q) => $q->whereNotNull('email')->orWhereNotNull('phone'))
                : $query->whereNull('email')->whereNull('phone');
        }

        foreach (['gclid', 'fbclid'] as $param) {
            if ($request->get("has_{$param}") !== null) {
                $method = $request->get("has_{$param}") ? 'whereHas' : 'whereDoesntHave';
                $query->{$method}('events', fn ($q) => $q->whereNotNull($param)->where($param, '!=', ''));
            }
        }

        foreach (['utm_source', 'utm_campaign'] as $param) {
            if ($value = $request->get($param)) {
                $query->whereHas('events', fn ($q) => $q->where($param, $value));
            }
        }

        $this->applyOptionalDateRange($query, $request, 'visitors.created_at');

        $total = (clone $query)->count();

        $firstValue = fn (string $column) => Event::select($column)
            ->whereColumn('visitor_id', 'visitors.id')
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->orderBy('created_at')
            ->limit(1);

        $query
            ->withCount(['events', 'trackingSessions'])
            ->withMax('events as last_seen_at', 'created_at')
            ->addSelect([
                'first_gclid' => $firstValue('gclid'),
                'first_fbclid' => $firstValue('fbclid'),
            ])
            ->with(['tags:id,name', 'firstEvent']);

        match ($request->get('sort', 'newest')) {
            'oldest' => $query->orderBy('created_at'),
            'most_events' => $query->orderByDesc('events_count'),
            'last_seen' => $query->orderByDesc('last_seen_at'),
            default => $query->orderByDesc('created_at'),
        };

        $limit = $this->limit($request, 25, 100);
        $offset = $this->offset($request);

        $visitors = $query->offset($offset)->limit($limit)->get()->map(fn (Visitor $v) => [
            'id' => $v->id,
            'visitor_uuid' => $v->visitor_uuid,
            'name' => $v->name,
            'email' => $v->email,
            'phone' => $v->phone,
            'starred' => (bool) $v->starred,
            'tags' => $v->tags->pluck('name')->all(),
            'first_seen_at' => $v->created_at?->toDateTimeString(),
            'last_seen_at' => $v->last_seen_at,
            'events_count' => $v->events_count,
            'sessions_count' => $v->tracking_sessions_count,
            'first_gclid' => $v->first_gclid,
            'first_fbclid' => $v->first_fbclid,
            'landing' => $v->firstEvent ? [
                'url' => $v->firstEvent->url,
                'referrer' => $v->firstEvent->referrer,
                'utm_source' => $v->firstEvent->utm_source,
                'utm_medium' => $v->firstEvent->utm_medium,
                'utm_campaign' => $v->firstEvent->utm_campaign,
            ] : null,
        ]);

        return $this->json([
            'total' => $total,
            'offset' => $offset,
            'limit' => $limit,
            'visitors' => $visitors,
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->description('Partial match on name, email or phone; exact match on visitor UUID.'),
            'tag' => $schema->string()->description('Only visitors with this tag name (see `list-tags`).'),
            'starred' => $schema->boolean()->description('Filter by starred flag.'),
            'has_contact' => $schema->boolean()->description('true = has email or phone (a lead); false = anonymous.'),
            'has_gclid' => $schema->boolean()->description('Visitor has (or has not) any event with a Google Ads click ID.'),
            'has_fbclid' => $schema->boolean()->description('Visitor has (or has not) any event with a Facebook click ID.'),
            'utm_source' => $schema->string()->description('Visitor has an event with this exact utm_source.'),
            'utm_campaign' => $schema->string()->description('Visitor has an event with this exact utm_campaign.'),
            'from' => $schema->string()->description('First-seen on/after this date. '.$this->dateSchemaDescription()),
            'to' => $schema->string()->description('First-seen on/before this date.'),
            'days' => $schema->integer()->min(1)->description('First seen within the last N days (when `from` omitted).'),
            'sort' => $schema->string()->enum(['newest', 'oldest', 'most_events', 'last_seen'])->description('Default newest.'),
            'limit' => $schema->integer()->min(1)->max(100)->description('Default 25.'),
            'offset' => $schema->integer()->min(0)->description('For pagination.'),
        ];
    }
}
