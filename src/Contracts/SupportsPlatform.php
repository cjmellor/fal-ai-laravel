<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Contracts;

/**
 * Interface for drivers that support platform APIs (pricing, usage, analytics).
 *
 * Drivers that do not implement this interface will throw
 * PlatformNotSupportedException when platform() is called.
 */
interface SupportsPlatform
{
    /**
     * Access platform APIs (pricing, usage, analytics).
     */
    public function platform(): mixed;
}
