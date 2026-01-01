<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Requests\Platform;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class EstimateCostRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    /**
     * @param  string  $estimateType  Either "historical_api_price" or "unit_price"
     * @param  array<string, array{call_quantity?: int, unit_quantity?: int}>  $endpoints  Map of endpoint IDs to quantities
     */
    public function __construct(
        protected readonly string $estimateType,
        protected readonly array $endpoints,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/v1/models/pricing/estimate';
    }

    /**
     * @return array{estimate_type: string, endpoints: array<string, array<string, int>>}
     */
    public function defaultBody(): array
    {
        return [
            'estimate_type' => $this->estimateType,
            'endpoints' => $this->endpoints,
        ];
    }
}
