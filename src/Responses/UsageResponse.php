<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Responses;

class UsageResponse extends AbstractResponse
{
    /**
     * Get the time series usage data
     *
     * @return array<array{bucket: string, results: array<array{endpoint_id: string, unit: string, quantity: int, unit_price: float, cost: float, currency: string, auth_method?: string}>}>
     */
    public array $timeSeries {
        get => $this->data['time_series'] ?? [];
    }

    /**
     * Get the summary data (if requested via expand)
     *
     * @return array<string, mixed>|null
     */
    public ?array $summary {
        get => $this->data['summary'] ?? null;
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
     * Get usage for a specific endpoint from the time series
     *
     * @return array<array{bucket: string, endpoint_id: string, unit: string, quantity: int, unit_price: float, cost: float, currency: string, auth_method?: string}>
     */
    public function getUsageFor(string $endpointId): array
    {
        $usage = [];

        foreach ($this->timeSeries as $bucket) {
            foreach ($bucket['results'] ?? [] as $result) {
                if ($result['endpoint_id'] === $endpointId) {
                    $usage[] = array_merge(['bucket' => $bucket['bucket']], $result);
                }
            }
        }

        return $usage;
    }

    /**
     * Get total cost across all time series buckets
     */
    public function getTotalCost(): float
    {
        $total = 0.0;

        foreach ($this->timeSeries as $bucket) {
            foreach ($bucket['results'] ?? [] as $result) {
                $total += (float) ($result['cost'] ?? 0.0);
            }
        }

        return $total;
    }

    /**
     * Get total cost for a specific endpoint
     */
    public function getTotalCostFor(string $endpointId): float
    {
        $total = 0.0;

        foreach ($this->timeSeries as $bucket) {
            foreach ($bucket['results'] ?? [] as $result) {
                if ($result['endpoint_id'] === $endpointId) {
                    $total += (float) ($result['cost'] ?? 0.0);
                }
            }
        }

        return $total;
    }

    /**
     * Get total quantity across all time series buckets
     */
    public function getTotalQuantity(): int
    {
        $total = 0;

        foreach ($this->timeSeries as $bucket) {
            foreach ($bucket['results'] ?? [] as $result) {
                $total += (int) ($result['quantity'] ?? 0);
            }
        }

        return $total;
    }

    /**
     * Get total quantity for a specific endpoint
     */
    public function getTotalQuantityFor(string $endpointId): int
    {
        $total = 0;

        foreach ($this->timeSeries as $bucket) {
            foreach ($bucket['results'] ?? [] as $result) {
                if ($result['endpoint_id'] === $endpointId) {
                    $total += (int) ($result['quantity'] ?? 0);
                }
            }
        }

        return $total;
    }
}
