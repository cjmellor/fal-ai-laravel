<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Responses;

use Saloon\Http\Response;

class UsageResponse
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

    private array $data;

    public function __construct(
        private Response $response,
        array $data
    ) {
        $this->data = $data;
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
