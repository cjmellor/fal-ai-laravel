<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Contracts;

interface FluentRequestInterface
{
    /**
     * Handle dynamic method calls for fluent interface
     */
    public function __call(string $method, array $arguments): self;

    /**
     * Set multiple data values at once
     *
     * @param  array  $data  Key-value pairs to set
     */
    public function with(array $data): self;

    /**
     * Set multiple data values at once (immutable)
     *
     * @param  array  $data  Key-value pairs to set
     * @return self New instance with merged data
     */
    public function withImmutable(array $data): self;

    /**
     * Get all data as an array
     */
    public function toArray(): array;

    /**
     * Get all data as JSON
     */
    public function toJson(): string;

    /**
     * Execute the request
     */
    public function run(): \Saloon\Http\Response;
}
