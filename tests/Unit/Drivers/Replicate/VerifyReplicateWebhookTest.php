<?php

declare(strict_types=1);

use Cjmellor\FalAi\Drivers\Replicate\Webhooks\ReplicateWebhookVerifier;
use Cjmellor\FalAi\Drivers\Replicate\Webhooks\VerifyReplicateWebhook;
use Cjmellor\FalAi\Exceptions\WebhookVerificationException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

beforeEach(function (): void {
    $this->mockVerifier = Mockery::mock(ReplicateWebhookVerifier::class);
    $this->middleware = new VerifyReplicateWebhook($this->mockVerifier);
});

afterEach(function (): void {
    Mockery::close();
});

it('passes request when verification succeeds', function (): void {
    $request = Request::create('/webhook', 'POST', [], [], [], [
        'HTTP_WEBHOOK_ID' => 'msg_test123',
        'HTTP_WEBHOOK_TIMESTAMP' => (string) time(),
        'HTTP_WEBHOOK_SIGNATURE' => 'v1,valid-signature',
    ], json_encode(['status' => 'succeeded']));

    $this->mockVerifier
        ->shouldReceive('verify')
        ->once()
        ->with($request)
        ->andReturn(true);

    $nextCalled = false;
    $next = function ($req) use (&$nextCalled): Response {
        $nextCalled = true;

        return new Response('Success');
    };

    $response = $this->middleware->handle($request, $next);

    expect($nextCalled)->toBeTrue();
    expect($response->getContent())->toBe('Success');
});

it('sets replicate_webhook_verified attribute on request', function (): void {
    $request = Request::create('/webhook', 'POST', [], [], [], [
        'HTTP_WEBHOOK_ID' => 'msg_test123',
        'HTTP_WEBHOOK_TIMESTAMP' => (string) time(),
        'HTTP_WEBHOOK_SIGNATURE' => 'v1,valid-signature',
    ], json_encode(['status' => 'succeeded']));

    $this->mockVerifier
        ->shouldReceive('verify')
        ->once()
        ->with($request)
        ->andReturn(true);

    $capturedRequest = null;
    $next = function ($req) use (&$capturedRequest): Response {
        $capturedRequest = $req;

        return new Response('Success');
    };

    $this->middleware->handle($request, $next);

    expect($capturedRequest->attributes->get('replicate_webhook_verified'))->toBeTrue();
});

it('returns unauthorized when verification fails', function (): void {
    config(['app.debug' => false]);

    $request = Request::create('/webhook', 'POST', [], [], [], [
        'HTTP_WEBHOOK_ID' => 'msg_test123',
        'HTTP_WEBHOOK_TIMESTAMP' => (string) time(),
        'HTTP_WEBHOOK_SIGNATURE' => 'v1,invalid-signature',
    ], json_encode(['status' => 'succeeded']));

    $this->mockVerifier
        ->shouldReceive('verify')
        ->once()
        ->with($request)
        ->andThrow(new WebhookVerificationException('Signature verification failed'));

    $nextCalled = false;
    $next = function ($req) use (&$nextCalled): Response {
        $nextCalled = true;

        return new Response('Success');
    };

    $response = $this->middleware->handle($request, $next);

    expect($nextCalled)->toBeFalse();
    expect($response->getStatusCode())->toBe(401);
    expect($response->headers->get('Content-Type'))->toBe('application/json');

    $responseData = json_decode($response->getContent(), true);
    expect($responseData)->toBe([
        'error' => 'Webhook verification failed',
        'message' => 'Invalid webhook signature',
    ]);
});

it('works with different verification errors', function (string $errorMessage, string $expectedMessage): void {
    config(['app.debug' => false]);

    $request = Request::create('/webhook', 'POST');

    $this->mockVerifier
        ->shouldReceive('verify')
        ->once()
        ->with($request)
        ->andThrow(new WebhookVerificationException($errorMessage));

    $next = function ($req): Response {
        return new Response('Success');
    };

    $response = $this->middleware->handle($request, $next);

    expect($response->getStatusCode())->toBe(401);

    $responseData = json_decode($response->getContent(), true);
    expect($responseData)->toBe([
        'error' => 'Webhook verification failed',
        'message' => $expectedMessage,
    ]);
})->with([
    'missing headers' => ['Missing required header: webhook-id', 'Invalid webhook signature'],
    'timestamp too old' => ['Timestamp too old or too far in the future', 'Invalid webhook signature'],
    'invalid signature' => ['Signature verification failed', 'Invalid webhook signature'],
    'no signatures' => ['No valid signatures found in header', 'Invalid webhook signature'],
]);

it('shows actual error message in debug mode', function (): void {
    config(['app.debug' => true]);

    $request = Request::create('/webhook', 'POST');
    $actualError = 'Missing required header: webhook-id';

    $this->mockVerifier
        ->shouldReceive('verify')
        ->once()
        ->with($request)
        ->andThrow(new WebhookVerificationException($actualError));

    $next = function ($req): Response {
        return new Response('Success');
    };

    $response = $this->middleware->handle($request, $next);

    expect($response->getStatusCode())->toBe(401);

    $responseData = json_decode($response->getContent(), true);
    expect($responseData)->toBe([
        'error' => 'Webhook verification failed',
        'message' => $actualError,
    ]);
});

covers(VerifyReplicateWebhook::class);
