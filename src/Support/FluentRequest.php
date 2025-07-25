<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Support;

use Cjmellor\FalAi\Contracts\FluentRequestInterface;
use Cjmellor\FalAi\FalAi;
use Cjmellor\FalAi\Responses\SubmitResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Conditionable;

class FluentRequest implements FluentRequestInterface
{
    use Conditionable;

    private array $data = [];

    private ?string $modelId;

    private FalAi $falAi;

    private ?string $baseUrlOverride = null;

    public function __construct(FalAi $falAi, ?string $modelId)
    {
        $this->falAi = $falAi;
        $this->modelId = $modelId;
    }

    /**
     * Handle dynamic method calls for fluent interface
     */
    public function __call(string $method, array $arguments): self
    {
        // Check if this is an immutable call (ends with 'Immutable')
        if (str_ends_with($method, 'Immutable')) {
            $actualMethod = mb_substr($method, 0, -9); // Remove 'Immutable' suffix

            return $this->__callImmutable($actualMethod, $arguments);
        }

        // Convert camelCase method names to snake_case for API compatibility
        $key = Str::snake($method);

        // Set the value (first argument)
        $this->data[$key] = $arguments[0] ?? null;

        return $this;
    }

    /**
     * Handle dynamic method calls for immutable fluent interface
     *
     * @param  string  $method  The method name (will be converted to snake_case)
     * @param  array  $arguments  The method arguments (first argument becomes the value)
     * @return self New instance with the data set
     *
     * @example $newRequest = $request->withPromptImmutable('Hello world')
     * @example $newRequest = $request->withImageSizeImmutable(512)
     */
    public function __callImmutable(string $method, array $arguments): self
    {
        $clone = clone $this;
        $key = Str::snake($method);
        $clone->data[$key] = $arguments[0] ?? null;

        return $clone;
    }

    /**
     * Set the request to use the queue endpoint explicitly
     */
    public function queue(): self
    {
        $this->baseUrlOverride = 'https://queue.fal.run';

        return $this;
    }

    /**
     * Set the request to use the sync endpoint
     */
    public function sync(): self
    {
        $this->baseUrlOverride = 'https://fal.run';

        return $this;
    }

    /**
     * Execute the request
     */
    public function run(): SubmitResponse
    {
        return $this->falAi->runWithBaseUrl($this->data, $this->modelId, $this->baseUrlOverride);
    }

    /**
     * Get the current data array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Get all data as an array
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Get all data as JSON
     */
    public function toJson(): string
    {
        return json_encode($this->data);
    }

    /**
     * Set multiple data values at once
     */
    public function with(array $data): self
    {
        $this->data = array_merge($this->data, $data);

        return $this;
    }

    /**
     * Set multiple data values at once (immutable - returns new instance)
     */
    public function withImmutable(array $data): self
    {
        $clone = clone $this;
        $clone->data = array_merge($this->data, $data);

        return $clone;
    }

    /**
     * Get the current base URL override
     */
    public function getBaseUrlOverride(): ?string
    {
        return $this->baseUrlOverride;
    }
}
