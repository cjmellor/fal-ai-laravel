<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Drivers\Replicate\Requests\Deployments;

use Saloon\Enums\Method;
use Saloon\Http\Request;

/**
 * List all deployments for the authenticated user.
 *
 * @see https://replicate.com/docs/reference/http#list-deployments
 */
class ListDeploymentsRequest extends Request
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/v1/deployments';
    }
}
