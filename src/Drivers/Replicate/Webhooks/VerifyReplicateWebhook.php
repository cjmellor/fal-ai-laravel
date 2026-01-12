<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Drivers\Replicate\Webhooks;

use Cjmellor\FalAi\Exceptions\WebhookVerificationException;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class VerifyReplicateWebhook
{
    public function __construct(
        private readonly ReplicateWebhookVerifier $verifier
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
            $request->attributes->set('replicate_webhook_verified', true);

            return $next($request);

        } catch (WebhookVerificationException $e) {
            Log::warning('Replicate webhook verification failed', [
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
