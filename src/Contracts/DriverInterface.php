<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Contracts;

use Cjmellor\FalAi\Support\FluentRequest;

interface DriverInterface
{
    /**
     * Execute a model and return the result/status.
     */
    public function run(FluentRequest $request): mixed;

    /**
     * Check the status of a running request.
     */
    public function status(string $requestId, ?string $model = null): mixed;

    /**
     * Get the result of a completed request.
     */
    public function result(string $requestId, ?string $model = null): mixed;

    /**
     * Cancel a running request.
     */
    public function cancel(string $requestId, ?string $model = null): bool;

    /**
     * Stream model execution.
     */
    public function stream(FluentRequest $request): mixed;

    /**
     * Create a fluent request builder for this driver.
     */
    public function model(string $modelId): FluentRequest;

    /**
     * Get the driver name.
     */
    public function getName(): string;
}
