<?php

declare(strict_types=1);

use Cjmellor\FalAi\Drivers\Replicate\Requests\Deployments\CreateDeploymentRequest;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;

covers(CreateDeploymentRequest::class);

describe('CreateDeploymentRequest', function (): void {

    it('uses POST method', function (): void {
        $request = new CreateDeploymentRequest(
            name: 'my-deployment',
            model: 'stability-ai/sdxl',
            version: 'da77bc59ee60423279fd632efb4795ab731d9e3ca9705ef3341091fb989b7eaf',
            hardware: 'gpu-t4',
            minInstances: 1,
            maxInstances: 5,
        );

        expect($request->getMethod())->toBe(Method::POST);
    });

    it('resolves endpoint to /v1/deployments', function (): void {
        $request = new CreateDeploymentRequest(
            name: 'my-deployment',
            model: 'stability-ai/sdxl',
            version: 'da77bc59',
            hardware: 'gpu-t4',
            minInstances: 1,
            maxInstances: 5,
        );

        expect($request->resolveEndpoint())->toBe('/v1/deployments');
    });

    it('returns body with all required fields', function (): void {
        $request = new CreateDeploymentRequest(
            name: 'my-deployment',
            model: 'stability-ai/sdxl',
            version: 'da77bc59ee60423279fd632efb4795ab731d9e3ca9705ef3341091fb989b7eaf',
            hardware: 'gpu-t4',
            minInstances: 1,
            maxInstances: 5,
        );

        $body = $request->defaultBody();

        expect($body)->toHaveKeys(['name', 'model', 'version', 'hardware', 'min_instances', 'max_instances'])
            ->and($body['name'])->toBe('my-deployment')
            ->and($body['model'])->toBe('stability-ai/sdxl')
            ->and($body['version'])->toBe('da77bc59ee60423279fd632efb4795ab731d9e3ca9705ef3341091fb989b7eaf')
            ->and($body['hardware'])->toBe('gpu-t4')
            ->and($body['min_instances'])->toBe(1)
            ->and($body['max_instances'])->toBe(5);
    });

    it('implements HasBody interface', function (): void {
        $request = new CreateDeploymentRequest(
            name: 'my-deployment',
            model: 'stability-ai/sdxl',
            version: 'da77bc59',
            hardware: 'gpu-t4',
            minInstances: 1,
            maxInstances: 5,
        );

        expect($request)->toBeInstanceOf(HasBody::class);
    });

});
