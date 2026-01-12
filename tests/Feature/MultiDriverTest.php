<?php

declare(strict_types=1);

use Cjmellor\FalAi\Contracts\DriverInterface;
use Cjmellor\FalAi\Drivers\Fal\FalDriver;
use Cjmellor\FalAi\Drivers\Replicate\ReplicateDriver;
use Cjmellor\FalAi\Manager\AIManager;

beforeEach(function (): void {
    config([
        'fal-ai.default' => 'fal',
        'fal-ai.drivers.fal.api_key' => 'test-fal-key',
        'fal-ai.drivers.fal.base_url' => 'https://queue.fal.run',
        'fal-ai.drivers.fal.sync_url' => 'https://fal.run',
        'fal-ai.drivers.fal.platform_base_url' => 'https://api.fal.ai',
        'fal-ai.drivers.fal.default_model' => 'fal-ai/flux/schnell',
        'fal-ai.drivers.replicate.api_key' => 'test-replicate-key',
        'fal-ai.drivers.replicate.base_url' => 'https://api.replicate.com',
        'fal-ai.drivers.replicate.default_model' => 'stability-ai/sdxl',
    ]);

    // Forget existing manager instance to pick up fresh config
    app()->forgetInstance('fal-ai');
});

covers(AIManager::class);

describe('AIManager Driver Resolution', function (): void {

    it('resolves AIManager from service container', function (): void {
        $manager = app('fal-ai');

        expect($manager)->toBeInstanceOf(AIManager::class);
    });

    it('returns default driver from config', function (): void {
        $manager = app('fal-ai');

        expect($manager->getDefaultDriver())->toBe('fal');
    });

    it('resolves Fal driver by default', function (): void {
        $manager = app('fal-ai');
        $driver = $manager->driver();

        expect($driver)->toBeInstanceOf(FalDriver::class)
            ->and($driver)->toBeInstanceOf(DriverInterface::class);
    });

    it('resolves Fal driver explicitly', function (): void {
        $manager = app('fal-ai');
        $driver = $manager->driver('fal');

        expect($driver)->toBeInstanceOf(FalDriver::class)
            ->and($driver->getName())->toBe('fal');
    });

    it('resolves Replicate driver explicitly', function (): void {
        $manager = app('fal-ai');
        $driver = $manager->driver('replicate');

        expect($driver)->toBeInstanceOf(ReplicateDriver::class)
            ->and($driver->getName())->toBe('replicate');
    });

    it('can switch default driver via config', function (): void {
        config(['fal-ai.default' => 'replicate']);
        app()->forgetInstance('fal-ai');

        $manager = app('fal-ai');

        expect($manager->getDefaultDriver())->toBe('replicate');

        $driver = $manager->driver();
        expect($driver)->toBeInstanceOf(ReplicateDriver::class);
    });

    it('throws exception for unsupported driver', function (): void {
        $manager = app('fal-ai');

        expect(fn () => $manager->driver('unsupported'))
            ->toThrow(InvalidArgumentException::class);
    });

});

describe('Multi-Driver Usage Patterns', function (): void {

    it('can use both drivers in same request', function (): void {
        $manager = app('fal-ai');

        $falDriver = $manager->driver('fal');
        $replicateDriver = $manager->driver('replicate');

        expect($falDriver)->toBeInstanceOf(FalDriver::class)
            ->and($replicateDriver)->toBeInstanceOf(ReplicateDriver::class)
            ->and($falDriver->getName())->not->toBe($replicateDriver->getName());
    });

    it('maintains separate driver instances', function (): void {
        $manager = app('fal-ai');

        $driver1 = $manager->driver('fal');
        $driver2 = $manager->driver('replicate');

        expect($driver1)->not->toBe($driver2);
    });

    it('can create fluent requests from both drivers', function (): void {
        $manager = app('fal-ai');

        $falRequest = $manager->driver('fal')->model('fal-ai/flux/schnell');
        $replicateRequest = $manager->driver('replicate')->model('stability-ai/sdxl:abc123');

        expect($falRequest->getModel())->toBe('fal-ai/flux/schnell')
            ->and($replicateRequest->getModel())->toBe('stability-ai/sdxl:abc123');
    });

});

describe('Driver Feature Differences', function (): void {

    it('Fal driver supports platform APIs', function (): void {
        $manager = app('fal-ai');
        $driver = $manager->driver('fal');

        $platform = $driver->platform();

        expect($platform)->toBeInstanceOf(Cjmellor\FalAi\Platform::class);
    });

    it('Replicate driver throws on platform APIs', function (): void {
        $manager = app('fal-ai');
        $driver = $manager->driver('replicate');

        expect(fn () => $driver->platform())
            ->toThrow(Cjmellor\FalAi\Exceptions\PlatformNotSupportedException::class);
    });

});

describe('AIManager Platform API', function (): void {

    it('throws PlatformNotSupportedException when calling platform() via manager with replicate as default', function (): void {
        config(['fal-ai.default' => 'replicate']);
        app()->forgetInstance('fal-ai');

        $manager = app('fal-ai');

        expect(fn () => $manager->platform())
            ->toThrow(Cjmellor\FalAi\Exceptions\PlatformNotSupportedException::class);
    });

    it('includes driver name in exception message when calling platform() on non-supporting driver', function (): void {
        config(['fal-ai.default' => 'replicate']);
        app()->forgetInstance('fal-ai');

        $manager = app('fal-ai');

        try {
            $manager->platform();
            $this->fail('Expected PlatformNotSupportedException was not thrown');
        } catch (Cjmellor\FalAi\Exceptions\PlatformNotSupportedException $e) {
            expect($e->getMessage())->toContain('replicate');
        }
    });

    it('successfully calls platform() when fal is default driver', function (): void {
        $manager = app('fal-ai');

        $platform = $manager->platform();

        expect($platform)->toBeInstanceOf(Cjmellor\FalAi\Platform::class);
    });

});

describe('Facade with Multi-Driver', function (): void {

    it('facade proxies to manager', function (): void {
        $facade = Cjmellor\FalAi\Facades\FalAi::getFacadeRoot();

        expect($facade)->toBeInstanceOf(AIManager::class);
    });

    it('facade can access drivers', function (): void {
        $driver = Cjmellor\FalAi\Facades\FalAi::driver('fal');

        expect($driver)->toBeInstanceOf(FalDriver::class);
    });

    it('facade can access replicate driver', function (): void {
        $driver = Cjmellor\FalAi\Facades\FalAi::driver('replicate');

        expect($driver)->toBeInstanceOf(ReplicateDriver::class);
    });

});

describe('AIManager __call Magic Method', function (): void {

    it('proxies model() call to default driver via __call', function (): void {
        $manager = app('fal-ai');
        $request = $manager->model('test-model');

        expect($request)->toBeInstanceOf(Cjmellor\FalAi\Support\FluentRequest::class)
            ->and($request->getModel())->toBe('test-model');
    });

    it('facade proxies model() call to default driver', function (): void {
        $request = Cjmellor\FalAi\Facades\FalAi::model('fal-ai/flux/schnell');

        expect($request)->toBeInstanceOf(Cjmellor\FalAi\Support\FluentRequest::class)
            ->and($request->getModel())->toBe('fal-ai/flux/schnell');
    });

    it('uses correct default driver when config changes', function (): void {
        config(['fal-ai.default' => 'replicate']);
        app()->forgetInstance('fal-ai');

        $request = Cjmellor\FalAi\Facades\FalAi::model('stability-ai/sdxl:abc123');

        expect($request->getModel())->toBe('stability-ai/sdxl:abc123');
    });

});
