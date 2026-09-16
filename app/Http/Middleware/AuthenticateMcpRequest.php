<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the HTTP MCP transport with a shared bearer token.
 *
 * Every PMS tool writes to ClickUp and the delete tools cannot be undone, so this
 * fails closed: with no token configured, nothing is served. The stdio transport
 * registered by `Mcp::local()` does not pass through HTTP middleware and so is
 * unaffected.
 *
 * The 401 is returned bare. The package's own AddWwwAuthenticateHeader middleware
 * wraps this one and attaches the WWW-Authenticate challenge.
 */
class AuthenticateMcpRequest
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('mcp.token');

        if (! is_string($expected) || $expected === '') {
            return $this->unauthorized();
        }

        $provided = $request->bearerToken();

        if (! is_string($provided) || ! hash_equals($expected, $provided)) {
            return $this->unauthorized();
        }

        return $next($request);
    }

    protected function unauthorized(): Response
    {
        return response()->json(['error' => 'Unauthorized'], 401);
    }
}
