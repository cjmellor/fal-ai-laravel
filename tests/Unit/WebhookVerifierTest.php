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

function createFalWebhookRequest(array $headers, string $body = '{"test":"data"}'): Request
{
    $serverHeaders = [];

    foreach ($headers as $key => $value) {
        $serverHeaders["HTTP_X_FAL_WEBHOOK_{$key}"] = $value;
    }

    return Request::create('/webhook', 'POST', [], [], [], $serverHeaders, $body);
}

function mockJwksResponse(array $keys): void
{
    Http::fake([
        'https://rest.alpha.fal.ai/.well-known/jwks.json' => Http::response(['keys' => $keys]),
    ]);

    Cache::shouldReceive('remember')
        ->once()
        ->andReturnUsing(fn ($key, $ttl, $callback) => $callback());
}

it('throws exception for missing headers', function (): void {
    $request = createFalWebhookRequest(['REQUEST_ID' => 'test-id']);

    expect(fn () => $this->verifier->verify($request))
        ->toThrow(WebhookVerificationException::class, 'Missing required header');
});

it('throws exception for old timestamp', function (): void {
    $request = createFalWebhookRequest([
        'REQUEST_ID' => 'test-id',
        'USER_ID' => 'user-123',
        'TIMESTAMP' => (string) (time() - 400),
        'SIGNATURE' => 'test-signature',
    ]);

    expect(fn () => $this->verifier->verify($request))
        ->toThrow(WebhookVerificationException::class, 'Timestamp too old or too far in the future');
});

it('throws exception for future timestamp', function (): void {
    $request = createFalWebhookRequest([
        'REQUEST_ID' => 'test-id',
        'USER_ID' => 'user-123',
        'TIMESTAMP' => (string) (time() + 400),
        'SIGNATURE' => 'test-signature',
    ]);

    expect(fn () => $this->verifier->verify($request))
        ->toThrow(WebhookVerificationException::class, 'Timestamp too old or too far in the future');
});

it('throws exception when jwks fetch fails', function (): void {
    Http::fake([
        'https://rest.alpha.fal.ai/.well-known/jwks.json' => Http::response(null, 500),
    ]);

    Cache::shouldReceive('remember')
        ->once()
        ->andReturnUsing(fn ($key, $ttl, $callback) => $callback());

    $request = createFalWebhookRequest([
        'REQUEST_ID' => 'test-id',
        'USER_ID' => 'user-123',
        'TIMESTAMP' => (string) time(),
        'SIGNATURE' => 'test-signature',
    ]);

    expect(fn () => $this->verifier->verify($request))
        ->toThrow(WebhookVerificationException::class, 'Failed to fetch JWKS');
});

it('throws exception for invalid jwks format', function (): void {
    mockJwksResponse([['kty' => 'OKP']]);

    $request = createFalWebhookRequest([
        'REQUEST_ID' => 'test-id',
        'USER_ID' => 'user-123',
        'TIMESTAMP' => (string) time(),
        'SIGNATURE' => 'test-signature',
    ]);

    expect(fn () => $this->verifier->verify($request))
        ->toThrow(WebhookVerificationException::class, 'No valid public keys found in JWKS');
});

it('throws exception for invalid signature', function (): void {
    $testPublicKey = 'MCowBQYDK2VwAyEAm3zVJjvFvwHyp3Fzov6F6b5uOkLlxBjHVU2E0q3rA9s=';

    mockJwksResponse([
        [
            'kty' => 'OKP',
            'crv' => 'Ed25519',
            'x' => $testPublicKey,
            'use' => 'sig',
            'alg' => 'EdDSA',
        ],
    ]);

    $request = createFalWebhookRequest([
        'REQUEST_ID' => 'test-id',
        'USER_ID' => 'user-123',
        'TIMESTAMP' => (string) time(),
        'SIGNATURE' => str_repeat('0123456789abcdef', 8),
    ]);

    expect(fn () => $this->verifier->verify($request))
        ->toThrow(WebhookVerificationException::class, 'Signature verification failed');
});

