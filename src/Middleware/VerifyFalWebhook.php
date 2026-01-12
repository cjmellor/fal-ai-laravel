<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Middleware;

use Cjmellor\FalAi\Exceptions\WebhookVerificationException;
use Cjmellor\FalAi\Services\WebhookVerifier;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class VerifyFalWebhook
{
    public function __construct(
        private readonly WebhookVerifier $verifier
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (SymfonyResponse)  $next
     */
    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        try {
            $this->verifier->verify($request);

            // Add verified flag to request for downstream use
            $request->attributes->set('fal_webhook_verified', true);

            return $next($request);

        } catch (WebhookVerificationException $e) {
            Log::warning('Fal webhook verification failed', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'error' => 'Webhook verification failed',
                'message' => config('app.debug') ? $e->getMessage() : 'Invalid webhook signature',
            ], Response::HTTP_UNAUTHORIZED);
        }
    }
}
