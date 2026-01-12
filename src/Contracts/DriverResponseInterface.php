<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Contracts;

/**
 * Common interface for all driver response types.
 *
 * This interface enables typed return values from driver methods
 * while allowing driver-specific response implementations.
 */
interface DriverResponseInterface
{
    /**
     * Get the raw JSON response data.
     *
     * @return array<string, mixed>
     */
    public function json(): array;

    /**
     * Get the HTTP status code.
     */
    public function status(): int;

    /**
     * Check if the request was successful (2xx status).
     */
    public function successful(): bool;

    /**
     * Check if the request failed (non-2xx status).
     */
    public function failed(): bool;
}
