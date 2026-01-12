<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Responses;

use Cjmellor\FalAi\Contracts\DriverResponseInterface;
use Saloon\Http\Response;

/**
 * Base class for response wrappers that provide common Saloon response accessors.
 */
abstract class AbstractResponse implements DriverResponseInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        protected Response $response,
        /** @var array<string, mixed> */
        protected array $data,
    ) {}

    /**
     * Get the raw JSON response
     *
     * @return array<string, mixed>
     */
    public function json(): array
    {
        return $this->response->json();
    }

    /**
     * Get the HTTP status code
     */
    public function status(): int
    {
        return $this->response->status();
    }

    /**
     * Check if the request was successful
     */
    public function successful(): bool
    {
        return $this->response->successful();
    }

    /**
     * Check if the request failed
     */
    public function failed(): bool
    {
        return $this->response->failed();
    }

    /**
     * Get the underlying Saloon response
     */
    public function getResponse(): Response
    {
        return $this->response;
    }
}
