<?php

declare(strict_types=1);

use Cjmellor\FalAi\Drivers\Replicate\Requests\Deployments\ListDeploymentsRequest;
use Saloon\Enums\Method;

covers(ListDeploymentsRequest::class);

describe('ListDeploymentsRequest', function (): void {

    it('uses GET method', function (): void {
        $request = new ListDeploymentsRequest;

        expect($request->getMethod())->toBe(Method::GET);
    });

    it('resolves endpoint to /v1/deployments', function (): void {
        $request = new ListDeploymentsRequest;

        expect($request->resolveEndpoint())->toBe('/v1/deployments');
    });

});
