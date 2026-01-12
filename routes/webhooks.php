<?php

declare(strict_types=1);

use Cjmellor\FalAi\Drivers\Replicate\Webhooks\VerifyReplicateWebhook;
use Cjmellor\FalAi\Middleware\VerifyFalWebhook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Webhook Routes
|--------------------------------------------------------------------------
|
| These routes handle incoming webhooks from AI providers.
| Fal.ai uses ED25519 signature verification via VerifyFalWebhook.
| Replicate uses a signing secret for verification.
|
*/

// Fal.ai webhook endpoint with verification middleware
Route::post('/webhooks/fal', function (Request $request) {
    $payload = $request->json()->all();
    $status = $payload['status'] ?? null;

    return match ($status) {
        'OK' => response()->json(['status' => 'processed']),
        'ERROR' => response()->json(['status' => 'error_processed']),
        default => response()->json(['status' => 'unknown']),
    };
})
    ->middleware(VerifyFalWebhook::class)
    ->name('webhooks.fal');

// Replicate webhook endpoint with verification middleware
// Note: Replicate webhooks include events: start, output, logs, completed
// The webhook_events_filter controls which events are sent
Route::post('/webhooks/replicate', function (Request $request) {
    $payload = $request->json()->all();
    $status = $payload['status'] ?? null;

    // Handle prediction status changes
    return match ($status) {
        'starting' => response()->json(['status' => 'starting_processed']),
        'processing' => response()->json(['status' => 'processing_processed']),
        'succeeded' => response()->json(['status' => 'success_processed']),
        'failed' => response()->json(['status' => 'error_processed']),
        'canceled' => response()->json(['status' => 'canceled_processed']),
        default => response()->json(['status' => 'unknown']),
    };
})
    ->middleware(VerifyReplicateWebhook::class)
    ->name('webhooks.replicate');
