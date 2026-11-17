<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\InteractsWithAnalytics;
use App\Mcp\Support\EventPresenter;
use App\Models\Event;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class SearchEventsTool extends Tool
{
    use InteractsWithAnalytics;

    protected string $name = 'search-events';

    protected string $description = <<<'MARKDOWN'
        Search raw tracking events (page views, clicks, form submits, etc.). Filter by event name, URL/referrer
        substring, UTM params, GCLID/FBCLID, visitor, session, device/browser and date range. Returns the total
        match count plus a page of events (newest first by default).
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $request->validate([
            'event_name' => ['nullable', 'string', 'max:255'],
            'url_contains' => ['nullable', 'string', 'max:255'],
            'referrer_contains' => ['nullable', 'string', 'max:255'],
            'utm_source' => ['nullable', 'string', 'max:255'],
            'utm_medium' => ['nullable', 'string', 'max:255'],
            'utm_campaign' => ['nullable', 'string', 'max:255'],
            'gclid' => ['nullable', 'string', 'max:255'],
            'fbclid' => ['nullable', 'string', 'max:255'],
            'has_gclid' => ['nullable', 'boolean'],
            'has_fbclid' => ['nullable', 'boolean'],
            'visitor_id' => ['nullable', 'integer'],
            'visitor_uuid' => ['nullable', 'string'],
            'session_uuid' => ['nullable', 'string'],
            'device' => ['nullable', 'string', 'max:255'],
            'browser' => ['nullable', 'string', 'max:255'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'days' => ['nullable', 'integer', 'min:1'],
            'order' => ['nullable', 'in:newest,oldest'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:200'],
            'offset' => ['nullable', 'integer', 'min:0'],
        ]);

        $query = Event::query();

        foreach (['event_name', 'utm_source', 'utm_medium', 'utm_campaign', 'gclid', 'fbclid', 'visitor_id', 'device', 'browser'] as $column) {
            if (($value = $request->get($column)) !== null && $value !== '') {
                $query->where($column, $value);
            }
        }

        if ($value = $request->get('url_contains')) {
            $query->where('url', 'like', "%{$value}%");
        }

        if ($value = $request->get('referrer_contains')) {
            $query->where('referrer', 'like', "%{$value}%");
        }

        foreach (['gclid', 'fbclid'] as $param) {
            if ($request->get("has_{$param}") !== null) {
                $request->get("has_{$param}")
                    ? $query->whereNotNull($param)->where($param, '!=', '')
                    : $query->where(fn ($q) => $q->whereNull($param)->orWhere($param, ''));
            }
        }

        if ($uuid = $request->get('visitor_uuid')) {
            $query->whereHas('visitor', fn ($q) => $q->where('visitor_uuid', $uuid));
        }

        if ($uuid = $request->get('session_uuid')) {
            $query->whereHas('trackingSession', fn ($q) => $q->where('session_uuid', $uuid));
        }

        $this->applyOptionalDateRange($query, $request);

        $total = (clone $query)->count();
        $limit = $this->limit($request, 50, 200);
        $offset = $this->offset($request);

        $events = $query
            ->with('visitor:id,visitor_uuid')
            ->orderBy('created_at', $request->get('order') === 'oldest' ? 'asc' : 'desc')
            ->orderBy('id', $request->get('order') === 'oldest' ? 'asc' : 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(fn (Event $e) => EventPresenter::present($e));

        return $this->json([
            'total' => $total,
            'offset' => $offset,
            'limit' => $limit,
            'events' => $events,
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'event_name' => $schema->string()->description('Exact event name, e.g. page_view, click, form_submit. See `get-analytics-summary` for names in use.'),
            'url_contains' => $schema->string()->description('Substring match on page URL.'),
            'referrer_contains' => $schema->string()->description('Substring match on referrer.'),
            'utm_source' => $schema->string(),
            'utm_medium' => $schema->string(),
            'utm_campaign' => $schema->string(),
            'gclid' => $schema->string()->description('Exact Google Ads click ID.'),
            'fbclid' => $schema->string()->description('Exact Facebook click ID.'),
            'has_gclid' => $schema->boolean(),
            'has_fbclid' => $schema->boolean(),
            'visitor_id' => $schema->integer(),
            'visitor_uuid' => $schema->string(),
            'session_uuid' => $schema->string(),
            'device' => $schema->string()->description('e.g. desktop, mobile, tablet.'),
            'browser' => $schema->string(),
            'from' => $schema->string()->description('Start date. '.$this->dateSchemaDescription()),
            'to' => $schema->string()->description('End date (inclusive).'),
            'days' => $schema->integer()->min(1)->description('Last N days (when `from` omitted).'),
            'order' => $schema->string()->enum(['newest', 'oldest'])->description('Default newest.'),
            'limit' => $schema->integer()->min(1)->max(200)->description('Default 50.'),
            'offset' => $schema->integer()->min(0),
        ];
    }
}
