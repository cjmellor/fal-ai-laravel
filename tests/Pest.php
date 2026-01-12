<?php

declare(strict_types=1);

use Cjmellor\FalAi\Drivers\Fal\FalDriver;
use Cjmellor\FalAi\Support\FluentRequest;
use Cjmellor\FalAi\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

// Helper function to create fresh FluentRequest instances using FalDriver
function createFluentRequest(): FluentRequest
{
    $driver = new FalDriver([
        'api_key' => 'test-api-key',
        'base_url' => 'https://queue.fal.run',
        'sync_url' => 'https://fal.run',
        'default_model' => 'test-model',
    ]);

    return new FluentRequest($driver, 'fal-ai/fast-sdxl');
}
