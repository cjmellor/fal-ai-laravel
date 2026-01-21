<?php

declare(strict_types=1);

use Cjmellor\FalAi\Drivers\Replicate\Requests\Deployments\UpdateDeploymentRequest;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;

covers(UpdateDeploymentRequest::class);

describe('UpdateDeploymentRequest', function (): void {

    it('uses PATCH method', function (): void {
        $request = new UpdateDeploymentRequest('acme', 'my-deployment', ['hardware' => 'gpu-a40-small']);

        expect($request->getMethod())->toBe(Method::PATCH);
    });

    it('resolves endpoint with owner and name', function (): void {
        $request = new UpdateDeploymentRequest('acme', 'my-deployment', ['hardware' => 'gpu-a40-small']);

        expect($request->resolveEndpoint())->toBe('/v1/deployments/acme/my-deployment');
    });

    it('returns body with update fields', function (): void {
        $updates = [
            'hardware' => 'gpu-a40-small',
            'min_instances' => 2,
            'max_instances' => 10,
        ];

        $request = new UpdateDeploymentRequest('acme', 'my-deployment', $updates);

        $body = $request->defaultBody();

        expect($body)->toBe($updates);
    });

    it('supports partial updates', function (): void {
        $request = new UpdateDeploymentRequest('acme', 'my-deployment', ['hardware' => 'gpu-a40-small']);

        $body = $request->defaultBody();

        expect($body)->toHaveKey('hardware')
            ->and($body)->not->toHaveKey('min_instances')
            ->and($body)->not->toHaveKey('max_instances');
    });

    it('implements HasBody interface', function (): void {
        $request = new UpdateDeploymentRequest('acme', 'my-deployment', ['hardware' => 'gpu-a40-small']);

        expect($request)->toBeInstanceOf(HasBody::class);
    });

});
