<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Drivers\Replicate\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

/**
 * Cancel a running prediction.
 *
 * @see https://replicate.com/docs/reference/http#cancel-a-prediction
 */
class CancelPredictionRequest extends Request
{
    protected Method $method = Method::POST;

    public function __construct(
        protected readonly string $predictionId,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/v1/predictions/'.$this->predictionId.'/cancel';
    }
}
