<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Support;

use Cjmellor\FalAi\Platform;
use Cjmellor\FalAi\Requests\Platform\GetAnalyticsRequest;
use Cjmellor\FalAi\Responses\AnalyticsResponse;
use Illuminate\Support\Traits\Conditionable;
use InvalidArgumentException;

class AnalyticsRequest
{
    use Conditionable;

    /**
     * Get the current endpoint IDs
     *
     * @var array<string>
     */
    public private(set) array $endpointIds = [];

    /**
     * Get the current expand options
     *
     * @var array<string>
     */
    public private(set) array $expand = ['time_series', 'request_count'];

    /**
     * Get the current timeframe
     */
    public private(set) ?string $timeframe = null;

    private ?string $start = null;

    private ?string $end = null;

    private ?string $timezone = null;

    private ?bool $boundToTimeframe = null;

    private ?int $limit = null;

    private ?string $cursor = null;

    private Platform $platform;

    public function __construct(Platform $platform)
    {
        $this->platform = $platform;
    }

    /**
     * Set endpoint IDs to get analytics for (required)
     *
     * @param  array<string>  $endpointIds  Array of endpoint IDs (1-50)
     */
    public function forEndpoints(array $endpointIds): self
    {
        if (count($endpointIds) > 50) {
            throw new InvalidArgumentException('Maximum of 50 endpoint IDs allowed');
        }

        $this->endpointIds = $endpointIds;

        return $this;
    }

    /**
     * Add a single endpoint ID to get analytics for
     */
    public function forEndpoint(string $endpointId): self
    {
        if (count($this->endpointIds) >= 50) {
            throw new InvalidArgumentException('Maximum of 50 endpoint IDs allowed');
        }

        $this->endpointIds[] = $endpointId;

        return $this;
    }

    /**
     * Set which metrics to include in the response
     *
     * @param  array<string>  $expand  Metrics: 'time_series', 'request_count', 'success_count', 'user_error_count', 'error_count', 'p50_prepare_duration', 'p75_prepare_duration', 'p90_prepare_duration', 'p50_duration', 'p75_duration', 'p90_duration'
     */
    public function expand(array $expand): self
    {
        $this->expand = $expand;

        return $this;
    }

    /**
     * Include time series data in the response (default)
     */
    public function withTimeSeries(): self
    {
        if (! in_array('time_series', $this->expand, true)) {
            $this->expand[] = 'time_series';
        }

        return $this;
    }

    /**
     * Include request count metrics
     */
    public function withRequestCount(): self
    {
        if (! in_array('request_count', $this->expand, true)) {
            $this->expand[] = 'request_count';
        }

        return $this;
    }

    /**
     * Include success count metrics
     */
    public function withSuccessCount(): self
    {
        if (! in_array('success_count', $this->expand, true)) {
            $this->expand[] = 'success_count';
        }

        return $this;
    }

    /**
     * Include user error count metrics (4xx responses)
     */
    public function withUserErrorCount(): self
    {
        if (! in_array('user_error_count', $this->expand, true)) {
            $this->expand[] = 'user_error_count';
        }

        return $this;
    }

    /**
     * Include server error count metrics (5xx responses)
     */
    public function withErrorCount(): self
    {
        if (! in_array('error_count', $this->expand, true)) {
            $this->expand[] = 'error_count';
        }

        return $this;
    }

    /**
     * Include all error metrics (user errors + server errors)
     */
    public function withAllErrors(): self
    {
        return $this->withUserErrorCount()->withErrorCount();
    }

    /**
     * Include P50 duration metrics (queue/prepare time and execution time)
     */
    public function withP50Duration(): self
    {
        if (! in_array('p50_prepare_duration', $this->expand, true)) {
            $this->expand[] = 'p50_prepare_duration';
        }

        if (! in_array('p50_duration', $this->expand, true)) {
            $this->expand[] = 'p50_duration';
        }

        return $this;
    }

