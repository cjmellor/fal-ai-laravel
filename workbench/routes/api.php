<?php

declare(strict_types=1);

use Cjmellor\FalAi\Facades\FalAi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Fal AI Queue API Test Routes

/**
 * Submit a request to Flux Schnell model
 * POST /api/fal/submit
 * Body: { "prompt": "your prompt here", "image_size": "landscape_4_3", "num_inference_steps": 4 }
 */
Route::post('/fal/submit', function (Request $request) {
    try {
        // Get all request data without strict validation for easier testing
        $data = $request->all();

        // Set defaults for Flux Schnell
        $data = array_merge([
            // 'prompt' => 'a photo of a cat',
            'image_size' => 'landscape_4_3',
            'num_inference_steps' => 4,
            'num_images' => 1,
            'enable_safety_checker' => true,
            'output_format' => 'jpeg',
            'acceleration' => 'regular',
        ], $data);

        // $response = FalAi::run($data, 'fal-ai/flux-1/schnell');
        $response = FalAi::model('fal-ai/flux-1/schnell')
            ->prompt('a dog driving a green car')
            ->run();

        return response()->json([
            'success' => true,
            'data' => $response->json(),
            'status_code' => $response->status(),
        ]);
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
        ], 500);
    }
});

/**
 * Get the status of a queued request
 * GET /api/fal/status/{requestId}?logs=1
 */
Route::get('/fal/status/{requestId}', function (Request $request, string $requestId) {
    try {
        $includeLogs = $request->boolean('logs', false);

        $response = FalAi::status($requestId, $includeLogs, 'fal-ai/flux-1');

        return response()->json([
            'success' => true,
            'data' => $response->json(),
            'status_code' => $response->status(),
        ]);
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
        ], 500);
    }
});

/**
 * Get the result of a completed request
 * GET /api/fal/result/{requestId}
 */
Route::get('/fal/result/{requestId}', function (string $requestId) {
    try {
        $response = FalAi::result($requestId, 'fal-ai/flux-1');

        return response()->json([
            'success' => true,
            'data' => $response->json(),
            'status_code' => $response->status(),
        ]);
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
        ], 500);
    }
});

/**
 * Cancel a queued request
 * PUT /api/fal/cancel/{requestId}
 */
Route::put('/fal/cancel/{requestId}', function (string $requestId) {
    try {
        $response = FalAi::cancel($requestId, 'fal-ai/flux-1/schnell');

        return response()->json([
            'success' => true,
            'data' => $response->json(),
            'status_code' => $response->status(),
        ]);
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
        ], 500);
    }
});
