<?php

return [
    /*
    |--------------------------------------------------------------------------
    | HTTP Transport Token
    |--------------------------------------------------------------------------
    |
    | Shared bearer token required by the `Mcp::web()` transport in routes/ai.php.
    | Every PMS tool writes to ClickUp and the delete tools cannot be undone, so
    | the endpoint fails closed: leave this empty and nothing is served over
    | HTTP. The stdio transport used locally never reaches the middleware.
    |
    */
    'token' => env('MCP_TOKEN'),
];
