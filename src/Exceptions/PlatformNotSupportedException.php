<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Exceptions;

/**
 * Thrown when platform() is called on a driver that doesn't support Platform APIs.
 *
 * Currently only the Fal driver implements Platform APIs (pricing, usage, analytics).
 * Calling platform() on the Replicate driver will throw this exception.
 */
class PlatformNotSupportedException extends FalAiException
{
    public static function forDriver(string $driver): self
    {
        return new self(
            "Platform APIs are not supported by the '{$driver}' driver. Platform APIs are only available with the 'fal' driver."
        );
    }
}
