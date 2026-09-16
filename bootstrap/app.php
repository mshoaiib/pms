<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        /**
         * The deployment sits behind a platform proxy that terminates TLS and
         * forwards the request over HTTP. Without trusting it, Laravel builds
         * asset and redirect URLs with an http:// scheme, which browsers then
         * block as mixed content on an https:// page. The proxy has no fixed
         * address, so every forwarding hop is trusted.
         */
        $middleware->trustProxies(at: '*');

        /**
         * There is no route named "login" in this application; the only way in
         * is the Filament panel. Without this, an unauthenticated visit to the
         * OAuth consent screen has nowhere to go, which is what an MCP client
         * hits on the first step of the authorization flow.
         */
        $middleware->redirectGuestsTo(fn () => route('filament.admin.auth.login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

/**
 * Relocate the bootstrap directory when LARAVEL_BOOTSTRAP_PATH is set.
 *
 * Laravel writes its package and service manifests into bootstrap/cache on the
 * first request. On a read-only deployment that write fails, so the serverless
 * entry point points this at a writable directory and the manifests are rebuilt
 * from the packages actually installed there, rather than from a stale copy.
 */
if (is_string($bootstrapPath = $_ENV['LARAVEL_BOOTSTRAP_PATH'] ?? null) && $bootstrapPath !== '') {
    $app->useBootstrapPath($bootstrapPath);
}

return $app;
