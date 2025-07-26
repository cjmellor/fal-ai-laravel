<?php

declare(strict_types=1);

use Cjmellor\FalAi\Exceptions\WebhookVerificationException;
use Cjmellor\FalAi\Services\WebhookVerifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    $this->verifier = new WebhookVerifier();
});

it('throws exception for missing headers', function (): void {
    $request = Request::create('/webhook', 'POST', [], [], [], [
        'HTTP_X_FAL_WEBHOOK_REQUEST_ID' => 'test-id',
        // Missing other required headers
    ], json_encode(['test' => 'data']));

    expect(fn () => $this->verifier->verify($request))
        ->toThrow(WebhookVerificationException::class, 'Missing required header');
});

it('throws exception for old timestamp', function (): void {
    $oldTimestamp = (string) (time() - 400); // 400 seconds ago (beyond 5 min tolerance)

    $request = Request::create('/webhook', 'POST', [], [], [], [
        'HTTP_X_FAL_WEBHOOK_REQUEST_ID' => 'test-id',
        'HTTP_X_FAL_WEBHOOK_USER_ID' => 'user-123',
        'HTTP_X_FAL_WEBHOOK_TIMESTAMP' => $oldTimestamp,
        'HTTP_X_FAL_WEBHOOK_SIGNATURE' => 'test-signature',
    ], json_encode(['test' => 'data']));

    expect(fn () => $this->verifier->verify($request))
        ->toThrow(WebhookVerificationException::class, 'Timestamp too old or too far in the future');
});

it('throws exception for future timestamp', function (): void {
    $futureTimestamp = (string) (time() + 400); // 400 seconds in future

    $request = Request::create('/webhook', 'POST', [], [], [], [
        'HTTP_X_FAL_WEBHOOK_REQUEST_ID' => 'test-id',
        'HTTP_X_FAL_WEBHOOK_USER_ID' => 'user-123',
        'HTTP_X_FAL_WEBHOOK_TIMESTAMP' => $futureTimestamp,
        'HTTP_X_FAL_WEBHOOK_SIGNATURE' => 'test-signature',
    ], json_encode(['test' => 'data']));

    expect(fn () => $this->verifier->verify($request))
        ->toThrow(WebhookVerificationException::class, 'Timestamp too old or too far in the future');
});

it('throws exception when jwks fetch fails', function (): void {
    Http::fake([
        'https://rest.alpha.fal.ai/.well-known/jwks.json' => Http::response(null, 500),
    ]);

    Cache::shouldReceive('remember')
        ->once()
        ->andReturnUsing(function ($key, $ttl, $callback) {
            return $callback();
        });

    $request = Request::create('/webhook', 'POST', [], [], [], [
        'HTTP_X_FAL_WEBHOOK_REQUEST_ID' => 'test-id',
        'HTTP_X_FAL_WEBHOOK_USER_ID' => 'user-123',
        'HTTP_X_FAL_WEBHOOK_TIMESTAMP' => (string) time(),
        'HTTP_X_FAL_WEBHOOK_SIGNATURE' => 'test-signature',
    ], json_encode(['test' => 'data']));

    expect(fn () => $this->verifier->verify($request))
        ->toThrow(WebhookVerificationException::class, 'Failed to fetch JWKS');
});

it('throws exception for invalid jwks format', function (): void {
    Http::fake([
        'https://rest.alpha.fal.ai/.well-known/jwks.json' => Http::response([
            'keys' => [
                // Missing required fields
                ['kty' => 'OKP'],
            ],
        ]),
    ]);

    Cache::shouldReceive('remember')
        ->once()
        ->andReturnUsing(function ($key, $ttl, $callback) {
            return $callback();
        });

    $request = Request::create('/webhook', 'POST', [], [], [], [
        'HTTP_X_FAL_WEBHOOK_REQUEST_ID' => 'test-id',
        'HTTP_X_FAL_WEBHOOK_USER_ID' => 'user-123',
        'HTTP_X_FAL_WEBHOOK_TIMESTAMP' => (string) time(),
        'HTTP_X_FAL_WEBHOOK_SIGNATURE' => 'test-signature',
    ], json_encode(['test' => 'data']));

    expect(fn () => $this->verifier->verify($request))
        ->toThrow(WebhookVerificationException::class, 'No valid public keys found in JWKS');
});

it('throws exception for invalid signature', function (): void {
    // Mock a valid JWKS response with a test key
    $testPublicKey = 'MCowBQYDK2VwAyEAm3zVJjvFvwHyp3Fzov6F6b5uOkLlxBjHVU2E0q3rA9s=';

    Http::fake([
        'https://rest.alpha.fal.ai/.well-known/jwks.json' => Http::response([
            'keys' => [
                [
                    'kty' => 'OKP',
                    'crv' => 'Ed25519',
                    'x' => $testPublicKey,
                    'use' => 'sig',
                    'alg' => 'EdDSA',
                ],
            ],
        ]),
    ]);

    Cache::shouldReceive('remember')
        ->once()
        ->andReturnUsing(function ($key, $ttl, $callback) {
            return $callback();
        });

    $request = Request::create('/webhook', 'POST', [], [], [], [
        'HTTP_X_FAL_WEBHOOK_REQUEST_ID' => 'test-id',
        'HTTP_X_FAL_WEBHOOK_USER_ID' => 'user-123',
        'HTTP_X_FAL_WEBHOOK_TIMESTAMP' => (string) time(),
        'HTTP_X_FAL_WEBHOOK_SIGNATURE' => '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef',
    ], json_encode(['test' => 'data']));

    expect(fn () => $this->verifier->verify($request))
        ->toThrow(WebhookVerificationException::class, 'Signature verification failed');
});

it('extract headers returns correct values', function (): void {
    $request = Request::create('/webhook', 'POST', [], [], [], [
        'HTTP_X_FAL_WEBHOOK_REQUEST_ID' => 'test-id',
        'HTTP_X_FAL_WEBHOOK_USER_ID' => 'user-123',
        'HTTP_X_FAL_WEBHOOK_TIMESTAMP' => '1234567890',
        'HTTP_X_FAL_WEBHOOK_SIGNATURE' => 'test-signature',
    ]);

    $reflection = new ReflectionClass($this->verifier);
    $method = $reflection->getMethod('extractHeaders');
    $method->setAccessible(true);

    $headers = $method->invoke($this->verifier, $request);

    expect($headers)->toBe([
        'request_id' => 'test-id',
        'user_id' => 'user-123',
        'timestamp' => '1234567890',
        'signature' => 'test-signature',
    ]);
});

it('construct message returns correct format', function (): void {
    $headers = [
        'request_id' => 'test-id',
        'user_id' => 'user-123',
        'timestamp' => '1234567890',
        'signature' => 'test-signature',
    ];

    $body = json_encode(['test' => 'data']);
    $bodyHash = hash('sha256', $body);

    $reflection = new ReflectionClass($this->verifier);
    $method = $reflection->getMethod('constructMessage');
    $method->setAccessible(true);

    $message = $method->invoke($this->verifier, $headers, $body);

    $expected = "test-id\nuser-123\n1234567890\n{$bodyHash}";
    expect($message)->toBe($expected);
});

covers(WebhookVerifier::class);
