<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Responses;

use Saloon\Http\Response;

class StatusResponse
{
    /**
     * Get the request status
     */
    public string $status {
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

    private array $data;

    public function __construct(
        private Response $response,
        array $data
    ) {
        $this->data = $data;
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
     * Get the raw JSON response
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
