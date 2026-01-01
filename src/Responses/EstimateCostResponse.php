<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Responses;

use Saloon\Http\Response;

class EstimateCostResponse
{
    /**
     * Get the estimate type used
     */
    public string $estimateType {
        get => $this->data['estimate_type'] ?? '';
    }

    /**
     * Get the total estimated cost
     */
    public float $totalCost {
        get => (float) ($this->data['total_cost'] ?? 0.0);
    }

    /**
     * Get the currency code (e.g., "USD")
     */
    public string $currency {
        get => $this->data['currency'] ?? 'USD';
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
