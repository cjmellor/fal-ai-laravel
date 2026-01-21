<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Drivers\Replicate\Requests\Deployments;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

/**
 * Create a new deployment.
 *
 * @see https://replicate.com/docs/reference/http#create-a-deployment
 */
class CreateDeploymentRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    /**
     * @param  string  $name  The name of the deployment
     * @param  string  $model  The model identifier in "owner/name" format
     * @param  string  $version  The model version ID (64 char hex)
     * @param  string  $hardware  The hardware SKU (e.g., "gpu-t4", "gpu-a40-small")
     * @param  int  $minInstances  Minimum number of instances
     * @param  int  $maxInstances  Maximum number of instances
     */
    public function __construct(
        protected readonly string $name,
        protected readonly string $model,
        protected readonly string $version,
        protected readonly string $hardware,
        protected readonly int $minInstances,
        protected readonly int $maxInstances,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/v1/deployments';
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultBody(): array
    {
        return [
            'name' => $this->name,
            'model' => $this->model,
            'version' => $this->version,
            'hardware' => $this->hardware,
            'min_instances' => $this->minInstances,
            'max_instances' => $this->maxInstances,
        ];
    }
}
