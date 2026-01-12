<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Support;

use Cjmellor\FalAi\Platform;
use Cjmellor\FalAi\Requests\Platform\GetUsageRequest;
use Cjmellor\FalAi\Responses\UsageResponse;
use Illuminate\Support\Traits\Conditionable;
use InvalidArgumentException;

class UsageRequest
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
    public private(set) array $expand = ['time_series'];

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
     * Set endpoint IDs to filter usage data
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
     * Add a single endpoint ID to filter usage data
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
     * Set which data to include in the response
     *
     * @param  array<string>  $expand  Options: 'time_series', 'summary', 'auth_method'
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
     * Include summary data in the response
     */
    public function withSummary(): self
    {
        if (! in_array('summary', $this->expand, true)) {
            $this->expand[] = 'summary';
        }

        return $this;
    }

    /**
     * Include auth method breakdown in the response
     */
    public function withAuthMethod(): self
    {
        if (! in_array('auth_method', $this->expand, true)) {
            $this->expand[] = 'auth_method';
        }

        return $this;
    }

    /**
     * Set the start date for filtering usage records
     *
     * @param  string  $start  ISO8601 datetime string
     */
    public function from(string $start): self
    {
        $this->start = $start;

        return $this;
    }

    /**
     * Set the end date for filtering usage records
     *
     * @param  string  $end  ISO8601 datetime string
     */
    public function to(string $end): self
    {
        $this->end = $end;

        return $this;
    }

    /**
     * Set the date range for filtering usage records
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
     * Execute the usage request
     */
    public function get(): UsageResponse
    {
        $request = new GetUsageRequest(
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

        return new UsageResponse($response, $response->json());
    }
}
