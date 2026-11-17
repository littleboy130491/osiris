<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\InteractsWithAnalytics;
use App\Models\Event;
use App\Models\TrackingSession;
use App\Models\Visitor;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class GetAnalyticsSummaryTool extends Tool
{
    use InteractsWithAnalytics;

    protected string $name = 'get-analytics-summary';

    protected string $description = <<<'MARKDOWN'
        Overview of tracking data for a date range: totals (new visitors, active visitors, sessions, events),
        events by name, top pages, top referrers, UTM sources/mediums/campaigns, GCLID/FBCLID attribution,
        and device/browser/OS breakdowns. Defaults to the last 30 days. Start here for most questions.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'top' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        [$from, $to] = $this->dateRange($request);
        $top = (int) $request->get('top', 10);

        $events = Event::query()->toBase()->whereBetween('created_at', [$from, $to]);
        $sessions = TrackingSession::query()->toBase()->whereBetween('created_at', [$from, $to]);

        return $this->json([
            'range' => [
                'from' => $from->toDateTimeString(),
                'to' => $to->toDateTimeString(),
                'timezone' => config('app.timezone'),
            ],
            'totals' => [
                'new_visitors' => Visitor::whereBetween('created_at', [$from, $to])->count(),
                'active_visitors' => (clone $events)->distinct()->count('visitor_id'),
                'sessions' => (clone $sessions)->count(),
                'events' => (clone $events)->count(),
                'visitors_with_contact' => Visitor::whereBetween('created_at', [$from, $to])
                    ->where(fn ($q) => $q->whereNotNull('email')->orWhereNotNull('phone'))
                    ->count(),
            ],
            'attribution' => [
                'visitors_with_gclid' => (clone $events)->whereNotNull('gclid')->where('gclid', '!=', '')->distinct()->count('visitor_id'),
                'visitors_with_fbclid' => (clone $events)->whereNotNull('fbclid')->where('fbclid', '!=', '')->distinct()->count('visitor_id'),
                'utm_sources' => $this->topCounts($events, 'utm_source', $top, 'visitor_id'),
                'utm_mediums' => $this->topCounts($events, 'utm_medium', $top, 'visitor_id'),
                'utm_campaigns' => $this->topCounts($events, 'utm_campaign', $top, 'visitor_id'),
                'note' => 'UTM counts are distinct visitors.',
            ],
            'events_by_name' => $this->topCounts($events, 'event_name', 50),
            'top_urls' => $this->topCounts($events, 'url', $top),
            'top_referrers' => $this->topCounts($events, 'referrer', $top),
            'sessions_by_device' => $this->topCounts($sessions, 'device', $top),
            'sessions_by_browser' => $this->topCounts($sessions, 'browser', $top),
            'sessions_by_os' => $this->topCounts($sessions, 'os', $top),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'from' => $schema->string()->description('Start date. '.$this->dateSchemaDescription()),
            'to' => $schema->string()->description('End date (inclusive). Defaults to today.'),
            'days' => $schema->integer()->min(1)->description('Number of days back from `to` when `from` is omitted. Default 30.'),
            'top' => $schema->integer()->min(1)->max(50)->description('How many rows per top-N list. Default 10.'),
        ];
    }
}
