<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Support;

use Cjmellor\FalAi\Platform;
use Cjmellor\FalAi\Requests\Platform\GetPricingRequest;
use Cjmellor\FalAi\Responses\PricingResponse;
use Illuminate\Support\Traits\Conditionable;
use InvalidArgumentException;

class PricingRequest
{
    use Conditionable;

    /**
     * Get the current endpoint IDs
     *
     * @var array<string>
     */
    public private(set) array $endpointIds = [];

    private Platform $platform;

    public function __construct(Platform $platform)
    {
        $this->platform = $platform;
    }

    /**
     * Set endpoint IDs to get pricing for
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
     * Add a single endpoint ID to get pricing for
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
     * Execute the pricing request
     */
    public function get(): PricingResponse
    {
        $request = new GetPricingRequest($this->endpointIds);
        $response = $this->platform->connector->send($request);

        return new PricingResponse($response, $response->json());
    }
}
