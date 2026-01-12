<?php

namespace Cjmellor\FalAi\Tests;

use Cjmellor\FalAi\FalAiServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName): string => 'Cjmellor\\FalAi\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            FalAiServiceProvider::class,
            \Saloon\Laravel\SaloonServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');

        // Set up default test config for the new driver structure
        config()->set('fal-ai.default', 'fal');
        config()->set('fal-ai.drivers.fal', [
            'api_key' => 'test-api-key',
            'base_url' => 'https://queue.fal.run',
            'sync_url' => 'https://fal.run',
            'platform_base_url' => 'https://api.fal.ai',
            'default_model' => 'test-model',
            'webhook' => [
                'jwks_cache_ttl' => 86400,
                'timestamp_tolerance' => 300,
                'verification_timeout' => 10,
            ],
        ]);
        config()->set('fal-ai.drivers.replicate', [
            'api_key' => 'test-replicate-key',
            'base_url' => 'https://api.replicate.com',
            'default_model' => null,
            'webhook' => [
                'verify_signatures' => true,
                'signing_secret' => 'test-secret',
            ],
        ]);
    }
}
