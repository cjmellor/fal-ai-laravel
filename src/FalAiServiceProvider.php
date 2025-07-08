<?php

namespace Cjmellor\FalAi;

use Illuminate\Support\ServiceProvider;

class FalAiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/fal-ai.php',
            'fal-ai'
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/fal-ai.php' => config_path('fal-ai.php'),
            ], 'fal-ai-config');
        }
    }
}
