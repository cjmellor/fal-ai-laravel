<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Support;

use Cjmellor\FalAi\Platform;
use Cjmellor\FalAi\Requests\Platform\EstimateCostRequest as SaloonEstimateCostRequest;
use Cjmellor\FalAi\Responses\EstimateCostResponse;
use Illuminate\Support\Traits\Conditionable;
use InvalidArgumentException;

class EstimateCostRequest
{
    use Conditionable;

    private const ESTIMATE_TYPE_HISTORICAL = 'historical_api_price';

    private const ESTIMATE_TYPE_UNIT = 'unit_price';

    private Platform $platform;

    private string $estimateType = self::ESTIMATE_TYPE_HISTORICAL;

    /** @var array<string, array{call_quantity?: int, unit_quantity?: int}> */
    private array $endpoints = [];

    public function __construct(Platform $platform)
    {
        $this->platform = $platform;
    }

    /**
     * Use historical API price estimation (based on past usage patterns)
     */
    public function historicalApiPrice(): self
    {
        $this->estimateType = self::ESTIMATE_TYPE_HISTORICAL;

        return $this;
    }

    /**
     * Use unit price estimation (based on billing units like images/videos)
     */
    public function unitPrice(): self
    {
        $this->estimateType = self::ESTIMATE_TYPE_UNIT;

        return $this;
    }

    /**
     * Add an endpoint with quantity for estimation
     *
     * @param  string  $endpointId  The endpoint ID (e.g., 'fal-ai/flux/dev')
     * @param  int|null  $callQuantity  Number of API calls (for historical_api_price)
     * @param  int|null  $unitQuantity  Number of billing units (for unit_price)
     */
    public function endpoint(string $endpointId, ?int $callQuantity = null, ?int $unitQuantity = null): self
    {
        if ($callQuantity === null && $unitQuantity === null) {
            throw new InvalidArgumentException('Either callQuantity or unitQuantity must be provided');
        }

        $quantity = [];

        if ($callQuantity !== null) {
            $quantity['call_quantity'] = $callQuantity;
        }

        if ($unitQuantity !== null) {
            $quantity['unit_quantity'] = $unitQuantity;
        }

        $this->endpoints[$endpointId] = $quantity;

        return $this;
    }

    /**
     * Set multiple endpoints at once
     *
     * @param  array<string, array{call_quantity?: int, unit_quantity?: int}>  $endpoints
     */
    public function endpoints(array $endpoints): self
    {
        $this->endpoints = array_merge($this->endpoints, $endpoints);

        return $this;
    }

    /**
     * Execute the estimate cost request
     */
    public function estimate(): EstimateCostResponse
    {
        if ($this->endpoints === []) {
            throw new InvalidArgumentException('At least one endpoint must be provided');
        }

        $request = new SaloonEstimateCostRequest($this->estimateType, $this->endpoints);
        $response = $this->platform->getConnector()->send($request);

        return new EstimateCostResponse($response, $response->json());
    }

    /**
     * Get the current estimate type
     */
    public function getEstimateType(): string
    {
        return $this->estimateType;
    }

    /**
     * Get the current endpoints
     *
     * @return array<string, array{call_quantity?: int, unit_quantity?: int}>
     */
    public function getEndpoints(): array
    {
        return $this->endpoints;
    }
}
