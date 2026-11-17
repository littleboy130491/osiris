<?php

use App\Mcp\Servers\AnalyticsServer;
use Laravel\Mcp\Facades\Mcp;

// OAuth discovery (.well-known) and dynamic client registration for MCP clients.
Mcp::oauthRoutes();

// Remote (HTTP) MCP server. Agents connect with just the URL and sign in via OAuth (Passport).
Mcp::web('/mcp', AnalyticsServer::class)
    ->middleware(['auth:api', 'throttle:60,1']);

// Local (stdio) MCP server for agents on the same machine: `php artisan mcp:start osiris`
Mcp::local('osiris', AnalyticsServer::class);
