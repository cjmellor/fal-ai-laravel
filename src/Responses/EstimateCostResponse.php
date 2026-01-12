<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Responses;

class EstimateCostResponse extends AbstractResponse
{
    /**
     * Get the estimate type used
     */
    public string $estimateType {
        get => $this->data['estimate_type'] ?? '';
    }

    /**
     * Get the total estimated cost
     */
    public float $totalCost {
        get => (float) ($this->data['total_cost'] ?? 0.0);
    }

    /**
     * Get the currency code (e.g., "USD")
     */
    public string $currency {
        get => $this->data['currency'] ?? 'USD';
    }
}