it('extract headers returns correct values', function (): void {
    $request = createFalWebhookRequest([
        'REQUEST_ID' => 'test-id',
        'USER_ID' => 'user-123',
        'TIMESTAMP' => '1234567890',
        'SIGNATURE' => 'test-signature',
    ]);

    $method = (new ReflectionClass($this->verifier))->getMethod('extractHeaders');

    expect($method->invoke($this->verifier, $request))->toBe([
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

    $body = '{"test":"data"}';
    $bodyHash = hash('sha256', $body);

    $method = (new ReflectionClass($this->verifier))->getMethod('constructMessage');

    expect($method->invoke($this->verifier, $headers, $body))
        ->toBe("test-id\nuser-123\n1234567890\n{$bodyHash}");
});

it('clearCache removes JWKS from cache', function (): void {
    Cache::shouldReceive('forget')
        ->once()
        ->with('fal_ai_jwks')
        ->andReturnTrue();

    $this->verifier->clearCache();
});

it('verifies valid signature successfully', function (): void {
    $keypair = sodium_crypto_sign_keypair();
    $publicKey = sodium_crypto_sign_publickey($keypair);
    $secretKey = sodium_crypto_sign_secretkey($keypair);
    $publicKeyBase64 = mb_rtrim(strtr(base64_encode($publicKey), '+/', '-_'), '=');

    $requestId = 'test-request-id';
    $userId = 'test-user-id';
    $timestamp = (string) time();
    $body = '{"test":"data"}';
    $bodyHash = hash('sha256', $body);

    $message = "{$requestId}\n{$userId}\n{$timestamp}\n{$bodyHash}";
    $signatureHex = bin2hex(sodium_crypto_sign_detached($message, $secretKey));

    mockJwksResponse([
        [
            'kty' => 'OKP',
            'crv' => 'Ed25519',
            'x' => $publicKeyBase64,
            'use' => 'sig',
            'alg' => 'EdDSA',
        ],
    ]);

    $request = createFalWebhookRequest([
        'REQUEST_ID' => $requestId,
        'USER_ID' => $userId,
        'TIMESTAMP' => $timestamp,
        'SIGNATURE' => $signatureHex,
    ], $body);

    expect($this->verifier->verify($request))->toBeTrue();
});

it('includes key errors in exception message when sodium fails', function (): void {
    mockJwksResponse([
        [
            'kty' => 'OKP',
            'crv' => 'Ed25519',
            'x' => base64_encode('short'),
            'use' => 'sig',
            'alg' => 'EdDSA',
        ],
    ]);

    $request = createFalWebhookRequest([
        'REQUEST_ID' => 'test-id',
        'USER_ID' => 'user-123',
        'TIMESTAMP' => (string) time(),
        'SIGNATURE' => str_repeat('0123456789abcdef', 8),
    ]);

    expect(fn () => $this->verifier->verify($request))
        ->toThrow(WebhookVerificationException::class, 'Key errors');
});

it('decodes base64url strings that need padding', function (): void {
    $keypair = sodium_crypto_sign_keypair();
    $publicKey = sodium_crypto_sign_publickey($keypair);
    $publicKeyBase64 = mb_rtrim(strtr(base64_encode($publicKey), '+/', '-_'), '=');

    // Verify the key needs padding (length not divisible by 4)
    expect(mb_strlen($publicKeyBase64) % 4)->not->toBe(0);

    mockJwksResponse([
        [
            'kty' => 'OKP',
            'crv' => 'Ed25519',
            'x' => $publicKeyBase64,
            'use' => 'sig',
            'alg' => 'EdDSA',
        ],
    ]);

    $request = createFalWebhookRequest([
        'REQUEST_ID' => 'test-id',
        'USER_ID' => 'user-123',
        'TIMESTAMP' => (string) time(),
        'SIGNATURE' => str_repeat('0123456789abcdef', 8),
    ]);

    // Passes if no base64 decoding exception; signature verification failure is expected
    expect(fn () => $this->verifier->verify($request))
        ->toThrow(WebhookVerificationException::class, 'Signature verification failed');
});

covers(WebhookVerifier::class);
