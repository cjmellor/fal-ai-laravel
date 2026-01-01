<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Requests\Platform;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetUsageRequest extends Request
{
    protected Method $method = Method::GET;

    /**
     * @param  array<string>  $endpointIds  Filter by specific endpoint ID(s) (1-50)
     * @param  array<string>  $expand  Data to include: 'time_series', 'summary', 'auth_method'
     * @param  string|null  $start  Start date (ISO8601, defaults to 24 hours ago)
     * @param  string|null  $end  End date (ISO8601, defaults to current time)
     * @param  string|null  $timezone  Timezone for date aggregation (default: UTC)
     * @param  string|null  $timeframe  Aggregation period: 'minute', 'hour', 'day', 'week', 'month'
     * @param  bool|null  $boundToTimeframe  Align dates to timeframe boundaries (default: true)
     * @param  int|null  $limit  Maximum number of items to return
     * @param  string|null  $cursor  Pagination cursor from previous response
     */
    public function __construct(
        protected readonly array $endpointIds = [],
        protected readonly array $expand = ['time_series'],
        protected readonly ?string $start = null,
        protected readonly ?string $end = null,
        protected readonly ?string $timezone = null,
        protected readonly ?string $timeframe = null,
        protected readonly ?bool $boundToTimeframe = null,
        protected readonly ?int $limit = null,
        protected readonly ?string $cursor = null,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/v1/models/usage';
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultQuery(): array
    {
        $query = [];

        if ($this->endpointIds !== []) {
            $query['endpoint_id'] = implode(',', $this->endpointIds);
        }

        if ($this->expand !== []) {
            $query['expand'] = implode(',', $this->expand);
        }

        if ($this->start !== null) {
            $query['start'] = $this->start;
        }

        if ($this->end !== null) {
            $query['end'] = $this->end;
        }

        if ($this->timezone !== null) {
            $query['timezone'] = $this->timezone;
        }

        if ($this->timeframe !== null) {
            $query['timeframe'] = $this->timeframe;
        }

        if ($this->boundToTimeframe !== null) {
            $query['bound_to_timeframe'] = $this->boundToTimeframe ? 'true' : 'false';
        }

        if ($this->limit !== null) {
            $query['limit'] = $this->limit;
        }

        if ($this->cursor !== null) {
            $query['cursor'] = $this->cursor;
        }

        return $query;
    }
}
