<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Drivers\Replicate\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

/**
 * Get the current state of a prediction.
 *
 * Returns the prediction status and output (if completed).
 *
 * @see https://replicate.com/docs/reference/http#get-a-prediction
 */
class GetPredictionRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected readonly string $predictionId,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/v1/predictions/'.$this->predictionId;
    }
}
