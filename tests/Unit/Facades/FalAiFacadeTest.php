<?php

declare(strict_types=1);

use Cjmellor\FalAi\Contracts\DriverInterface;
use Cjmellor\FalAi\Drivers\Fal\FalDriver;
use Cjmellor\FalAi\Drivers\Replicate\ReplicateDriver;
use Cjmellor\FalAi\Facades\FalAi;
use Cjmellor\FalAi\Manager\AIManager;
use Cjmellor\FalAi\Support\FluentRequest;
use Saloon\Http\Faking\MockClient;

covers(FalAi::class);

beforeEach(function (): void {
    MockClient::destroyGlobal();

    config([
        'fal-ai.default' => 'fal',
        'fal-ai.drivers.fal.api_key' => 'test-api-key',
        'fal-ai.drivers.fal.base_url' => 'https://queue.fal.run',
        'fal-ai.drivers.fal.sync_url' => 'https://fal.run',
        'fal-ai.drivers.fal.platform_base_url' => 'https://api.fal.ai',
        'fal-ai.drivers.fal.default_model' => 'test-model',
    ]);
});

describe('FalAi Facade', function (): void {
    it('resolves to AIManager instance', function (): void {
        expect(FalAi::getFacadeRoot())->toBeInstanceOf(AIManager::class);
    });

    it('returns correct facade accessor', function (): void {
        $method = (new ReflectionClass(FalAi::class))->getMethod('getFacadeAccessor');

        expect($method->invoke(null))->toBe('fal-ai');
    });

    it('proxies model() method to AIManager', function (): void {
        expect(FalAi::model('fal-ai/flux/schnell'))->toBeInstanceOf(FluentRequest::class);
    });

    it('proxies driver() method to AIManager', function (): void {
        expect(FalAi::driver('fal'))->toBeInstanceOf(DriverInterface::class);
    });

    it('supports method chaining through facade', function (): void {
        $request = FalAi::model('fal-ai/flux/schnell')
            ->prompt('A beautiful sunset')
            ->imageSize('1024x1024');

        expect($request)->toBeInstanceOf(FluentRequest::class)
            ->and($request->toArray())->toBe([
                'prompt' => 'A beautiful sunset',
                'image_size' => '1024x1024',
            ]);
    });

    it('can switch drivers via facade', function (): void {
        config([
            'fal-ai.drivers.replicate.api_key' => 'test-replicate-key',
            'fal-ai.drivers.replicate.default_model' => 'test-replicate-model',
        ]);

        expect(FalAi::driver('fal'))->toBeInstanceOf(FalDriver::class)
            ->and(FalAi::driver('replicate'))->toBeInstanceOf(ReplicateDriver::class);
    });
});
