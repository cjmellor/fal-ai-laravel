<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Drivers\Replicate\Requests\Deployments;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

/**
 * Update an existing deployment.
 *
 * @see https://replicate.com/docs/reference/http#update-a-deployment
 */
class UpdateDeploymentRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::PATCH;

    /**
     * @param  string  $owner  The owner of the deployment
     * @param  string  $name  The name of the deployment
     * @param  array<string, mixed>  $updates  The fields to update
     */
    public function __construct(
        protected readonly string $owner,
        protected readonly string $name,
        protected readonly array $updates,
    ) {}

    public function resolveEndpoint(): string
    {
        return "/v1/deployments/{$this->owner}/{$this->name}";
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultBody(): array
    {
        return $this->updates;
    }
}
