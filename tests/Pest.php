<?php

declare(strict_types=1);

use Cjmellor\FalAi\FalAi;
use Cjmellor\FalAi\Support\FluentRequest;
use Cjmellor\FalAi\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

// Helper function to create fresh FluentRequest instances
function createFluentRequest(): FluentRequest
{
    return new FluentRequest(new FalAi(), 'fal-ai/fast-sdxl');
}
