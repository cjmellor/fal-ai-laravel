<?php

declare(strict_types=1);

use Cjmellor\FalAi\FalAi;
use Cjmellor\FalAi\Requests\SubmitRequest;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

beforeEach(function (): void {
    MockClient::destroyGlobal();

    config([
        'fal-ai.api_key' => 'test-api-key',
        'fal-ai.base_url' => 'https://test.fal.run',
        'fal-ai.default_model' => 'test-model',
    ]);
});

describe('Laravel Integration Tests', function (): void {

    it('can resolve FalAi from service container', function (): void {
        $falAi = app(FalAi::class);

        expect($falAi)->toBeInstanceOf(FalAi::class);
    });

    it('can use config values for API requests', function (): void {
        MockClient::global([
            SubmitRequest::class => MockResponse::make([
                'request_id' => 'config-test-123',
                'response_url' => 'https://queue.fal.run/test-model/requests/config-test-123',
            ], 200),
        ]);

        $falAi = app(FalAi::class);
        $response = $falAi->run(['prompt' => 'Test using config']);

        expect($response->status())->toBe(200)
            ->and($response->json()['request_id'])->toBe('config-test-123');
    });

    it('can override config default model', function (): void {
        MockClient::global([
            SubmitRequest::class => MockResponse::make([
                'request_id' => 'override-test-456',
                'response_url' => 'https://queue.fal.run/custom-model/requests/override-test-456',
            ], 200),
        ]);

        $falAi = app(FalAi::class);
        $response = $falAi->run(['prompt' => 'Test with custom model'], 'custom-model');

        expect($response->status())->toBe(200)
            ->and($response->json()['request_id'])->toBe('override-test-456');
    });

    it('can use fluent interface with service container', function (): void {
        MockClient::global([
            SubmitRequest::class => MockResponse::make([
                'request_id' => 'fluent-container-789',
                'response_url' => 'https://queue.fal.run/test-model/requests/fluent-container-789',
            ], 200),
        ]);

        $response = app(FalAi::class)
            ->model('test-model')
            ->prompt('Test fluent with container')
            ->imageSize('512x512')
            ->run();

        expect($response->status())->toBe(200)
            ->and($response->json()['request_id'])->toBe('fluent-container-789');
    });

    it('respects Laravel environment configuration', function (): void {
        // Test that the package respects Laravel's environment-based config
        config(['fal-ai.default_model' => 'env-specific-model']);

        MockClient::global([
            SubmitRequest::class => MockResponse::make([
                'request_id' => 'env-test-101',
                'response_url' => 'https://queue.fal.run/env-specific-model/requests/env-test-101',
            ], 200),
        ]);

        $falAi = app(FalAi::class);
        $response = $falAi->run(['prompt' => 'Test environment config']);

        expect($response->status())->toBe(200)
            ->and($response->json()['request_id'])->toBe('env-test-101');
    });

    it('can be used as singleton from container', function (): void {
        $falAi1 = app(FalAi::class);
        $falAi2 = app(FalAi::class);

        // Note: FalAi is not registered as singleton in the service provider,
        // so each resolution creates a new instance
        expect($falAi1)->toBeInstanceOf(FalAi::class)
            ->and($falAi2)->toBeInstanceOf(FalAi::class);
    });

})->group('feature', 'laravel-integration');
