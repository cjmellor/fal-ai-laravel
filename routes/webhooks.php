<?php

declare(strict_types=1);

use Cjmellor\FalAi\Middleware\VerifyFalWebhook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Webhook Routes
|--------------------------------------------------------------------------
|
| These routes handle incoming webhooks from Fal.ai. They use the
| VerifyFalWebhook middleware to ensure webhook authenticity.
|
*/

// Main webhook endpoint with verification middleware
Route::post('/webhooks/fal', function (Request $request) {
    $payload = $request->json()->all();

    // Handle successful completion (status: OK)
    if (isset($payload['status']) && $payload['status'] === 'OK') {
        return response()->json(['status' => 'processed']);
    }

    // Handle errors (status: ERROR)
    if (isset($payload['status']) && $payload['status'] === 'ERROR') {
        return response()->json(['status' => 'error_processed']);
    }

    // Unknown status
    return response()->json(['status' => 'unknown']);
})->middleware(VerifyFalWebhook::class);
