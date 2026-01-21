<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Drivers\Replicate\Requests\Deployments;

use Saloon\Enums\Method;
use Saloon\Http\Request;

/**
 * Delete a deployment.
 *
 * @see https://replicate.com/docs/reference/http#delete-a-deployment
 */
class DeleteDeploymentRequest extends Request
{
    protected Method $method = Method::DELETE;

    public function __construct(
        protected readonly string $owner,
        protected readonly string $name,
    ) {}

    public function resolveEndpoint(): string
    {
        return "/v1/deployments/{$this->owner}/{$this->name}";
    }
}
