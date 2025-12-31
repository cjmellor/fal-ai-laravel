<?php

declare(strict_types=1);

namespace Cjmellor\FalAi;

use Cjmellor\FalAi\Connectors\PlatformConnector;
use Cjmellor\FalAi\Support\EstimateCostRequest;
use Cjmellor\FalAi\Support\PricingRequest;

class Platform
{
    protected PlatformConnector $connector;

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
     * Get the platform connector instance
     */
    public function getConnector(): PlatformConnector
    {
        return $this->connector;
    }
}
