<?php

declare(strict_types=1);

use Cjmellor\FalAi\FalAi;
use Cjmellor\FalAi\Support\FluentRequest;
use InvalidArgumentException;

it('sets webhook url with withWebhook', function (): void {
    $request = new FluentRequest(new FalAi('test-key'), 'test-model');
    $webhookUrl = 'https://example.com/webhook';

    $result = $request->withWebhook($webhookUrl);

    expect($result)
        ->toBeInstanceOf(FluentRequest::class)
        ->and($result->getWebhookUrl())->toBe($webhookUrl);
});

it('validates webhook url', function (): void {
    $request = new FluentRequest(new FalAi('test-key'), 'test-model');

    expect(fn () => $request->withWebhook('not-a-valid-url'))
        ->toThrow(InvalidArgumentException::class, 'Invalid webhook URL provided');
});

it('requires https for webhook url', function (): void {
    $request = new FluentRequest(new FalAi('test-key'), 'test-model');

    expect(fn () => $request->withWebhook('http://example.com/webhook'))
        ->toThrow(InvalidArgumentException::class, 'Webhook URL must use HTTPS');
});

it('automatically uses queue when webhook is set', function (): void {
    $request = new FluentRequest(new FalAi('test-key'), 'test-model');
    $webhookUrl = 'https://example.com/webhook';

    expect($request->getBaseUrlOverride())->toBeNull();

    $request->withWebhook($webhookUrl);

    expect($request->getBaseUrlOverride())->toBe('https://queue.fal.run');
});

it('returns null when webhook url is not set', function (): void {
    $request = new FluentRequest(new FalAi('test-key'), 'test-model');

    expect($request->getWebhookUrl())->toBeNull();
});

it('sets webhook url correctly', function (): void {
    $request = new FluentRequest(new FalAi('test-key'), 'test-model');
    $webhookUrl = 'https://example.com/webhook';

    expect($request->getWebhookUrl())->toBeNull();

    $request->withWebhook($webhookUrl);

    expect($request->getWebhookUrl())->toBe($webhookUrl);
});

covers(FluentRequest::class);
