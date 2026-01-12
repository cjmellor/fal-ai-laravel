<?php

declare(strict_types=1);

use Cjmellor\FalAi\Contracts\DriverInterface;
use Cjmellor\FalAi\Drivers\Fal\FalDriver;
use Cjmellor\FalAi\Manager\AIManager;
use Cjmellor\FalAi\Requests\SubmitRequest;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

beforeEach(function (): void {
    MockClient::destroyGlobal();

    config([
        'fal-ai.default' => 'fal',
        'fal-ai.drivers.fal.api_key' => 'test-api-key',
        'fal-ai.drivers.fal.base_url' => 'https://test.fal.run',
        'fal-ai.drivers.fal.sync_url' => 'https://fal.run',
        'fal-ai.drivers.fal.default_model' => 'test-model',
    ]);
});

describe('Laravel Integration Tests', function (): void {

    it('can resolve AIManager from service container', function (): void {
        $manager = app('fal-ai');

        expect($manager)->toBeInstanceOf(AIManager::class);
    });

    it('can resolve driver from service container', function (): void {
        $manager = app('fal-ai');
        $driver = $manager->driver();

        expect($driver)->toBeInstanceOf(DriverInterface::class)
            ->and($driver)->toBeInstanceOf(FalDriver::class);
    });

    it('can use config values for API requests', function (): void {
        MockClient::global([
            SubmitRequest::class => MockResponse::make([
                'request_id' => 'config-test-123',
                'response_url' => 'https://queue.fal.run/test-model/requests/config-test-123',
            ], 200),
        ]);

        $manager = app('fal-ai');
        $response = $manager
            ->model('test-model')
            ->prompt('Test using config')
            ->run();

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

        $manager = app('fal-ai');
        $response = $manager
            ->model('custom-model')
            ->prompt('Test with custom model')
            ->run();

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

        $response = app('fal-ai')
            ->model('test-model')
            ->prompt('Test fluent with container')
            ->imageSize('512x512')
            ->run();

        expect($response->status())->toBe(200)
            ->and($response->json()['request_id'])->toBe('fluent-container-789');
    });

    it('respects Laravel environment configuration', function (): void {
        // Test that the package respects Laravel's environment-based config
        config(['fal-ai.drivers.fal.default_model' => 'env-specific-model']);

        MockClient::global([
            SubmitRequest::class => MockResponse::make([
                'request_id' => 'env-test-101',
                'response_url' => 'https://queue.fal.run/env-specific-model/requests/env-test-101',
            ], 200),
        ]);

        // Need to resolve a fresh manager to pick up new config
        $this->app->forgetInstance('fal-ai');
        $manager = app('fal-ai');
        $response = $manager
            ->model('env-specific-model')
            ->prompt('Test environment config')
            ->run();

        expect($response->status())->toBe(200)
            ->and($response->json()['request_id'])->toBe('env-test-101');
    });

    it('manager returns driver instances correctly', function (): void {
        $manager = app('fal-ai');
        $driver1 = $manager->driver('fal');
        $driver2 = $manager->driver('fal');

        expect($driver1)->toBeInstanceOf(FalDriver::class)
            ->and($driver2)->toBeInstanceOf(FalDriver::class);
    });

})->group('feature', 'laravel-integration');
