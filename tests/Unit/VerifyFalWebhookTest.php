<?php

declare(strict_types=1);

use Cjmellor\FalAi\Exceptions\WebhookVerificationException;
use Cjmellor\FalAi\Middleware\VerifyFalWebhook;
use Cjmellor\FalAi\Services\WebhookVerifier;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

beforeEach(function (): void {
    $this->mockVerifier = Mockery::mock(WebhookVerifier::class);
    $this->middleware = new VerifyFalWebhook($this->mockVerifier);
});

afterEach(function (): void {
    Mockery::close();
});

it('passes request when verification succeeds', function (): void {
    $request = Request::create('/webhook', 'POST', [], [], [], [
        'HTTP_X_FAL_WEBHOOK_REQUEST_ID' => 'test-id',
        'HTTP_X_FAL_WEBHOOK_USER_ID' => 'user-123',
        'HTTP_X_FAL_WEBHOOK_TIMESTAMP' => (string) time(),
        'HTTP_X_FAL_WEBHOOK_SIGNATURE' => 'valid-signature',
    ], json_encode(['test' => 'data']));

    $this->mockVerifier
        ->shouldReceive('verify')
        ->once()
        ->with($request)
        ->andReturn(true);

    $nextCalled = false;
    $next = function ($req) use (&$nextCalled): \Illuminate\Http\Response {
        $nextCalled = true;

        return new Response('Success');
    };

    $response = $this->middleware->handle($request, $next);

    expect($nextCalled)->toBeTrue();
    expect($response->getContent())->toBe('Success');
});

it('returns unauthorized when verification fails', function (): void {
    config(['app.debug' => false]); // Ensure consistent behavior

    $request = Request::create('/webhook', 'POST', [], [], [], [
        'HTTP_X_FAL_WEBHOOK_REQUEST_ID' => 'test-id',
        'HTTP_X_FAL_WEBHOOK_USER_ID' => 'user-123',
        'HTTP_X_FAL_WEBHOOK_TIMESTAMP' => (string) time(),
        'HTTP_X_FAL_WEBHOOK_SIGNATURE' => 'invalid-signature',
    ], json_encode(['test' => 'data']));

    $this->mockVerifier
        ->shouldReceive('verify')
        ->once()
        ->with($request)
        ->andThrow(new WebhookVerificationException('Invalid signature'));

    $nextCalled = false;
    $next = function ($req) use (&$nextCalled): \Illuminate\Http\Response {
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

it('logs verification failure details', function (): void {
    $request = Request::create('/webhook', 'POST', [], [], [], [
        'HTTP_X_FAL_WEBHOOK_REQUEST_ID' => 'test-id',
        'HTTP_X_FAL_WEBHOOK_USER_ID' => 'user-123',
        'HTTP_X_FAL_WEBHOOK_TIMESTAMP' => (string) time(),
        'HTTP_X_FAL_WEBHOOK_SIGNATURE' => 'invalid-signature',
    ], json_encode(['test' => 'data']));

    $request->server->set('REMOTE_ADDR', '192.168.1.1');
    $request->headers->set('User-Agent', 'Test User Agent');

    $this->mockVerifier
        ->shouldReceive('verify')
        ->once()
        ->with($request)
        ->andThrow(new WebhookVerificationException('Timestamp too old'));

    $next = function ($req): \Illuminate\Http\Response {
        return new Response('Success');
    };

    $response = $this->middleware->handle($request, $next);

    expect($response->getStatusCode())->toBe(401);
});

it('works with different verification errors', function (string $errorMessage, string $expectedMessage): void {
    config(['app.debug' => false]); // Ensure debug is off for consistent testing

    $request = Request::create('/webhook', 'POST');

    $this->mockVerifier
        ->shouldReceive('verify')
        ->once()
        ->with($request)
        ->andThrow(new WebhookVerificationException($errorMessage));

    $next = function ($req): \Illuminate\Http\Response {
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
    'missing headers' => ['Missing required headers', 'Invalid webhook signature'],
    'timestamp too old' => ['Timestamp too old', 'Invalid webhook signature'],
    'invalid signature' => ['Invalid signature format', 'Invalid webhook signature'],
]);

it('shows actual error message in debug mode', function (): void {
    config(['app.debug' => true]);

    $request = Request::create('/webhook', 'POST');
    $actualError = 'Missing required headers';

    $this->mockVerifier
        ->shouldReceive('verify')
        ->once()
        ->with($request)
        ->andThrow(new WebhookVerificationException($actualError));

    $next = function ($req): \Illuminate\Http\Response {
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

covers(VerifyFalWebhook::class);
