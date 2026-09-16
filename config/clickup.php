<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | ClickUp API Token / OAuth Token
    |--------------------------------------------------------------------------
    |
    | Your personal API key (starts with 'pk_') or an OAuth access token.
    |
    */
    'api_key' => env('CLICKUP_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Authentication Mode
    |--------------------------------------------------------------------------
    |
    | Set to true if 'api_key' represents an OAuth access token instead of a
    | personal API key. ClickUp accepts both as a bare Authorization header, so
    | this only sharpens the error messages raised on a 401.
    |
    */
    'is_oauth' => env('CLICKUP_IS_OAUTH', false),

    /*
    |--------------------------------------------------------------------------
    | OAuth Application Credentials
    |--------------------------------------------------------------------------
    |
    | Needed only when your app performs the OAuth flow on behalf of other
    | ClickUp users. Create an app under Settings > Apps in ClickUp.
    |
    */
    'oauth' => [
        'client_id' => env('CLICKUP_CLIENT_ID'),
        'client_secret' => env('CLICKUP_CLIENT_SECRET'),
        'redirect_uri' => env('CLICKUP_REDIRECT_URI'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default List ID
    |--------------------------------------------------------------------------
    |
    | Optional target List ID used by ClickUp::createTask() and
    | ClickUp::listTasks() when no List is passed explicitly.
    |
    */
    'list_id' => env('CLICKUP_LIST_ID'),

    /*
    |--------------------------------------------------------------------------
    | Webhooks
    |--------------------------------------------------------------------------
    |
    | 'secret' is the value ClickUp returned when the webhook was created; it is
    | used to verify the X-Signature header on every delivery. The package can
    | also register a ready-made receiver route that dispatches a
    | Maya719\ClickUp\Events\WebhookReceived event for each verified delivery.
    |
    */
    'webhooks' => [
        'secret' => env('CLICKUP_WEBHOOK_SECRET'),

        'route' => [
            'enabled' => env('CLICKUP_WEBHOOK_ROUTE_ENABLED', false),
            'path' => env('CLICKUP_WEBHOOK_ROUTE_PATH', 'clickup/webhook'),
            'name' => 'clickup.webhook',
            'middleware' => ['api'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | API Base URL
    |--------------------------------------------------------------------------
    */
    'base_url' => env('CLICKUP_BASE_URL', 'https://api.clickup.com/api/v2/'),

    /*
    |--------------------------------------------------------------------------
    | Request Behaviour
    |--------------------------------------------------------------------------
    |
    | 'retries' controls how many times a 429 or 5xx response is retried. Retries
    | honour ClickUp's Retry-After / X-RateLimit-Reset headers where present and
    | otherwise back off exponentially.
    |
    */
    'retries' => env('CLICKUP_RETRIES', 2),
    'timeout' => env('CLICKUP_TIMEOUT', 30),
];
