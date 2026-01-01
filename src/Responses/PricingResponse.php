<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Responses;

use Saloon\Http\Response;

class PricingResponse
{
    /**
     * Get all prices
     *
     * @return array<array{endpoint_id: string, unit_price: float, unit: string, currency: string}>
     */
    public array $prices {
        get => $this->data['prices'] ?? [];
    }

    /**
     * Get the next cursor for pagination
     */
    public ?string $nextCursor {
        get => $this->data['next_cursor'] ?? null;
    }

    /**
     * Check if there are more results
     */
    public bool $hasMore {
        get => $this->data['has_more'] ?? false;
    }

    private array $data;

    public function __construct(
        private Response $response,
        array $data
    ) {
        $this->data = $data;
    }

    /**
     * Get pricing for a specific endpoint
     *
     * @return array{endpoint_id: string, unit_price: float, unit: string, currency: string}|null
     */
    public function getPriceFor(string $endpointId): ?array
    {
        foreach ($this->prices as $price) {
            if ($price['endpoint_id'] === $endpointId) {
                return $price;
            }
        }

        return null;
    }

    /**
     * Get unit price for a specific endpoint
     */
    public function getUnitPriceFor(string $endpointId): ?float
    {
        return $this->getPriceFor($endpointId)['unit_price'] ?? null;
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
