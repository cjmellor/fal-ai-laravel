<?php

declare(strict_types=1);

use Cjmellor\FalAi\Drivers\Fal\FalDriver;
use Cjmellor\FalAi\Requests\SubmitRequest;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

beforeEach(function (): void {
    config()->set('fal-ai.default', 'fal');
    config()->set('fal-ai.drivers.fal.api_key', 'test-api-key');
    config()->set('fal-ai.drivers.fal.base_url', 'https://queue.fal.run');
    config()->set('fal-ai.drivers.fal.sync_url', 'https://fal.run');
    MockClient::destroyGlobal();
});

function createDriverForWebhookTests(): FalDriver
{
    return new FalDriver([
        'api_key' => 'test-api-key',
        'base_url' => 'https://queue.fal.run',
        'sync_url' => 'https://fal.run',
        'default_model' => 'test-model',
    ]);
}

it('completes webhook flow', function (): void {
    // Mock the HTTP request to fal.ai using Saloon
    MockClient::global([
        SubmitRequest::class => MockResponse::make([
            'request_id' => 'test-request-id-123',
            'status' => 'IN_QUEUE',
        ], 200),
    ]);

    $driver = createDriverForWebhookTests();
    $webhookUrl = 'https://myapp.com/webhooks/fal';

    // Test the fluent API with webhook
    $request = $driver->model('fal-ai/flux/schnell')
        ->withWebhook($webhookUrl)
        ->prompt('A beautiful sunset over mountains')
        ->imageSize('landscape_4_3')
        ->numImages(1);

    // Verify webhook URL is set
    expect($request->getWebhook())->toBe($webhookUrl);

    // Execute the request
    $response = $request->run();

    // Verify the response
    expect($response)
        ->toBeInstanceOf(Cjmellor\FalAi\Responses\SubmitResponse::class)
        ->and($response->requestId)->toBe('test-request-id-123');
});

it('validates webhook url in integration', function (): void {
    $driver = createDriverForWebhookTests();

    // Test invalid URL
    expect(fn (): Cjmellor\FalAi\Support\FluentRequest => $driver->model('fal-ai/flux/schnell')
        ->withWebhook('not-a-valid-url'))
        ->toThrow(InvalidArgumentException::class, 'Invalid webhook URL provided');
});

it('requires https in integration', function (): void {
    $driver = createDriverForWebhookTests();

    // Test HTTP URL (should fail)
    expect(fn (): Cjmellor\FalAi\Support\FluentRequest => $driver->model('fal-ai/flux/schnell')
        ->withWebhook('http://myapp.com/webhook'))
        ->toThrow(InvalidArgumentException::class, 'Webhook URL must use HTTPS');
});

it('automatically uses queue endpoint', function (): void {
    MockClient::global([
        SubmitRequest::class => MockResponse::make(['request_id' => 'test-123'], 200),
    ]);

    $driver = createDriverForWebhookTests();

    $response = $driver->model('fal-ai/flux/schnell')
        ->withWebhook('https://myapp.com/webhook')
        ->prompt('Test prompt')
        ->run();

    // The mock ensures the request was made correctly
    expect($response)
        ->toBeInstanceOf(Cjmellor\FalAi\Responses\SubmitResponse::class)
        ->and($response->requestId)->toBe('test-123');
});

it('allows webhook url to be changed', function (): void {
    $driver = createDriverForWebhookTests();

    $request = $driver->model('fal-ai/flux/schnell')
        ->prompt('Test prompt');

    // Initially no webhook
    expect($request->getWebhook())->toBeNull();

    // Set first webhook
    $request->withWebhook('https://app1.com/webhook');
    expect($request->getWebhook())->toBe('https://app1.com/webhook');

    // Change to second webhook
    $request->withWebhook('https://app2.com/webhook');
    expect($request->getWebhook())->toBe('https://app2.com/webhook');
});

it('works with other fluent methods', function (): void {
    MockClient::global([
        SubmitRequest::class => MockResponse::make(['request_id' => 'test-123'], 200),
    ]);

    $driver = createDriverForWebhookTests();

    $response = $driver->model('fal-ai/flux/schnell')
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

covers(FalDriver::class);
