<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /**
         * The consent screen an MCP client sends the user to before it is
         * issued a token. Published from laravel/mcp via the mcp-views tag.
         */
        Passport::authorizationView(fn (array $parameters) => view('mcp.authorize', $parameters));
    }
}
