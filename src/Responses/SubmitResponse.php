<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Responses;

use Saloon\Http\Response;

class SubmitResponse
{
    /**
     * Get the request ID
     */
    public string $requestId {
        get => $this->data['request_id'] ?? '';
    }

    /**
     * Get the response URL
     */
    public string $responseUrl {
        get => $this->data['response_url'] ?? '';
    }

    /**
     * Get the status URL
     */
    public string $statusUrl {
        get => $this->data['status_url'] ?? '';
    }

    /**
     * Get the cancel URL
     */
    public string $cancelUrl {
        get => $this->data['cancel_url'] ?? '';
    }

    private array $data;

    public function __construct(
        private Response $response,
        array $data
    ) {
        $this->data = $data;
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
