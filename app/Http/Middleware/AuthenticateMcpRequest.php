<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the HTTP MCP transport, accepting either credential the clients use.
 *
 * Claude Code passes a fixed bearer token set in MCP_TOKEN. The hosted Claude
 * surfaces cannot be given a fixed token, so they complete the OAuth flow and
 * present a Passport access token instead; those fall through to the api guard.
 *
 * Every PMS tool writes to ClickUp and the delete tools cannot be undone, so
 * this fails closed: a request carrying neither credential is rejected. The
 * stdio transport registered by `Mcp::local()` does not pass through HTTP
 * middleware and so is unaffected.
 *
 * The 401 is returned bare. The package's own AddWwwAuthenticateHeader
 * middleware wraps this one and attaches the WWW-Authenticate challenge, which
 * points OAuth clients at the discovery document registered by Mcp::oauthRoutes.
 */
class AuthenticateMcpRequest
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->hasValidStaticToken($request)) {
            return $next($request);
        }

        if (Auth::guard('api')->check()) {
            Auth::shouldUse('api');

            return $next($request);
        }

        return $this->unauthorized();
    }

    protected function hasValidStaticToken(Request $request): bool
    {
        $expected = config('mcp.token');
        $provided = $request->bearerToken();

        if (! is_string($expected) || $expected === '') {
            return false;
        }

        return is_string($provided) && hash_equals($expected, $provided);
    }

    protected function unauthorized(): Response
    {
        return response()->json(['error' => 'Unauthorized'], 401);
    }
}
