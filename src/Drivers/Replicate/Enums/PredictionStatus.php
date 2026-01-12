<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Drivers\Replicate\Enums;

/**
 * Represents the status of a Replicate prediction.
 */
enum PredictionStatus: string
{
    /**
     * Prediction is starting up.
     */
    case Starting = 'starting';

    /**
     * Prediction is actively processing.
     */
    case Processing = 'processing';

    /**
     * Prediction completed successfully.
     */
    case Succeeded = 'succeeded';

    /**
     * Prediction failed with an error.
     */
    case Failed = 'failed';

    /**
     * Prediction was canceled by the user.
     */
    case Canceled = 'canceled';

    /**
     * Try to create a status from a string, returning null if invalid.
     */
    public static function tryFromString(?string $status): ?self
    {
        if ($status === null) {
            return null;
        }

        return self::tryFrom($status);
    }

    /**
     * Check if this is a terminal state (no further changes expected).
     */
    public function isTerminal(): bool
    {
        return match ($this) {
            self::Succeeded, self::Failed, self::Canceled => true,
            default => false,
        };
    }

    /**
     * Check if the prediction is still running (not terminal).
     */
    public function isRunning(): bool
    {
        return ! $this->isTerminal();
    }

    /**
     * Check if this is a successful terminal state.
     */
    public function isSuccessful(): bool
    {
        return $this === self::Succeeded;
    }

    /**
     * Check if this is a failed terminal state.
     */
    public function isFailed(): bool
    {
        return $this === self::Failed;
    }

    /**
     * Check if this prediction was canceled.
     */
    public function isCanceled(): bool
    {
        return $this === self::Canceled;
    }
}
