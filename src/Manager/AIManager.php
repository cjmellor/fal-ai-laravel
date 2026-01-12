<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Manager;

use Cjmellor\FalAi\Contracts\DriverInterface;
use Cjmellor\FalAi\Contracts\SupportsPlatform;
use Cjmellor\FalAi\Drivers\Fal\FalDriver;
use Cjmellor\FalAi\Drivers\Replicate\ReplicateDriver;
use Cjmellor\FalAi\Exceptions\PlatformNotSupportedException;
use Cjmellor\FalAi\Support\FluentRequest;
use Illuminate\Support\Manager;

/**
 * AI provider manager using Laravel's Manager pattern.
 *
 * Provides a unified interface to multiple AI providers (Fal, Replicate).
 * Supports driver-based switching via config or explicit driver() calls.
 *
 * @method mixed run(FluentRequest $request)
 * @method mixed status(string $requestId, ?string $model = null)
 * @method mixed result(string $requestId, ?string $model = null)
 * @method bool cancel(string $requestId, ?string $model = null)
 * @method mixed stream(FluentRequest $request)
 * @method FluentRequest model(string $model)
 */
class AIManager extends Manager
{
    /**
     * Dynamically call the default driver instance.
     *
     * @param  string  $method
     * @param  array<int, mixed>  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->driver()->$method(...$parameters);
    }

    /**
     * Get the default driver name.
     */
    public function getDefaultDriver(): string
    {
        return $this->config->get('fal-ai.default', 'fal');
    }

    /**
     * Get a driver instance by name.
     *
     * @param  string|null  $driver
     */
    public function driver($driver = null): DriverInterface
    {
        return parent::driver($driver);
    }

    /**
     * Access platform APIs (pricing, usage, analytics).
     *
     * Only available on drivers that implement SupportsPlatform.
     * Currently only the Fal driver supports Platform APIs.
     *
     * @throws PlatformNotSupportedException
     */
    public function platform(): mixed
    {
        $driver = $this->driver();

        throw_unless(
            $driver instanceof SupportsPlatform,
            PlatformNotSupportedException::forDriver($driver->getName())
        );

        return $driver->platform();
    }

    /**
     * Create the Fal driver instance.
     */
    protected function createFalDriver(): FalDriver
    {
        $config = $this->config->get('fal-ai.drivers.fal', []);

        return new FalDriver($config);
    }

    /**
     * Create the Replicate driver instance.
     */
    protected function createReplicateDriver(): ReplicateDriver
    {
        $config = $this->config->get('fal-ai.drivers.replicate', []);

        return new ReplicateDriver($config);
    }
}
