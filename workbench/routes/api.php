<?php

declare(strict_types=1);

use Cjmellor\FalAi\Exceptions\WebhookVerificationException;
use Cjmellor\FalAi\Facades\FalAi;
use Cjmellor\FalAi\Middleware\VerifyFalWebhook;
use Cjmellor\FalAi\Services\WebhookVerifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

// Fal AI Queue API Test Routes

/**
 * Submit a request to Flux Schnell model
 * POST /api/fal/submit
 * Body: { "prompt": "your prompt here", "image_size": "landscape_4_3", "num_inference_steps": 4 }
 */
Route::post('/fal/submit', function (Request $request) {
    try {
        // Set defaults for Flux Schnell
        $data = [
            // 'prompt' => 'a photo of a cat',
            // 'image_size' => 'landscape_4_3',
            // 'num_inference_steps' => 4,
            // 'num_images' => 1,
            // 'enable_safety_checker' => true,
            // 'output_format' => 'jpeg',
            // 'acceleration' => 'regular',
        ];

        // CORRECTED: Use proper fluent API pattern as documented
        // The run() method should not accept parameters

        // Option 1: Use with() method to set data then run() (recommended for dynamic data)
        $response = FalAi::model('fal-ai/flux-1/schnell')
            ->withWebhook('https://5d594dd13af5.ngrok-free.app/webhooks/fal')
            ->prompt('dancing pickle wearing a cowboy hat in space')
            ->run();

        return response()->json(data: [
            'success' => (bool) $response->successful(),
            'data' => $response->json(),
        ], status: $response->status());
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

/*
|--------------------------------------------------------------------------
| Webhook Routes
|--------------------------------------------------------------------------
|
| These routes handle incoming webhooks from Fal.ai. They are placed in
| api.php to avoid CSRF protection which would cause 419 errors.
|
*/

// Webhook endpoint using middleware (Recommended)
Route::post('/webhooks/fal', function (Request $request) {
    $payload = $request->json()->all();
    
    // Log the full payload for debugging
    Log::info('Fal.ai webhook received', [
        'payload' => $payload,
        'headers' => $request->headers->all(),
    ]);

    // Handle successful completion (status: OK)
    if (isset($payload['status']) && $payload['status'] === 'OK') {
        $requestId = $payload['request_id'];
        $gatewayRequestId = $payload['gateway_request_id'] ?? $requestId;
        
        // Check for payload errors
        if (isset($payload['payload_error'])) {
            Log::warning('Fal.ai payload error', [
                'request_id' => $requestId,
                'gateway_request_id' => $gatewayRequestId,
                'payload_error' => $payload['payload_error'],
            ]);
            return response()->json(['status' => 'payload_error']);
        }
        
        $images = $payload['payload']['images'] ?? [];
        $seed = $payload['payload']['seed'] ?? null;

        Log::info('Fal.ai request completed', [
            'request_id' => $requestId,
            'gateway_request_id' => $gatewayRequestId,
            'image_count' => count($images),
            'seed' => $seed,
        ]);

        // Process the generated images
        foreach ($images as $image) {
            $imageUrl = $image['url'];
            $width = $image['width'];
            $height = $image['height'];
            $contentType = $image['content_type'] ?? 'unknown';
            $fileName = $image['file_name'] ?? 'unknown';
            $fileSize = $image['file_size'] ?? 0;

            // Save to database, send notifications, etc.
            Log::info('Generated image', [
                'request_id' => $requestId,
                'url' => $imageUrl,
                'dimensions' => "{$width}x{$height}",
                'content_type' => $contentType,
                'file_name' => $fileName,
                'file_size' => $fileSize,
            ]);
        }

        return response()->json(['status' => 'processed']);
    }

    // Handle errors (status: ERROR)
    if (isset($payload['status']) && $payload['status'] === 'ERROR') {
        $requestId = $payload['request_id'];
        $gatewayRequestId = $payload['gateway_request_id'] ?? $requestId;
        $error = $payload['error'] ?? 'Unknown error';
        $errorPayload = $payload['payload'] ?? null;

        Log::error('Fal.ai request failed', [
            'request_id' => $requestId,
            'gateway_request_id' => $gatewayRequestId,
            'error' => $error,
            'error_payload' => $errorPayload,
        ]);

        return response()->json(['status' => 'error_processed']);
    }

    // Log unknown status for debugging
    Log::warning('Fal.ai webhook with unknown status', [
        'payload' => $payload,
        'status' => $payload['status'] ?? 'missing',
    ]);

    return response()->json(['status' => 'unknown']);
})->middleware(VerifyFalWebhook::class);

// Webhook endpoint with manual verification
Route::post('/webhooks/fal-manual', function (Request $request) {
    $verifier = new WebhookVerifier();

    try {
        // Verify the webhook signature
        $verifier->verify($request);

        // Webhook is valid, process the payload
        $payload = $request->json()->all();

        Log::info('Manual webhook verification successful', [
            'request_id' => $payload['request_id'] ?? 'unknown',
            'status' => $payload['status'] ?? 'unknown',
        ]);

        return response()->json(['status' => 'verified']);

    } catch (WebhookVerificationException $e) {
        Log::warning('Webhook verification failed', [
            'error' => $e->getMessage(),
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'error' => 'Unauthorized',
            'message' => 'Webhook verification failed',
        ], 401);
    }
});
