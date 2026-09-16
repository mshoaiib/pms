<?php

use App\Http\Middleware\AuthenticateMcpRequest;
use App\Mcp\Servers\PMSServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::local('pms', PMSServer::class);

/**
 * OAuth2 discovery and dynamic client registration.
 *
 * The hosted Claude surfaces (claude.ai, Desktop, mobile) cannot be handed a
 * fixed token, so they register a client here and complete an authorization
 * code flow against Passport. Claude Code keeps using MCP_TOKEN instead.
 */
Mcp::oauthRoutes();

/**
 * The same server over HTTP, for MCP clients that cannot start a local process
 * and for the Postman collection. Every tool writes to ClickUp, so the route is
 * guarded: a caller presents either the MCP_TOKEN bearer token or an OAuth
 * access token. With neither set or presented, the endpoint serves nothing.
 */
Mcp::web('/mcp/pms', PMSServer::class)
    ->middleware(AuthenticateMcpRequest::class);
