<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\InteractsWithAnalytics;
use App\Models\Event;
use Carbon\CarbonPeriod;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class GetTrendsTool extends Tool
{
    use InteractsWithAnalytics;

    protected string $name = 'get-trends';

    protected string $description = <<<'MARKDOWN'
        Time series of events, distinct visitors and distinct sessions per day, week or month. Optionally restrict
        to one event name or UTM source/campaign, or require a GCLID/FBCLID. Use for "how is traffic trending",
        "conversions per week", "compare months" style questions. Defaults to the last 30 days by day.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $request->validate([
            'interval' => ['nullable', 'in:day,week,month'],
            'event_name' => ['nullable', 'string', 'max:255'],
            'utm_source' => ['nullable', 'string', 'max:255'],
            'utm_campaign' => ['nullable', 'string', 'max:255'],
            'has_gclid' => ['nullable', 'boolean'],
            'has_fbclid' => ['nullable', 'boolean'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'days' => ['nullable', 'integer', 'min:1', 'max:3650'],
        ]);

        $interval = $request->get('interval', 'day');
        [$from, $to] = $this->dateRange($request);

        $bucket = $this->bucketExpression($interval);

        $query = Event::query()->toBase()
            ->whereBetween('created_at', [$from, $to])
            ->when($request->get('event_name'), fn ($q, $v) => $q->where('event_name', $v))
            ->when($request->get('utm_source'), fn ($q, $v) => $q->where('utm_source', $v))
            ->when($request->get('utm_campaign'), fn ($q, $v) => $q->where('utm_campaign', $v))
            ->when($request->get('has_gclid'), fn ($q) => $q->whereNotNull('gclid')->where('gclid', '!=', ''))
            ->when($request->get('has_fbclid'), fn ($q) => $q->whereNotNull('fbclid')->where('fbclid', '!=', ''));

        $rows = $query
            ->select(
                DB::raw("{$bucket} as period"),
                DB::raw('COUNT(*) as events'),
                DB::raw('COUNT(DISTINCT visitor_id) as visitors'),
                DB::raw('COUNT(DISTINCT tracking_session_id) as sessions'),
            )
            ->groupBy(DB::raw($bucket))
            ->orderBy('period')
            ->get()
            ->keyBy('period');

        // Fill empty periods with zeros so gaps are visible.
        $format = ['day' => 'Y-m-d', 'week' => 'o-\WW', 'month' => 'Y-m'][$interval];
        $start = match ($interval) {
            'week' => $from->startOfWeek(),
            'month' => $from->startOfMonth(),
            default => $from,
        };
        $series = collect(CarbonPeriod::create($start, '1 '.$interval, $to))
            ->map(fn ($date) => $date->format($format))
            ->merge($rows->keys())
            ->unique()
            ->sort()
            ->values()
            ->map(fn ($period) => [
                'period' => $period,
                'events' => (int) ($rows[$period]->events ?? 0),
                'visitors' => (int) ($rows[$period]->visitors ?? 0),
                'sessions' => (int) ($rows[$period]->sessions ?? 0),
            ]);

        return $this->json([
            'range' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'interval' => $interval,
                'timezone' => config('app.timezone'),
            ],
            'filters' => array_filter($request->only(['event_name', 'utm_source', 'utm_campaign', 'has_gclid', 'has_fbclid'])),
            'totals' => [
                'events' => $series->sum('events'),
            ],
            'series' => $series,
        ]);
    }

    protected function bucketExpression(string $interval): string
    {
        return match (DB::connection()->getDriverName()) {
            'mysql', 'mariadb' => match ($interval) {
                'day' => "DATE_FORMAT(created_at, '%Y-%m-%d')",
                'week' => "DATE_FORMAT(created_at, '%x-W%v')",
                'month' => "DATE_FORMAT(created_at, '%Y-%m')",
            },
            'pgsql' => match ($interval) {
                'day' => "to_char(created_at, 'YYYY-MM-DD')",
                'week' => "to_char(created_at, 'IYYY-\"W\"IW')",
                'month' => "to_char(created_at, 'YYYY-MM')",
            },
            // sqlite: %W is not ISO week, so derive ISO year/week from the Thursday of the week.
            default => match ($interval) {
                'day' => "strftime('%Y-%m-%d', created_at)",
                'week' => "strftime('%Y', created_at, 'weekday 0', '-3 days') || '-W' || printf('%02d', (strftime('%j', created_at, 'weekday 0', '-3 days') - 1) / 7 + 1)",
                'month' => "strftime('%Y-%m', created_at)",
            },
        };
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'interval' => $schema->string()->enum(['day', 'week', 'month'])->description('Bucket size. Default day. Weeks are ISO weeks (YYYY-Www).'),
            'event_name' => $schema->string()->description('Only count this event name.'),
            'utm_source' => $schema->string(),
            'utm_campaign' => $schema->string(),
            'has_gclid' => $schema->boolean()->description('Only events carrying a GCLID.'),
            'has_fbclid' => $schema->boolean()->description('Only events carrying an FBCLID.'),
            'from' => $schema->string()->description('Start date. '.$this->dateSchemaDescription()),
            'to' => $schema->string()->description('End date (inclusive). Defaults to today.'),
            'days' => $schema->integer()->min(1)->description('Last N days when `from` omitted. Default 30.'),
        ];
    }
}
