<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Facades;

use Cjmellor\FalAi\Contracts\DriverInterface;
use Cjmellor\FalAi\Manager\AIManager;
use Cjmellor\FalAi\Support\FluentRequest;
use Illuminate\Support\Facades\Facade;

/**
 * Facade for the AI Manager.
 *
 * @method static DriverInterface driver(?string $driver = null)
 * @method static FluentRequest model(string $model)
 * @method static mixed run(FluentRequest $request)
 * @method static mixed status(string $requestId, ?string $model = null)
 * @method static mixed result(string $requestId, ?string $model = null)
 * @method static bool cancel(string $requestId, ?string $model = null)
 * @method static mixed stream(FluentRequest $request)
 * @method static mixed platform()
 *
 * @see AIManager
 */
class FalAi extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'fal-ai';
    }
}
