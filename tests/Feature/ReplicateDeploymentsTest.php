<?php

declare(strict_types=1);

use Cjmellor\FalAi\Drivers\Replicate\ReplicateDriver;
use Cjmellor\FalAi\Drivers\Replicate\Requests\Deployments\CreateDeploymentPredictionRequest;
use Cjmellor\FalAi\Drivers\Replicate\Requests\Deployments\CreateDeploymentRequest;
use Cjmellor\FalAi\Drivers\Replicate\Requests\Deployments\DeleteDeploymentRequest;
use Cjmellor\FalAi\Drivers\Replicate\Requests\Deployments\GetDeploymentRequest;
use Cjmellor\FalAi\Drivers\Replicate\Requests\Deployments\ListDeploymentsRequest;
use Cjmellor\FalAi\Drivers\Replicate\Requests\Deployments\UpdateDeploymentRequest;
use Cjmellor\FalAi\Drivers\Replicate\Responses\DeploymentResponse;
use Cjmellor\FalAi\Drivers\Replicate\Responses\DeploymentsCollection;
use Cjmellor\FalAi\Drivers\Replicate\Responses\PredictionResponse;
use Cjmellor\FalAi\Drivers\Replicate\Support\DeploymentsManager;
use Cjmellor\FalAi\Facades\FalAi;
use Saloon\Http\Faking\MockResponse;
use Saloon\Laravel\Facades\Saloon;

beforeEach(function (): void {
    config([
        'fal-ai.default' => 'replicate',
        'fal-ai.drivers.replicate.api_key' => 'test-replicate-key',
        'fal-ai.drivers.replicate.base_url' => 'https://api.replicate.com',
    ]);

    app()->forgetInstance('fal-ai');
});

function replicateDriver(): ReplicateDriver
{
    $driver = FalAi::driver('replicate');
    assert($driver instanceof ReplicateDriver);

    return $driver;
}

describe('Replicate Deployments API via Facade', function (): void {

    it('accesses deployments manager via facade', function (): void {
        $manager = replicateDriver()->deployments();

        expect($manager)->toBeInstanceOf(DeploymentsManager::class);
    });

    it('lists deployments via facade', function (): void {
        Saloon::fake([
            ListDeploymentsRequest::class => MockResponse::fixture('Replicate/Deployments/list-deployments-success'),
        ]);

        $collection = replicateDriver()->deployments()->list();

        expect($collection)
            ->toBeInstanceOf(DeploymentsCollection::class)
            ->and($collection)->count()->toBe(2)
            ->and($collection->results())->sequence(
                fn ($item) => $item->name->toBe('deployment-one'),
                fn ($item) => $item->name->toBe('deployment-two'),
            );
    });

    it('gets a deployment via facade', function (): void {
        Saloon::fake([
            GetDeploymentRequest::class => MockResponse::fixture('Replicate/Deployments/get-deployment-success'),
        ]);

        $response = replicateDriver()->deployments()->get('acme', 'my-deployment');

        expect($response)->toBeInstanceOf(DeploymentResponse::class)
            ->and($response)->owner->toBe('acme')
            ->and($response)->name->toBe('my-deployment')
            ->and($response)->hardware()->toBe('gpu-t4')
            ->and($response)->minInstances()->toBe(1)
            ->and($response)->maxInstances()->toBe(5);
    });

    it('creates a deployment via facade', function (): void {
        Saloon::fake([
            CreateDeploymentRequest::class => MockResponse::fixture('Replicate/Deployments/create-deployment-success'),
        ]);

        $response = replicateDriver()
            ->deployments()
            ->create('my-deployment')
            ->model('stability-ai/sdxl')
            ->version('da77bc59ee60423279fd632efb4795ab731d9e3ca9705ef3341091fb989b7eaf')
            ->hardware('gpu-t4')
            ->instances(1, 5)
            ->save();

        expect($response)->toBeInstanceOf(DeploymentResponse::class)
            ->and($response)->name->toBe('my-deployment');
    });

    it('updates a deployment via facade', function (): void {
        Saloon::fake([
            UpdateDeploymentRequest::class => MockResponse::fixture('Replicate/Deployments/update-deployment-success'),
        ]);

        $response = replicateDriver()
            ->deployments()
            ->update('acme', 'my-deployment')
            ->hardware('gpu-a40-small')
            ->instances(2, 10)
            ->save();

        expect($response)->toBeInstanceOf(DeploymentResponse::class)
            ->and($response)->hardware()->toBe('gpu-a40-small')
            ->and($response)->minInstances()->toBe(2)
            ->and($response)->maxInstances()->toBe(10);
    });

    it('deletes a deployment via facade', function (): void {
        Saloon::fake([
            DeleteDeploymentRequest::class => MockResponse::fixture('Replicate/Deployments/delete-deployment-success'),
        ]);

        $result = replicateDriver()->deployments()->delete('acme', 'my-deployment');

        expect($result)->toBeTrue();
    });

});

