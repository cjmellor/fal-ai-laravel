<?php

declare(strict_types=1);

namespace Cjmellor\FalAi;

use Cjmellor\FalAi\Connectors\PlatformConnector;
use Cjmellor\FalAi\Support\AnalyticsRequest;
use Cjmellor\FalAi\Support\DeleteRequestPayloadsRequest;
use Cjmellor\FalAi\Support\EstimateCostRequest;
use Cjmellor\FalAi\Support\PricingRequest;
use Cjmellor\FalAi\Support\UsageRequest;

class Platform
{
    public protected(set) PlatformConnector $connector;

    public function __construct()
    {
        $this->connector = new PlatformConnector;
    }

    /**
     * Create a fluent request builder for fetching model pricing
     */
    public function pricing(): PricingRequest
    {
        return new PricingRequest($this);
    }

    /**
     * Create a fluent request builder for estimating costs
     */
    public function estimateCost(): EstimateCostRequest
    {
        return new EstimateCostRequest($this);
    }

    /**
     * Create a fluent request builder for fetching usage data
     */
    public function usage(): UsageRequest
    {
        return new UsageRequest($this);
    }

    /**
     * Create a fluent request builder for fetching analytics data
     */
    public function analytics(): AnalyticsRequest
    {
        return new AnalyticsRequest($this);
    }

    /**
     * Create a fluent request builder for deleting request payloads
     *
     * Deletes IO payloads and associated CDN output files for a specific request.
     * This action is irreversible.
     */
    public function deleteRequestPayloads(string $requestId): DeleteRequestPayloadsRequest
    {
        return new DeleteRequestPayloadsRequest($this, $requestId);
    }
}
