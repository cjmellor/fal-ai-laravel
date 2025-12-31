<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Requests\Platform;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetPricingRequest extends Request
{
    protected Method $method = Method::GET;

    /**
     * @param  array<string>  $endpointIds  Array of endpoint IDs to get pricing for (1-50)
     */
    public function __construct(
        protected readonly array $endpointIds = [],
    ) {}

    public function resolveEndpoint(): string
    {
        return '/v1/models/pricing';
    }

    /**
     * @return array<string, string>
     */
    protected function defaultQuery(): array
    {
        if ($this->endpointIds === []) {
            return [];
        }

        return [
            'endpoint_id' => implode(',', $this->endpointIds),
        ];
    }
}
