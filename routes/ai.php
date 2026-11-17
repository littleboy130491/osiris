<?php

use App\Mcp\Servers\AnalyticsServer;
use Laravel\Mcp\Facades\Mcp;

// Remote (HTTP) MCP server. Authenticate with a Sanctum token: `php artisan mcp:token you@example.com`
Mcp::web('/mcp', AnalyticsServer::class)
    ->middleware(['auth:sanctum', 'throttle:60,1']);

// Local (stdio) MCP server for agents on the same machine: `php artisan mcp:start osiris`
Mcp::local('osiris', AnalyticsServer::class);
