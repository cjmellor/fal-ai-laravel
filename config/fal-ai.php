<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default AI Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default AI driver that will be used.
    | You may change this to any of the drivers defined below.
    |
    | Supported: "fal", "replicate"
    |
    */

    'default' => env('AI_DRIVER', 'fal'),

    /*
    |--------------------------------------------------------------------------
    | AI Drivers
    |--------------------------------------------------------------------------
    |
    | Configuration for each AI provider driver.
    |
    */

    'drivers' => [

        'fal' => [
            /*
            |--------------------------------------------------------------------------
            | Fal.ai API Key
            |--------------------------------------------------------------------------
            |
            | This value is the API key used to authenticate with the Fal.ai API.
            | You can obtain your API key from the Fal.ai dashboard.
            |
            */
            'api_key' => env('FAL_API_KEY'),

            /*
            |--------------------------------------------------------------------------
            | Fal.ai Base URL
            |--------------------------------------------------------------------------
            |
            | This value is the base URL for the Fal.ai queue API. You should not
            | need to change this unless you are using a custom Fal.ai instance.
            |
            */
            'base_url' => env('FAL_BASE_URL', 'https://queue.fal.run'),

            /*
            |--------------------------------------------------------------------------
            | Fal.ai Sync URL
            |--------------------------------------------------------------------------
            |
            | This value is the base URL for synchronous requests and streaming.
            |
            */
            'sync_url' => env('FAL_SYNC_URL', 'https://fal.run'),

            /*
            |--------------------------------------------------------------------------
            | Fal.ai Platform API Base URL
            |--------------------------------------------------------------------------
            |
            | This value is the base URL for the Fal.ai Platform APIs (pricing,
            | usage, analytics, etc.). This is separate from the model execution APIs.
            |
            */
            'platform_base_url' => env('FAL_PLATFORM_URL', 'https://api.fal.ai'),

            /*
            |--------------------------------------------------------------------------
            | Fal.ai Default Model
            |--------------------------------------------------------------------------
            |
            | This value is the default model to use when making requests to the
            | Fal.ai API. You can override this value for specific requests.
            |
            */
            'default_model' => env('FAL_DEFAULT_MODEL'),

            /*
            |--------------------------------------------------------------------------
            | Webhook Configuration
            |--------------------------------------------------------------------------
            |
            | Configuration options for Fal.ai webhook verification and handling.
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
        ],

        'replicate' => [
            /*
            |--------------------------------------------------------------------------
            | Replicate API Key
            |--------------------------------------------------------------------------
            |
            | This value is the API key used to authenticate with the Replicate API.
            | You can obtain your API key from the Replicate dashboard.
            |
            */
            'api_key' => env('REPLICATE_API_KEY'),

            /*
            |--------------------------------------------------------------------------
            | Replicate Base URL
            |--------------------------------------------------------------------------
            |
            | This value is the base URL for the Replicate API. You should not
            | need to change this unless you are using a custom endpoint.
            |
            */
            'base_url' => env('REPLICATE_BASE_URL', 'https://api.replicate.com'),

            /*
            |--------------------------------------------------------------------------
            | Replicate Default Model
            |--------------------------------------------------------------------------
            |
            | This value is the default model to use when making requests to the
            | Replicate API. Format: "owner/model:version" or just the version ID.
            |
            */
            'default_model' => env('REPLICATE_DEFAULT_MODEL'),

            /*
            |--------------------------------------------------------------------------
            | Webhook Configuration
            |--------------------------------------------------------------------------
            |
            | Configuration options for Replicate webhook verification and handling.
            |
            */
            'webhook' => [
                /*
                |--------------------------------------------------------------------------
                | Verify Signatures
                |--------------------------------------------------------------------------
                |
                | Whether to verify webhook signatures. Recommended to keep enabled
                | in production environments.
                |
                */
                'verify_signatures' => env('REPLICATE_VERIFY_WEBHOOKS', true),

                /*
                |--------------------------------------------------------------------------
                | Signing Secret
                |--------------------------------------------------------------------------
                |
                | The signing secret for verifying webhook signatures. Can be obtained
                | from the Replicate API at /v1/webhooks/default/secret.
                |
                */
                'signing_secret' => env('REPLICATE_WEBHOOK_SECRET'),

                /*
                |--------------------------------------------------------------------------
                | Timestamp Tolerance
                |--------------------------------------------------------------------------
                |
                | Maximum allowed time difference in seconds between the webhook timestamp
                | and current time. This prevents replay attacks. Default is 5 minutes (300 seconds).
                |
                */
                'timestamp_tolerance' => env('REPLICATE_WEBHOOK_TIMESTAMP_TOLERANCE', 300),
            ],
        ],

    ],

];
