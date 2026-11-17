<?php

namespace App\Mcp\Concerns;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

trait InteractsWithAnalytics
{
    /**
     * Resolve the [from, to] range from `from`, `to` and `days` arguments.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    protected function dateRange(Request $request, int $defaultDays = 30): array
    {
        $to = $request->get('to')
            ? CarbonImmutable::parse($request->get('to'))->endOfDay()
            : CarbonImmutable::now()->endOfDay();

        $from = $request->get('from')
            ? CarbonImmutable::parse($request->get('from'))->startOfDay()
            : $to->subDays(max(1, (int) $request->get('days', $defaultDays)) - 1)->startOfDay();

        return [$from, $to];
    }

    /**
     * Apply an optional date range (only when `from`, `to` or `days` was given).
     */
    protected function applyOptionalDateRange($query, Request $request, string $column = 'created_at')
    {
        if ($request->get('from') || $request->get('to') || $request->get('days')) {
            [$from, $to] = $this->dateRange($request);
            $query->whereBetween($column, [$from, $to]);
        }

        return $query;
    }

    protected function limit(Request $request, int $default = 25, int $max = 200): int
    {
        return min($max, max(1, (int) $request->get('limit', $default)));
    }

    protected function offset(Request $request): int
    {
        return max(0, (int) $request->get('offset', 0));
    }

    /**
     * Top values of a column with counts.
     *
     * @return array<int, array{value: mixed, count: int}>
     */
    protected function topCounts(Builder $query, string $column, int $limit = 10, ?string $distinct = null): array
    {
        $aggregate = $distinct ? "COUNT(DISTINCT {$distinct})" : 'COUNT(*)';

        return (clone $query)
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->select($column.' as value', DB::raw("{$aggregate} as count"))
            ->groupBy($column)
            ->orderByDesc('count')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => ['value' => $row->value, 'count' => (int) $row->count])
            ->all();
    }

    /**
     * JSON response without escaped slashes/unicode so URLs and names stay readable for the agent.
     */
    protected function json(mixed $data): Response
    {
        return Response::text(json_encode(
            $data,
            JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ));
    }

    protected function dateSchemaDescription(): string
    {
        return 'Date (YYYY-MM-DD), interpreted in the app timezone ('.config('app.timezone').').';
    }
}
