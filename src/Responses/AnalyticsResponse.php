<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Responses;

class AnalyticsResponse extends AbstractResponse
{
    /**
     * Get the time series analytics data
     *
     * @var array<array{bucket: string, results: array<array{endpoint_id: string, request_count?: int, success_count?: int, user_error_count?: int, error_count?: int, p50_prepare_duration?: float, p75_prepare_duration?: float, p90_prepare_duration?: float, p50_duration?: float, p75_duration?: float, p90_duration?: float}>}>
     */
    public array $timeSeries {
        get => $this->data['time_series'] ?? [];
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
     * Get analytics for a specific endpoint from the time series
     *
     * @return array<array{bucket: string, endpoint_id: string, request_count?: int, success_count?: int, user_error_count?: int, error_count?: int, p50_prepare_duration?: float, p75_prepare_duration?: float, p90_prepare_duration?: float, p50_duration?: float, p75_duration?: float, p90_duration?: float}>
     */
    public function getAnalyticsFor(string $endpointId): array
    {
        $analytics = [];

        foreach ($this->timeSeries as $bucket) {
            foreach ($bucket['results'] ?? [] as $result) {
                if ($result['endpoint_id'] === $endpointId) {
                    $analytics[] = array_merge(['bucket' => $bucket['bucket']], $result);
                }
            }
        }

        return $analytics;
    }

    /**
     * Get total request count across all time series buckets
     */
    public function getTotalRequests(): int
    {
        $total = 0;

        foreach ($this->timeSeries as $bucket) {
            foreach ($bucket['results'] ?? [] as $result) {
                $total += (int) ($result['request_count'] ?? 0);
            }
        }

        return $total;
    }

    /**
     * Get total request count for a specific endpoint
     */
    public function getTotalRequestsFor(string $endpointId): int
    {
        $total = 0;

        foreach ($this->timeSeries as $bucket) {
            foreach ($bucket['results'] ?? [] as $result) {
                if ($result['endpoint_id'] === $endpointId) {
                    $total += (int) ($result['request_count'] ?? 0);
                }
            }
        }

        return $total;
    }

    /**
     * Get total success count across all time series buckets
     */
    public function getTotalSuccesses(): int
    {
        $total = 0;

        foreach ($this->timeSeries as $bucket) {
            foreach ($bucket['results'] ?? [] as $result) {
                $total += (int) ($result['success_count'] ?? 0);
            }
        }

        return $total;
    }

    /**
     * Get total success count for a specific endpoint
     */
    public function getTotalSuccessesFor(string $endpointId): int
    {
        $total = 0;

        foreach ($this->timeSeries as $bucket) {
            foreach ($bucket['results'] ?? [] as $result) {
                if ($result['endpoint_id'] === $endpointId) {
                    $total += (int) ($result['success_count'] ?? 0);
                }
            }
        }

        return $total;
    }

    /**
     * Get total error count across all time series buckets
     */
    public function getTotalErrors(): int
    {
        $total = 0;

        foreach ($this->timeSeries as $bucket) {
            foreach ($bucket['results'] ?? [] as $result) {
                $total += (int) ($result['error_count'] ?? 0);
            }
        }

        return $total;
    }

    /**
     * Get total user error count across all time series buckets
     */
    public function getTotalUserErrors(): int
    {
        $total = 0;

        foreach ($this->timeSeries as $bucket) {
            foreach ($bucket['results'] ?? [] as $result) {
                $total += (int) ($result['user_error_count'] ?? 0);
            }
        }

        return $total;
    }

    /**
     * Calculate overall success rate as a percentage
     */
    public function getSuccessRate(): float
    {
        $totalRequests = $this->getTotalRequests();

        if ($totalRequests === 0) {
            return 0.0;
        }

        return ($this->getTotalSuccesses() / $totalRequests) * 100;
    }

    /**
     * Calculate success rate for a specific endpoint
     */
    public function getSuccessRateFor(string $endpointId): float
    {
        $totalRequests = $this->getTotalRequestsFor($endpointId);

        if ($totalRequests === 0) {
            return 0.0;
        }

        return ($this->getTotalSuccessesFor($endpointId) / $totalRequests) * 100;
    }
}
