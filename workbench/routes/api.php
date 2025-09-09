<?php

declare(strict_types=1);

use Cjmellor\FalAi\Exceptions\WebhookVerificationException;
use Cjmellor\FalAi\Facades\FalAi;
use Cjmellor\FalAi\Middleware\VerifyFalWebhook;
use Cjmellor\FalAi\Services\WebhookVerifier;
use HosmelQ\SSE\SSEProtocolException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::post('/fal/submit', function (Request $request) {
    try {
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

// Webhook endpoint using middleware
Route::post('/webhooks/fal', function (Request $request) {
    $payload = $request->json()->all();

    Log::info('Fal.ai webhook received', [
        'payload' => $payload,
        'headers' => $request->headers->all(),
    ]);

    if (isset($payload['status']) && $payload['status'] === 'OK') {
        $requestId = $payload['request_id'];
        $gatewayRequestId = $payload['gateway_request_id'] ?? $requestId;

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

        foreach ($images as $image) {
            $imageUrl = $image['url'];
            $width = $image['width'];
            $height = $image['height'];
            $contentType = $image['content_type'] ?? 'unknown';
            $fileName = $image['file_name'] ?? 'unknown';
            $fileSize = $image['file_size'] ?? 0;

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

    Log::warning('Fal.ai webhook with unknown status', [
        'payload' => $payload,
        'status' => $payload['status'] ?? 'missing',
    ]);

    return response()->json(['status' => 'unknown']);
})->middleware(VerifyFalWebhook::class);

Route::post('/webhooks/fal-manual', function (Request $request) {
    $verifier = new WebhookVerifier();

    try {
        $verifier->verify($request);

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

Route::post(uri: '/test-stream', action: function (Request $request) {
    try {
        $streamResponse = FalAi::model(modelId: 'fal-ai/flux-1/krea')
            ->prompt('A beautiful young Thai lady wearing a bikini on the beaches in Thailand at night time')
            ->stream();

        return $streamResponse->getResponse();
    } catch (SSEProtocolException $e) {
        return response()->json([
            'error' => 'SSE Protocol Error',
            'message' => $e->getMessage(),
        ], status: 500);
    }
});
