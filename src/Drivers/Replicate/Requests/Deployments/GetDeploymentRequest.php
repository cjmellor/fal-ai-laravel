<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Drivers\Replicate\Requests\Deployments;

use Saloon\Enums\Method;
use Saloon\Http\Request;

/**
 * Get a specific deployment by owner and name.
 *
 * @see https://replicate.com/docs/reference/http#get-a-deployment
 */
class GetDeploymentRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected readonly string $owner,
        protected readonly string $name,
    ) {}

    public function resolveEndpoint(): string
    {
        return "/v1/deployments/{$this->owner}/{$this->name}";
    }
}
