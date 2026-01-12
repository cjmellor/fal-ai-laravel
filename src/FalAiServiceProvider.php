<?php

declare(strict_types=1);

namespace Cjmellor\FalAi;

use Cjmellor\FalAi\Manager\AIManager;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class FalAiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/fal-ai.php',
            'fal-ai'
        );

        $this->app->singleton('fal-ai', function (Application $app): AIManager {
            return new AIManager($app);
        });

        $this->app->alias('fal-ai', AIManager::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/webhooks.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/fal-ai.php' => config_path('fal-ai.php'),
            ], 'fal-ai-config');
        }
    }
}
