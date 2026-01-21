<?php

declare(strict_types=1);

use Cjmellor\FalAi\Drivers\Replicate\Requests\Deployments\GetDeploymentRequest;
use Saloon\Enums\Method;

covers(GetDeploymentRequest::class);

describe('GetDeploymentRequest', function (): void {

    it('uses GET method', function (): void {
        $request = new GetDeploymentRequest('acme', 'my-deployment');

        expect($request->getMethod())->toBe(Method::GET);
    });

    it('resolves endpoint with owner and name', function (): void {
        $request = new GetDeploymentRequest('acme', 'my-deployment');

        expect($request->resolveEndpoint())->toBe('/v1/deployments/acme/my-deployment');
    });

    it('handles various owner and name formats', function (string $owner, string $name, string $expectedEndpoint): void {
        $request = new GetDeploymentRequest($owner, $name);

        expect($request->resolveEndpoint())->toBe($expectedEndpoint);
    })->with([
        'simple names' => ['acme', 'test', '/v1/deployments/acme/test'],
        'hyphenated names' => ['my-org', 'my-deployment', '/v1/deployments/my-org/my-deployment'],
        'underscored names' => ['org_name', 'deploy_name', '/v1/deployments/org_name/deploy_name'],
    ]);

});
