<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | FalAi.ai API Key
    |--------------------------------------------------------------------------
    |
    | This value is the API key used to authenticate with the FalAi.ai API.
    | You can obtain your API key from the FalAi.ai dashboard.
    |
    */

    'api_key' => env('FAL_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | FalAi.ai Base URL
    |--------------------------------------------------------------------------
    |
    | This value is the base URL for the FalAi.ai API. You should not need to
    | change this unless you are using a custom FalAi.ai instance.
    |
    */

    'base_url' => 'https://queue.fal.run',

    /*
    |--------------------------------------------------------------------------
    | FalAi.ai Platform API Base URL
    |--------------------------------------------------------------------------
    |
    | This value is the base URL for the FalAi.ai Platform APIs (pricing,
    | usage, analytics, etc.). This is separate from the model execution APIs.
    |
    */

    'platform_base_url' => 'https://api.fal.ai',

    /*
    |--------------------------------------------------------------------------
    | FalAi.ai API Timeout
    |--------------------------------------------------------------------------
    |
    | NOT SURE IF FAL.AI SUPPORTS TIMEOUTS
    |
    | This value is the maximum number of seconds to wait for a response from
    | the FalAi.ai API. You may increase this value for longer-running requests.
    |
    */

    // 'timeout' => 30,

    /*
    |--------------------------------------------------------------------------
    | FalAi.ai Default Model
    |--------------------------------------------------------------------------
    |
    | This value is the default model to use when making requests to the FalAi.ai
    | API. You can override this value for specific requests.
    |
    */

    'default_model' => '',

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for webhook verification and handling.
    |
    */

    'webhook' => [
        /*
        |--------------------------------------------------------------------------
        | JWKS Cache TTL
        |--------------------------------------------------------------------------
        |
        | How long to cache the JSON Web Key Set (JWKS) from fal.ai in seconds.
        | Default is 24 hours (86400 seconds). Do not set higher than 24 hours.
        |
        */
        'jwks_cache_ttl' => env('FAL_WEBHOOK_JWKS_CACHE_TTL', 86400),

        /*
        |--------------------------------------------------------------------------
        | Timestamp Tolerance
        |--------------------------------------------------------------------------
        |
        | Maximum allowed time difference in seconds between the webhook timestamp
        | and current time. This prevents replay attacks. Default is 5 minutes (300 seconds).
        |
        */
        'timestamp_tolerance' => env('FAL_WEBHOOK_TIMESTAMP_TOLERANCE', 300),

        /*
        |--------------------------------------------------------------------------
        | Verification Timeout
        |--------------------------------------------------------------------------
        |
        | Timeout in seconds for fetching JWKS from fal.ai. Default is 10 seconds.
        |
        */
        'verification_timeout' => env('FAL_WEBHOOK_VERIFICATION_TIMEOUT', 10),
    ],

];
