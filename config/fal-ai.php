<?php

return [

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
    | This value is the base URL for the Fal.ai API. You should not need to
    | change this unless you are using a custom Fal.ai instance.
    |
    */

    'base_url' => 'https://api.fal.ai',

    /*
    |--------------------------------------------------------------------------
    | Fal.ai API Timeout
    |--------------------------------------------------------------------------
    |
    | This value is the maximum number of seconds to wait for a response from
    | the Fal.ai API. You may increase this value for longer-running requests.
    |
    */

    'timeout' => 30,

    /*
    |--------------------------------------------------------------------------
    | Fal.ai Default Model
    |--------------------------------------------------------------------------
    |
    | This value is the default model to use when making requests to the Fal.ai
    | API. You can override this value for specific requests.
    |
    */

    'default_model' => 'fal-ai/default',

];