    /**
     * Include P75 duration metrics (queue/prepare time and execution time)
     */
    public function withP75Duration(): self
    {
        if (! in_array('p75_prepare_duration', $this->expand, true)) {
            $this->expand[] = 'p75_prepare_duration';
        }

        if (! in_array('p75_duration', $this->expand, true)) {
            $this->expand[] = 'p75_duration';
        }

        return $this;
    }

    /**
     * Include P90 duration metrics (queue/prepare time and execution time)
     */
    public function withP90Duration(): self
    {
        if (! in_array('p90_prepare_duration', $this->expand, true)) {
            $this->expand[] = 'p90_prepare_duration';
        }

        if (! in_array('p90_duration', $this->expand, true)) {
            $this->expand[] = 'p90_duration';
        }

        return $this;
    }

    /**
     * Include all latency metrics (P50, P75, P90)
     */
    public function withAllLatencyMetrics(): self
    {
        return $this->withP50Duration()
            ->withP75Duration()
            ->withP90Duration();
    }

    /**
     * Include all available metrics
     */
    public function withAllMetrics(): self
    {
        return $this->withTimeSeries()
            ->withRequestCount()
            ->withSuccessCount()
            ->withAllErrors()
            ->withAllLatencyMetrics();
    }

    /**
     * Set the start date for filtering analytics records
     *
     * @param  string  $start  ISO8601 datetime string
     */
    public function from(string $start): self
    {
        $this->start = $start;

        return $this;
    }

    /**
     * Set the end date for filtering analytics records
     *
     * @param  string  $end  ISO8601 datetime string
     */
    public function to(string $end): self
    {
        $this->end = $end;

        return $this;
    }

    /**
     * Set the date range for filtering analytics records
     *
     * @param  string  $start  ISO8601 datetime string
     * @param  string  $end  ISO8601 datetime string
     */
    public function between(string $start, string $end): self
    {
        $this->start = $start;
        $this->end = $end;

        return $this;
    }

    /**
     * Set the timezone for date aggregation
     */
    public function timezone(string $timezone): self
    {
        $this->timezone = $timezone;

        return $this;
    }

    /**
     * Set the aggregation timeframe
     *
     * @param  string  $timeframe  One of: 'minute', 'hour', 'day', 'week', 'month'
     */
    public function timeframe(string $timeframe): self
    {
        $validTimeframes = ['minute', 'hour', 'day', 'week', 'month'];

        throw_unless(
            in_array($timeframe, $validTimeframes, true),
            InvalidArgumentException::class,
            sprintf('Invalid timeframe. Must be one of: %s', implode(', ', $validTimeframes))
        );

        $this->timeframe = $timeframe;

        return $this;
    }

    /**
     * Set whether to align dates to timeframe boundaries
     */
    public function boundToTimeframe(bool $bound = true): self
    {
        $this->boundToTimeframe = $bound;

        return $this;
    }

    /**
     * Set the maximum number of items to return
     */
    public function limit(int $limit): self
    {
        throw_if(
            $limit < 1,
            InvalidArgumentException::class,
            'Limit must be at least 1'
        );

        $this->limit = $limit;

        return $this;
    }

    /**
     * Set the pagination cursor
     */
    public function cursor(string $cursor): self
    {
        $this->cursor = $cursor;

        return $this;
    }

    /**
     * Execute the analytics request
     */
    public function get(): AnalyticsResponse
    {
        throw_if(
            $this->endpointIds === [],
            InvalidArgumentException::class,
            'At least one endpoint ID is required for analytics'
        );

        $request = new GetAnalyticsRequest(
            endpointIds: $this->endpointIds,
            expand: $this->expand,
            start: $this->start,
            end: $this->end,
            timezone: $this->timezone,
            timeframe: $this->timeframe,
            boundToTimeframe: $this->boundToTimeframe,
            limit: $this->limit,
            cursor: $this->cursor,
        );

        $response = $this->platform->connector->send($request);

        return new AnalyticsResponse($response, $response->json());
    }
}