describe('Replicate Deployment Predictions via Facade', function (): void {

    it('creates prediction via deployment', function (): void {
        Saloon::fake([
            CreateDeploymentPredictionRequest::class => MockResponse::fixture('Replicate/Deployments/create-deployment-prediction-success'),
        ]);

        $response = replicateDriver()
            ->deployment('acme/my-deployment')
            ->with(['prompt' => 'A beautiful sunset over mountains'])
            ->run();

        expect($response)->toBeInstanceOf(PredictionResponse::class)
            ->and($response)->id->toBe('abc123xyz789prediction');
    });

    it('creates prediction via deployment with webhook', function (): void {
        Saloon::fake([
            CreateDeploymentPredictionRequest::class => MockResponse::fixture('Replicate/Deployments/create-deployment-prediction-success'),
        ]);

        $response = replicateDriver()
            ->deployment('acme/my-deployment')
            ->with(['prompt' => 'A beautiful sunset'])
            ->webhook('https://example.com/webhook')
            ->run();

        expect($response)->toBeInstanceOf(PredictionResponse::class);
    });

    it('throws exception for invalid deployment format', function (): void {
        expect(fn () => replicateDriver()->deployment('invalid-format'))
            ->toThrow(InvalidArgumentException::class, "Deployment must be in 'owner/name' format");
    });

});

describe('Replicate Deployments End-to-End Workflow', function (): void {

    it('can perform full deployment lifecycle', function (): void {
        // Create deployment
        Saloon::fake([
            CreateDeploymentRequest::class => MockResponse::fixture('Replicate/Deployments/create-deployment-success'),
        ]);

        $created = replicateDriver()
            ->deployments()
            ->create('my-deployment')
            ->model('stability-ai/sdxl')
            ->version('da77bc59ee60423279fd632efb4795ab731d9e3ca9705ef3341091fb989b7eaf')
            ->hardware('gpu-t4')
            ->instances(1, 5)
            ->save();

        expect($created)->name->toBe('my-deployment');

        // Run prediction
        Saloon::fake([
            CreateDeploymentPredictionRequest::class => MockResponse::fixture('Replicate/Deployments/create-deployment-prediction-success'),
        ]);

        $prediction = replicateDriver()
            ->deployment('acme/my-deployment')
            ->with(['prompt' => 'A sunset'])
            ->run();

        expect($prediction)->id->toBe('abc123xyz789prediction');

        // Update deployment
        Saloon::fake([
            UpdateDeploymentRequest::class => MockResponse::fixture('Replicate/Deployments/update-deployment-success'),
        ]);

        $updated = replicateDriver()
            ->deployments()
            ->update('acme', 'my-deployment')
            ->hardware('gpu-a40-small')
            ->instances(2, 10)
            ->save();

        expect($updated)->hardware()->toBe('gpu-a40-small');

        // Delete deployment
        Saloon::fake([
            DeleteDeploymentRequest::class => MockResponse::fixture('Replicate/Deployments/delete-deployment-success'),
        ]);

        $deleted = replicateDriver()
            ->deployments()
            ->delete('acme', 'my-deployment');

        expect($deleted)->toBeTrue();
    });

    it('handles pagination in deployment list', function (): void {
        Saloon::fake([
            ListDeploymentsRequest::class => MockResponse::fixture('Replicate/Deployments/list-deployments-success'),
        ]);

        $collection = replicateDriver()->deployments()->list();

        expect($collection)->hasMore()->toBeTrue()
            ->and($collection)->next()->toBe('https://api.replicate.com/v1/deployments?cursor=abc123')
            ->and($collection)->previous()->toBeNull();
    });

});

describe('Direct Driver Access', function (): void {

    it('can access deployments directly from driver instance', function (): void {
        $driver = new ReplicateDriver([
            'api_key' => 'test-key',
            'base_url' => 'https://api.replicate.com',
        ]);

        $manager = $driver->deployments();

        expect($manager)->toBeInstanceOf(DeploymentsManager::class);
    });

});
