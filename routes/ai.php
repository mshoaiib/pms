<?php

use App\Mcp\Servers\PMSServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::local('pms', PMSServer::class);

/**
 * The same server over HTTP, for MCP clients that cannot start a local process
 * and for the Postman collection. Every tool writes to ClickUp, so put an
 * authentication middleware on this route before it leaves your machine.
 */
Mcp::web('/mcp/pms', PMSServer::class);
