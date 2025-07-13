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

];
