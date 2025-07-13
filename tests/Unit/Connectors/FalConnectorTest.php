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

});
