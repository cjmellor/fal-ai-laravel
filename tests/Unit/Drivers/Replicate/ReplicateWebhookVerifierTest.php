<?php

declare(strict_types=1);

use Cjmellor\FalAi\Drivers\Replicate\Webhooks\ReplicateWebhookVerifier;
use Cjmellor\FalAi\Exceptions\WebhookVerificationException;
use Illuminate\Http\Request;

const TEST_SECRET = 'dGVzdC1zZWNyZXQta2V5LWZvci10ZXN0aW5n';

beforeEach(function (): void {
    $this->verifier = new ReplicateWebhookVerifier();

    config([
        'fal-ai.drivers.replicate.webhook.signing_secret' => 'whsec_'.TEST_SECRET,
        'fal-ai.drivers.replicate.webhook.verify_signatures' => true,
        'fal-ai.drivers.replicate.webhook.timestamp_tolerance' => 300,
    ]);
});

function createReplicateWebhookRequest(array $headers, string $body = '{"test":"data"}'): Request
{
    $serverHeaders = [];

    foreach ($headers as $key => $value) {
        $serverHeaders["HTTP_WEBHOOK_{$key}"] = $value;
    }

    return Request::create('/webhook', 'POST', [], [], [], $serverHeaders, $body);
}

function computeReplicateSignature(string $webhookId, string $timestamp, string $body, string $secret = TEST_SECRET): string
{
    $signedContent = "{$webhookId}.{$timestamp}.{$body}";
    $secretBytes = base64_decode($secret);

    return base64_encode(hash_hmac('sha256', $signedContent, $secretBytes, true));
}

it('throws exception for missing headers', function (): void {
    $request = createReplicateWebhookRequest(['ID' => 'msg_test123']);

    expect(fn () => $this->verifier->verify($request))
        ->toThrow(WebhookVerificationException::class, 'Missing required header');
});

it('throws exception for old timestamp', function (): void {
    $request = createReplicateWebhookRequest([
        'ID' => 'msg_test123',
        'TIMESTAMP' => (string) (time() - 400),
        'SIGNATURE' => 'v1,test-signature',
    ]);

    expect(fn () => $this->verifier->verify($request))
        ->toThrow(WebhookVerificationException::class, 'Timestamp too old or too far in the future');
});

it('throws exception for future timestamp', function (): void {
    $request = createReplicateWebhookRequest([
        'ID' => 'msg_test123',
        'TIMESTAMP' => (string) (time() + 400),
        'SIGNATURE' => 'v1,test-signature',
    ]);

    expect(fn () => $this->verifier->verify($request))
        ->toThrow(WebhookVerificationException::class, 'Timestamp too old or too far in the future');
});

it('throws exception when signing secret is not configured', function (): void {
    config(['fal-ai.drivers.replicate.webhook.signing_secret' => null]);

    $request = createReplicateWebhookRequest([
        'ID' => 'msg_test123',
        'TIMESTAMP' => (string) time(),
        'SIGNATURE' => 'v1,test-signature',
    ]);

    expect(fn () => $this->verifier->verify($request))
        ->toThrow(WebhookVerificationException::class, 'signing secret not configured');
});

it('throws exception for invalid signature', function (): void {
    $request = createReplicateWebhookRequest([
        'ID' => 'msg_test123',
        'TIMESTAMP' => (string) time(),
        'SIGNATURE' => 'v1,aW52YWxpZC1zaWduYXR1cmU=',
    ]);

    expect(fn () => $this->verifier->verify($request))
        ->toThrow(WebhookVerificationException::class, 'Signature verification failed');
});

it('throws exception when no v1 signatures found', function (): void {
    $request = createReplicateWebhookRequest([
        'ID' => 'msg_test123',
        'TIMESTAMP' => (string) time(),
        'SIGNATURE' => 'v2,some-signature',
    ]);

    expect(fn () => $this->verifier->verify($request))
        ->toThrow(WebhookVerificationException::class, 'No valid signatures found');
});

it('verifies valid signature successfully', function (): void {
    config(['fal-ai.drivers.replicate.webhook.signing_secret' => TEST_SECRET]);

    $webhookId = 'msg_test123';
    $timestamp = (string) time();
    $body = '{"status":"succeeded","id":"prediction123"}';
    $signature = computeReplicateSignature($webhookId, $timestamp, $body);

    $request = createReplicateWebhookRequest([
        'ID' => $webhookId,
        'TIMESTAMP' => $timestamp,
        'SIGNATURE' => "v1,{$signature}",
    ], $body);

    expect($this->verifier->verify($request))->toBeTrue();
});

it('skips verification when disabled', function (): void {
    config(['fal-ai.drivers.replicate.webhook.verify_signatures' => false]);

    $request = Request::create('/webhook', 'POST', [], [], [], [], '{"test":"data"}');

    expect($this->verifier->verify($request))->toBeTrue();
});

it('strips whsec_ prefix from signing secret', function (): void {
    $webhookId = 'msg_test123';
    $timestamp = (string) time();
    $body = '{"status":"succeeded"}';
    $signature = computeReplicateSignature($webhookId, $timestamp, $body);

    $request = createReplicateWebhookRequest([
        'ID' => $webhookId,
        'TIMESTAMP' => $timestamp,
        'SIGNATURE' => "v1,{$signature}",
    ], $body);

    expect($this->verifier->verify($request))->toBeTrue();
});

it('extract headers returns correct values', function (): void {
    $request = createReplicateWebhookRequest([
        'ID' => 'msg_test123',
        'TIMESTAMP' => '1234567890',
        'SIGNATURE' => 'v1,test-signature',
    ]);

    expect($this->verifier->extractHeaders($request))->toBe([
        'id' => 'msg_test123',
        'timestamp' => '1234567890',
        'signature' => 'v1,test-signature',
    ]);
});

it('construct signed content returns correct format', function (): void {
    $body = '{"test":"data"}';

    expect($this->verifier->constructSignedContent('msg_test123', '1234567890', $body))
        ->toBe("msg_test123.1234567890.{$body}");
});

it('handles multiple signatures in header', function (): void {
    config(['fal-ai.drivers.replicate.webhook.signing_secret' => TEST_SECRET]);

    $webhookId = 'msg_test123';
    $timestamp = (string) time();
    $body = '{"status":"succeeded"}';
    $signature = computeReplicateSignature($webhookId, $timestamp, $body);

    $request = createReplicateWebhookRequest([
        'ID' => $webhookId,
        'TIMESTAMP' => $timestamp,
        'SIGNATURE' => "v1,invalid-sig v1,{$signature}",
    ], $body);

    expect($this->verifier->verify($request))->toBeTrue();
});

it('handles extra whitespace in signature header', function (): void {
    config(['fal-ai.drivers.replicate.webhook.signing_secret' => TEST_SECRET]);

    $webhookId = 'msg_test123';
    $timestamp = (string) time();
    $body = '{"status":"succeeded"}';
    $signature = computeReplicateSignature($webhookId, $timestamp, $body);

    $request = createReplicateWebhookRequest([
        'ID' => $webhookId,
        'TIMESTAMP' => $timestamp,
        'SIGNATURE' => "  v1,{$signature}   v1,invalid-sig  ",
    ], $body);

    expect($this->verifier->verify($request))->toBeTrue();
});

covers(ReplicateWebhookVerifier::class);
