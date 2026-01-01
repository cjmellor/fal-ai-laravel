<?php

declare(strict_types=1);

use Cjmellor\FalAi\FalAi;
use Cjmellor\FalAi\Requests\SubmitRequest;
use InvalidArgumentException;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

beforeEach(function (): void {
    config()->set('fal-ai.api_key', 'test-api-key');
    MockClient::destroyGlobal();
});

it('completes webhook flow', function (): void {
    // Mock the HTTP request to fal.ai using Saloon
    MockClient::global([
        SubmitRequest::class => MockResponse::make([
            'request_id' => 'test-request-id-123',
            'status' => 'IN_QUEUE',
        ], 200),
    ]);

    $falAi = new FalAi('test-api-key');
    $webhookUrl = 'https://myapp.com/webhooks/fal';

    // Test the fluent API with webhook
    $request = $falAi->model('fal-ai/flux/schnell')
        ->withWebhook($webhookUrl)
        ->prompt('A beautiful sunset over mountains')
        ->imageSize('landscape_4_3')
        ->numImages(1);

    // Verify webhook URL is set
    expect($request->getWebhookUrl())->toBe($webhookUrl);

    // Execute the request
    $response = $request->run();

    // Verify the response
    expect($response)
        ->toBeInstanceOf(Cjmellor\FalAi\Responses\SubmitResponse::class)
        ->and($response->requestId)->toBe('test-request-id-123');
});

it('validates webhook url in integration', function (): void {
    $falAi = new FalAi('test-api-key');

    // Test invalid URL
    expect(fn (): Cjmellor\FalAi\Support\FluentRequest => $falAi->model('fal-ai/flux/schnell')
        ->withWebhook('not-a-valid-url'))
        ->toThrow(InvalidArgumentException::class, 'Invalid webhook URL provided');
});

it('requires https in integration', function (): void {
    $falAi = new FalAi('test-api-key');

    // Test HTTP URL (should fail)
    expect(fn (): Cjmellor\FalAi\Support\FluentRequest => $falAi->model('fal-ai/flux/schnell')
        ->withWebhook('http://myapp.com/webhook'))
        ->toThrow(InvalidArgumentException::class, 'Webhook URL must use HTTPS');
});

it('automatically uses queue endpoint', function (): void {
    MockClient::global([
        SubmitRequest::class => MockResponse::make(['request_id' => 'test-123'], 200),
    ]);

    $falAi = new FalAi('test-api-key');

    $response = $falAi->model('fal-ai/flux/schnell')
        ->withWebhook('https://myapp.com/webhook')
        ->prompt('Test prompt')
        ->run();

    // The mock ensures the request was made correctly
    expect($response)
        ->toBeInstanceOf(Cjmellor\FalAi\Responses\SubmitResponse::class)
        ->and($response->requestId)->toBe('test-123');
});

it('allows webhook url to be changed', function (): void {
    $falAi = new FalAi('test-api-key');

    $request = $falAi->model('fal-ai/flux/schnell')
        ->prompt('Test prompt');

    // Initially no webhook
    expect($request->getWebhookUrl())->toBeNull();

    // Set first webhook
    $request->withWebhook('https://app1.com/webhook');
    expect($request->getWebhookUrl())->toBe('https://app1.com/webhook');

    // Change to second webhook
    $request->withWebhook('https://app2.com/webhook');
    expect($request->getWebhookUrl())->toBe('https://app2.com/webhook');
});

it('works with other fluent methods', function (): void {
    MockClient::global([
        SubmitRequest::class => MockResponse::make(['request_id' => 'test-123'], 200),
    ]);

    $falAi = new FalAi('test-api-key');

    $response = $falAi->model('fal-ai/flux/schnell')
        ->prompt('A beautiful landscape')
        ->imageSize('square')
        ->withWebhook('https://myapp.com/webhook')
        ->numImages(2)
        ->seed(12345)
        ->run();

    // The mock ensures the request was made correctly
    expect($response)
        ->toBeInstanceOf(Cjmellor\FalAi\Responses\SubmitResponse::class)
        ->and($response->requestId)->toBe('test-123');
});

covers(FalAi::class);
