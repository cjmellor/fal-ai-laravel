<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Responses;

use Saloon\Http\Response;

class SubmitResponse
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
     * Get the request ID
     */
    public function getRequestId(): string
    {
        return $this->request_id;
    }

    /**
     * Get the response URL
     */
    public function getResponseUrl(): string
    {
        return $this->response_url;
    }

    /**
     * Get the status URL
     */
    public function getStatusUrl(): string
    {
        return $this->status_url;
    }

    /**
     * Get the cancel URL
     */
    public function getCancelUrl(): string
    {
        return $this->cancel_url;
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
