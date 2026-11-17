<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\GetAnalyticsSummaryTool;
use App\Mcp\Tools\GetTrendsTool;
use App\Mcp\Tools\GetVisitorTool;
use App\Mcp\Tools\ListTagsTool;
use App\Mcp\Tools\SearchEventsTool;
use App\Mcp\Tools\SearchVisitorsTool;
use Laravel\Mcp\Server;

class AnalyticsServer extends Server
{
    protected string $name = 'Osiris Analytics';

    protected string $version = '1.0.0';

    protected string $instructions = <<<'MARKDOWN'
        Read-only access to Osiris website visitor tracking data.

        Data model: a Visitor (identified by visitor_uuid, optionally with name/email/phone, tags, starred flag)
        has many TrackingSessions (device/browser/OS/IP), and each session has many Events (event_name such as
        page_view/click/form_submit, url, referrer, gclid, fbclid, utm_source/medium/campaign, query_strings, meta).

        - Start with `get-analytics-summary` for an overview and to discover event names and UTM values.
        - Use `get-trends` for time series (day/week/month).
        - Use `search-visitors` to find leads/visitors, then `get-visitor` for their full journey.
        - Use `search-events` for raw event-level questions.
        - `list-tags` shows available visitor tags.

        All dates are YYYY-MM-DD in the app timezone. Results are paginated with `limit`/`offset`; check `total`.
    MARKDOWN;

    protected array $tools = [
        GetAnalyticsSummaryTool::class,
        GetTrendsTool::class,
        SearchVisitorsTool::class,
        GetVisitorTool::class,
        SearchEventsTool::class,
        ListTagsTool::class,
    ];
}
