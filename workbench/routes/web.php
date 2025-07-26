<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|---------------------------------------
-----------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return response()->json([
        'msg' => 'Hi',
    ]);
});

// Test endpoint to demonstrate webhook payload structure
Route::get('/webhooks/test', function () {
    return response()->json([
        'message' => 'Webhook endpoints are ready',
        'endpoints' => [
            'middleware' => url('/api/webhooks/fal'),
            'manual' => url('/api/webhooks/fal-manual'),
        ],
        'example_payload' => [
            'completed' => [
                'request_id' => 'req_123456789',
                'status' => 'COMPLETED',
                'data' => [
                    'images' => [
                        [
                            'url' => 'https://fal.media/files/generated-image.jpg',
                            'width' => 1024,
                            'height' => 768,
                            'content_type' => 'image/jpeg',
                        ],
                    ],
                    'seed' => 12345,
                    'has_nsfw_concepts' => [false],
                    'prompt' => 'A beautiful sunset over mountains',
                ],
            ],
            'failed' => [
                'request_id' => 'req_123456789',
                'status' => 'FAILED',
                'error' => [
                    'type' => 'ValidationError',
                    'message' => 'Invalid prompt provided',
                ],
            ],
        ],
    ]);
});
