<?php

declare(strict_types=1);

use Cjmellor\FalAi\Connectors\FalConnector;

beforeEach(function (): void {
    // Set up test config
    config([
        'fal-ai.api_key' => 'test-api-key-12345',
        'fal-ai.base_url' => 'https://test.fal.run',
    ]);
});

describe('FalConnector', function (): void {

    it('resolves base url from config', function (): void {
        $connector = new FalConnector();

        expect($connector->resolveBaseUrl())->toBe('https://test.fal.run');
    });

    it('uses default queue URL when no override is provided', function (): void {
        config(['fal-ai.base_url' => 'https://queue.fal.run']);

        $connector = new FalConnector();

        expect($connector->resolveBaseUrl())->toBe('https://queue.fal.run');
    });

    it('can override base URL with queue URL explicitly', function (): void {
        $connector = new FalConnector('https://queue.fal.run');

        expect($connector->resolveBaseUrl())->toBe('https://queue.fal.run');
    });

    it('can override base URL with sync URL', function (): void {
        $connector = new FalConnector('https://fal.run');

        expect($connector->resolveBaseUrl())->toBe('https://fal.run');
    });

    it('falls back to config when no override is provided', function (): void {
        config(['fal-ai.base_url' => 'https://custom.fal.run']);

        $connector = new FalConnector();

        expect($connector->resolveBaseUrl())->toBe('https://custom.fal.run');
    });

});
