<?php

use App\Http\Middleware\AuthenticateMcpRequest;
use App\Mcp\Servers\PMSServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::local('pms', PMSServer::class);

/**
 * The same server over HTTP, for MCP clients that cannot start a local process
 * and for the Postman collection. Every tool writes to ClickUp, so the route is
 * guarded by a shared bearer token set in MCP_TOKEN. Without that token set the
 * endpoint serves nothing.
 */
Mcp::web('/mcp/pms', PMSServer::class)
    ->middleware(AuthenticateMcpRequest::class);
