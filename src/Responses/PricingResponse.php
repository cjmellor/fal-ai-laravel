<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Responses;

class PricingResponse extends AbstractResponse
{
    /**
     * Get all prices
     *
     * @var array<array{endpoint_id: string, unit_price: float, unit: string, currency: string}>
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
}
