<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Responses;

class StatusResponse extends AbstractResponse
{
    /**
     * Get the request status
     */
    public string $requestStatus {
        get => $this->data['status'] ?? '';
    }

    /**
     * Get queue position if available
     */
    public ?int $queuePosition {
        get => $this->data['queue_position'] ?? null;
    }

    /**
     * Get response URL if available
     */
    public ?string $responseUrl {
        get => $this->data['response_url'] ?? null;
    }

    /**
     * Get logs if available
     */
    public ?array $logs {
        get => $this->data['logs'] ?? null;
    }

    /**
     * Get metrics if available
     */
    public ?array $metrics {
        get => $this->data['metrics'] ?? null;
    }

    /**
     * Get timings if available
     */
    public ?array $timings {
        get => $this->data['timings'] ?? null;
    }

    /**
     * Check if the request is in queue
     */
    public function isInQueue(): bool
    {
        return $this->requestStatus === 'IN_QUEUE';
    }

    /**
     * Check if the request is in progress
     */
    public function isInProgress(): bool
    {
        return $this->requestStatus === 'IN_PROGRESS';
    }

    /**
     * Check if the request is completed
     */
    public function isCompleted(): bool
    {
        return $this->requestStatus === 'COMPLETED';
    }
}
