<?php

declare(strict_types=1);

use Cjmellor\FalAi\Contracts\DriverInterface;
use Cjmellor\FalAi\Exceptions\FalAiException;
use Illuminate\Support\Manager;
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

// Ensure Fal driver connector extends Saloon Connector
arch('Fal driver connector extends Saloon Connector')
    ->expect('Cjmellor\FalAi\Drivers\Fal\FalConnector')
    ->toExtend(Connector::class);

// Ensure Replicate driver connector extends Saloon Connector
arch('Replicate driver connector extends Saloon Connector')
    ->expect('Cjmellor\FalAi\Drivers\Replicate\ReplicateConnector')
    ->toExtend(Connector::class);

// Ensure requests extend Saloon Request
arch('requests extend Saloon Request')
    ->expect('Cjmellor\FalAi\Requests')
    ->toExtend(Request::class);

// Ensure Fal driver requests extend Saloon Request
arch('Fal driver requests extend Saloon Request')
    ->expect('Cjmellor\FalAi\Drivers\Fal\Requests')
    ->toExtend(Request::class);

// Ensure Replicate driver requests extend Saloon Request
arch('Replicate driver requests extend Saloon Request')
    ->expect('Cjmellor\FalAi\Drivers\Replicate\Requests')
    ->toExtend(Request::class);

// Ensure exceptions extend base FalAiException
arch('exceptions extend base FalAiException')
    ->expect('Cjmellor\FalAi\Exceptions')
    ->toExtend(FalAiException::class);

// Ensure FalAiException extends base Exception
arch('FalAiException extends base Exception')
    ->expect('Cjmellor\FalAi\Exceptions\FalAiException')
    ->toExtend(Exception::class);

// Ensure AIManager extends Laravel Manager
arch('AIManager extends Laravel Manager')
    ->expect('Cjmellor\FalAi\Manager\AIManager')
    ->toExtend(Manager::class);

// Ensure all drivers implement DriverInterface
arch('drivers implement DriverInterface')
    ->expect('Cjmellor\FalAi\Drivers\Fal\FalDriver')
    ->toImplement(DriverInterface::class);

arch('Replicate driver implements DriverInterface')
    ->expect('Cjmellor\FalAi\Drivers\Replicate\ReplicateDriver')
    ->toImplement(DriverInterface::class);

// Ensure Replicate driver code does not reference Fal driver code
arch('Replicate driver does not depend on Fal driver')
    ->expect('Cjmellor\FalAi\Drivers\Replicate')
    ->not->toUse('Cjmellor\FalAi\Drivers\Fal');

// Ensure Fal driver code does not reference Replicate driver code
arch('Fal driver does not depend on Replicate driver')
    ->expect('Cjmellor\FalAi\Drivers\Fal')
    ->not->toUse('Cjmellor\FalAi\Drivers\Replicate');

// Ensure Replicate deployment requests extend Saloon Request
arch('Replicate deployment requests extend Saloon Request')
    ->expect('Cjmellor\FalAi\Drivers\Replicate\Requests\Deployments')
    ->toExtend(Request::class);

// Ensure Replicate deployment responses extend AbstractResponse
arch('Replicate DeploymentResponse extends AbstractResponse')
    ->expect('Cjmellor\FalAi\Drivers\Replicate\Responses\DeploymentResponse')
    ->toExtend(Cjmellor\FalAi\Responses\AbstractResponse::class);

// Ensure support classes are final
arch('Replicate support classes are final')
    ->expect('Cjmellor\FalAi\Drivers\Replicate\Support')
    ->toBeFinal();
