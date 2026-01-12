<?php

declare(strict_types=1);

use Cjmellor\FalAi\Drivers\Fal\FalDriver;
use Cjmellor\FalAi\Enums\RequestMode;
use Cjmellor\FalAi\Support\FluentRequest;

function createWebhookTestDriver(): FalDriver
{
    return new FalDriver([
        'api_key' => 'test-key',
        'base_url' => 'https://queue.fal.run',
        'sync_url' => 'https://fal.run',
        'default_model' => 'test-model',
    ]);
}

it('sets webhook url with withWebhook', function (): void {
    $request = new FluentRequest(createWebhookTestDriver(), 'test-model');
    $webhookUrl = 'https://example.com/webhook';

    $result = $request->withWebhook($webhookUrl);

    expect($result)
        ->toBeInstanceOf(FluentRequest::class)
        ->and($result->getWebhook())->toBe($webhookUrl);
});

it('validates webhook url', function (): void {
    $request = new FluentRequest(createWebhookTestDriver(), 'test-model');

    expect(fn (): FluentRequest => $request->withWebhook('not-a-valid-url'))
        ->toThrow(InvalidArgumentException::class, 'Invalid webhook URL provided');
});

it('requires https for webhook url', function (): void {
    $request = new FluentRequest(createWebhookTestDriver(), 'test-model');

    expect(fn (): FluentRequest => $request->withWebhook('http://example.com/webhook'))
        ->toThrow(InvalidArgumentException::class, 'Webhook URL must use HTTPS');
});

it('automatically uses queue mode when webhook is set', function (): void {
    $request = new FluentRequest(createWebhookTestDriver(), 'test-model');
    $webhookUrl = 'https://example.com/webhook';

    expect($request->getMode())->toBe(RequestMode::Queue);

    $request->withWebhook($webhookUrl);

    expect($request->getMode())->toBe(RequestMode::Queue);
});

it('returns null when webhook url is not set', function (): void {
    $request = new FluentRequest(createWebhookTestDriver(), 'test-model');

    expect($request->getWebhook())->toBeNull();
});

it('sets webhook url correctly', function (): void {
    $request = new FluentRequest(createWebhookTestDriver(), 'test-model');
    $webhookUrl = 'https://example.com/webhook';

    expect($request->getWebhook())->toBeNull();

    $request->withWebhook($webhookUrl);

    expect($request->getWebhook())->toBe($webhookUrl);
});

covers(FluentRequest::class);
