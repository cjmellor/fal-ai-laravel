<?php

namespace Cjmellor\FalAi\Facades;

use Illuminate\Support\Facades\Facade;

class FalAi extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'fal-ai';
    }
}
