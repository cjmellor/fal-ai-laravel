<?php

declare(strict_types=1);

use Cjmellor\FalAi\Drivers\Replicate\ReplicateDriver;
use Cjmellor\FalAi\Drivers\Replicate\Requests\Deployments\CreateDeploymentRequest;
use Cjmellor\FalAi\Drivers\Replicate\Requests\Deployments\UpdateDeploymentRequest;
use Cjmellor\FalAi\Drivers\Replicate\Responses\DeploymentResponse;
use Cjmellor\FalAi\Drivers\Replicate\Support\DeploymentBuilder;
use Cjmellor\FalAi\Exceptions\InvalidConfigurationException;
use Saloon\Http\Faking\MockResponse;
use Saloon\Laravel\Facades\Saloon;

covers(DeploymentBuilder::class);

beforeEach(function (): void {
    config([
        'fal-ai.drivers.replicate.api_key' => 'test-replicate-key',
        'fal-ai.drivers.replicate.base_url' => 'https://api.replicate.com',
    ]);
});

function getReplicateDriver(): ReplicateDriver
{
    return new ReplicateDriver([
        'api_key' => 'test-key',
        'base_url' => 'https://api.replicate.com',
    ]);
}

describe('DeploymentBuilder fluent interface', function (): void {

    it('allows chaining all methods', function (): void {
        $driver = getReplicateDriver();
        $builder = $driver->deployments()->create('my-deployment');

        $result = $builder
            ->model('stability-ai/sdxl')
            ->version('da77bc59')
            ->hardware('gpu-t4')
            ->instances(1, 5);

        expect($result)->toBeInstanceOf(DeploymentBuilder::class);
    });

});

describe('DeploymentBuilder create', function (): void {

    it('creates deployment with all required fields', function (): void {
        Saloon::fake([
            CreateDeploymentRequest::class => MockResponse::fixture('Replicate/Deployments/create-deployment-success'),
        ]);

        $driver = getReplicateDriver();
        $response = $driver->deployments()
            ->create('my-deployment')
            ->model('stability-ai/sdxl')
            ->version('da77bc59ee60423279fd632efb4795ab731d9e3ca9705ef3341091fb989b7eaf')
            ->hardware('gpu-t4')
            ->instances(1, 5)
            ->save();

        expect($response)->toBeInstanceOf(DeploymentResponse::class)
            ->and($response->name)->toBe('my-deployment');
    });

    it('throws exception when model is missing', function (): void {
        $driver = getReplicateDriver();
        $builder = $driver->deployments()
            ->create('my-deployment')
            ->version('da77bc59')
            ->hardware('gpu-t4')
            ->instances(1, 5);

        expect(fn () => $builder->save())
            ->toThrow(InvalidConfigurationException::class, 'Missing required deployment fields: model');
    });

    it('throws exception when version is missing', function (): void {
        $driver = getReplicateDriver();
        $builder = $driver->deployments()
            ->create('my-deployment')
            ->model('stability-ai/sdxl')
            ->hardware('gpu-t4')
            ->instances(1, 5);

        expect(fn () => $builder->save())
            ->toThrow(InvalidConfigurationException::class, 'Missing required deployment fields: version');
    });

    it('throws exception when hardware is missing', function (): void {
        $driver = getReplicateDriver();
        $builder = $driver->deployments()
            ->create('my-deployment')
            ->model('stability-ai/sdxl')
            ->version('da77bc59')
            ->instances(1, 5);

        expect(fn () => $builder->save())
            ->toThrow(InvalidConfigurationException::class, 'Missing required deployment fields: hardware');
    });

    it('throws exception when instances are missing', function (): void {
        $driver = getReplicateDriver();
        $builder = $driver->deployments()
            ->create('my-deployment')
            ->model('stability-ai/sdxl')
            ->version('da77bc59')
            ->hardware('gpu-t4');

        expect(fn () => $builder->save())
            ->toThrow(InvalidConfigurationException::class, 'instances');
    });

    it('throws exception listing all missing fields', function (): void {
        $driver = getReplicateDriver();
        $builder = $driver->deployments()->create('my-deployment');

        expect(fn () => $builder->save())
            ->toThrow(InvalidConfigurationException::class);
    });

});

describe('DeploymentBuilder update', function (): void {

    it('updates deployment with partial fields', function (): void {
        Saloon::fake([
            UpdateDeploymentRequest::class => MockResponse::fixture('Replicate/Deployments/update-deployment-success'),
        ]);

        $driver = getReplicateDriver();
        $response = $driver->deployments()
            ->update('acme', 'my-deployment')
            ->hardware('gpu-a40-small')
            ->instances(2, 10)
            ->save();

        expect($response)->toBeInstanceOf(DeploymentResponse::class)
            ->and($response->hardware())->toBe('gpu-a40-small');
    });

    it('updates deployment with only hardware', function (): void {
        Saloon::fake([
            UpdateDeploymentRequest::class => MockResponse::fixture('Replicate/Deployments/update-deployment-success'),
        ]);

        $driver = getReplicateDriver();
        $response = $driver->deployments()
            ->update('acme', 'my-deployment')
            ->hardware('gpu-a40-small')
            ->save();

        expect($response)->toBeInstanceOf(DeploymentResponse::class);
    });

    it('allows update without any fields set', function (): void {
        Saloon::fake([
            UpdateDeploymentRequest::class => MockResponse::fixture('Replicate/Deployments/update-deployment-success'),
        ]);

        $driver = getReplicateDriver();
        $response = $driver->deployments()
            ->update('acme', 'my-deployment')
            ->save();

        expect($response)->toBeInstanceOf(DeploymentResponse::class);
    });

    it('updates deployment with model', function (): void {
        Saloon::fake([
            UpdateDeploymentRequest::class => MockResponse::fixture('Replicate/Deployments/update-deployment-success'),
        ]);

        $driver = getReplicateDriver();
        $response = $driver->deployments()
            ->update('acme', 'my-deployment')
            ->model('new-owner/new-model')
            ->save();

        expect($response)->toBeInstanceOf(DeploymentResponse::class);
    });

    it('updates deployment with version', function (): void {
        Saloon::fake([
            UpdateDeploymentRequest::class => MockResponse::fixture('Replicate/Deployments/update-deployment-success'),
        ]);

        $driver = getReplicateDriver();
        $response = $driver->deployments()
            ->update('acme', 'my-deployment')
            ->version('new-version-hash-abc123')
            ->save();

        expect($response)->toBeInstanceOf(DeploymentResponse::class);
    });

});
