<?php

declare(strict_types=1);

use Cjmellor\FalAi\Drivers\Replicate\Requests\Deployments\DeleteDeploymentRequest;
use Saloon\Enums\Method;

covers(DeleteDeploymentRequest::class);

describe('DeleteDeploymentRequest', function (): void {

    it('uses DELETE method', function (): void {
        $request = new DeleteDeploymentRequest('acme', 'my-deployment');

        expect($request->getMethod())->toBe(Method::DELETE);
    });

    it('resolves endpoint with owner and name', function (): void {
        $request = new DeleteDeploymentRequest('acme', 'my-deployment');

        expect($request->resolveEndpoint())->toBe('/v1/deployments/acme/my-deployment');
    });

    it('handles various owner and name formats', function (string $owner, string $name, string $expectedEndpoint): void {
        $request = new DeleteDeploymentRequest($owner, $name);

        expect($request->resolveEndpoint())->toBe($expectedEndpoint);
    })->with([
        'simple names' => ['acme', 'test', '/v1/deployments/acme/test'],
        'hyphenated names' => ['my-org', 'my-deployment', '/v1/deployments/my-org/my-deployment'],
    ]);

});
