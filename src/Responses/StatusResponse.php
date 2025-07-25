<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Responses;

use Saloon\Http\Response;

class StatusResponse
{
    public function __construct(
        private Response $response,
        private array $data
    ) {}

    /**
     * Magic property access for response data
     */
    public function __get(string $name): mixed
    {
        return $this->data[$name] ?? null;
    }

    /**
     * Check if the request is in queue
     */
    public function isInQueue(): bool
    {
        return $this->status === 'IN_QUEUE';
    }

    /**
     * Check if the request is in progress
     */
    public function isInProgress(): bool
    {
        return $this->status === 'IN_PROGRESS';
    }

    /**
     * Check if the request is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'COMPLETED';
    }

    /**
     * Get queue position if available
     */
    public function getQueuePosition(): ?int
    {
        return $this->queue_position;
    }

    /**
     * Get response URL if available
     */
    public function getResponseUrl(): ?string
    {
        return $this->response_url;
    }

    /**
     * Get logs if available
     */
    public function getLogs(): ?array
    {
        return $this->logs;
    }

    /**
     * Get metrics if available
     */
    public function getMetrics(): ?array
    {
        return $this->metrics;
    }

    /**
     * Get timings if available
     */
    public function getTimings(): ?array
    {
        return $this->timings;
    }

    // Backward compatibility methods
    public function json(): array
    {
        return $this->response->json();
    }

    public function status(): int
    {
        return $this->response->status();
    }

    public function successful(): bool
    {
        return $this->response->successful();
    }

    public function failed(): bool
    {
        return $this->response->failed();
    }

    public function getResponse(): Response
    {
        return $this->response;
    }
}
