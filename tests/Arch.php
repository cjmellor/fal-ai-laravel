<?php

declare(strict_types=1);

use Cjmellor\FalAi\Exceptions\FalAiException;
use Saloon\Http\Connector;
use Saloon\Http\Request;

// Ensure debug functions are not used
arch('debug functions not used')
    ->expect(['dd', 'dump', 'var_dump'])
    ->not->toBeUsed();

// Ensure connectors extend Saloon Connector
arch('connectors extend Saloon Connector')
    ->expect('Cjmellor\FalAi\Connectors')
    ->toExtend(Connector::class);

// Ensure requests extend Saloon Request
arch('requests extend Saloon Request')
    ->expect('Cjmellor\FalAi\Requests')
    ->toExtend(Request::class);

// Ensure exceptions extend base FalAiException
arch('exceptions extend base FalAiException')
    ->expect('Cjmellor\FalAi\Exceptions')
    ->toExtend(FalAiException::class);

// Ensure FalAiException extends base Exception
arch('FalAiException extends base Exception')
    ->expect('Cjmellor\FalAi\Exceptions\FalAiException')
    ->toExtend(Exception::class);
