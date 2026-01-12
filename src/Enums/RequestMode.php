<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Enums;

/**
 * Represents the execution mode for AI model requests.
 */
enum RequestMode: string
{
    /**
     * Queue mode - requests are queued and processed asynchronously.
     * Results are retrieved via polling or webhooks.
     */
    case Queue = 'queue';

    /**
     * Sync mode - requests are processed synchronously.
     * The response is returned immediately when complete.
     */
    case Sync = 'sync';

    /**
     * Stream mode - responses are streamed as they are generated.
     * Uses Server-Sent Events (SSE) for real-time output.
     */
    case Stream = 'stream';

    /**
     * Check if this mode is asynchronous (requires polling/webhooks).
     */
    public function isAsync(): bool
    {
        return $this === self::Queue;
    }

    /**
     * Check if this mode returns results immediately.
     */
    public function isImmediate(): bool
    {
        return $this === self::Sync || $this === self::Stream;
    }
}
