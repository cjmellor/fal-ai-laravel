<?php

declare(strict_types=1);

use Cjmellor\FalAi\Drivers\Replicate\ReplicateDriver;
use Cjmellor\FalAi\Drivers\Replicate\Requests\Deployments\DeleteDeploymentRequest;
use Cjmellor\FalAi\Drivers\Replicate\Requests\Deployments\GetDeploymentRequest;
use Cjmellor\FalAi\Drivers\Replicate\Requests\Deployments\ListDeploymentsRequest;
use Cjmellor\FalAi\Drivers\Replicate\Responses\DeploymentResponse;
use Cjmellor\FalAi\Drivers\Replicate\Responses\DeploymentsCollection;
use Cjmellor\FalAi\Drivers\Replicate\Support\DeploymentBuilder;
use Cjmellor\FalAi\Drivers\Replicate\Support\DeploymentsManager;
use Saloon\Http\Faking\MockResponse;
use Saloon\Laravel\Facades\Saloon;

covers(DeploymentsManager::class);

beforeEach(function (): void {
    config([
        'fal-ai.drivers.replicate.api_key' => 'test-replicate-key',
        'fal-ai.drivers.replicate.base_url' => 'https://api.replicate.com',
    ]);
});

function getDeploymentsManager(): DeploymentsManager
{
    $driver = new ReplicateDriver([
        'api_key' => 'test-key',
        'base_url' => 'https://api.replicate.com',
    ]);

    return $driver->deployments();
}

describe('DeploymentsManager list', function (): void {

    it('returns DeploymentsCollection', function (): void {
        Saloon::fake([
            ListDeploymentsRequest::class => MockResponse::fixture('Replicate/Deployments/list-deployments-success'),
        ]);

        $manager = getDeploymentsManager();
        $collection = $manager->list();

        expect($collection)->toBeInstanceOf(DeploymentsCollection::class)
            ->and($collection->count())->toBe(2);
    });

    it('handles empty list', function (): void {
        Saloon::fake([
            ListDeploymentsRequest::class => MockResponse::fixture('Replicate/Deployments/list-deployments-empty'),
        ]);

        $manager = getDeploymentsManager();
        $collection = $manager->list();

        expect($collection)->toBeInstanceOf(DeploymentsCollection::class)
            ->and($collection->count())->toBe(0)
            ->and($collection->hasMore())->toBeFalse();
    });

});

describe('DeploymentsManager get', function (): void {

    it('returns DeploymentResponse', function (): void {
        Saloon::fake([
            GetDeploymentRequest::class => MockResponse::fixture('Replicate/Deployments/get-deployment-success'),
        ]);

        $manager = getDeploymentsManager();
        $response = $manager->get('acme', 'my-deployment');

        expect($response)->toBeInstanceOf(DeploymentResponse::class)
            ->and($response->owner)->toBe('acme')
            ->and($response->name)->toBe('my-deployment');
    });

});

describe('DeploymentsManager create', function (): void {

    it('returns DeploymentBuilder for create', function (): void {
        $manager = getDeploymentsManager();
        $builder = $manager->create('my-new-deployment');

        expect($builder)->toBeInstanceOf(DeploymentBuilder::class);
    });

});

describe('DeploymentsManager update', function (): void {

    it('returns DeploymentBuilder for update', function (): void {
        $manager = getDeploymentsManager();
        $builder = $manager->update('acme', 'my-deployment');

        expect($builder)->toBeInstanceOf(DeploymentBuilder::class);
    });

});

describe('DeploymentsManager delete', function (): void {

    it('returns true on successful delete', function (): void {
        Saloon::fake([
            DeleteDeploymentRequest::class => MockResponse::fixture('Replicate/Deployments/delete-deployment-success'),
        ]);

        $manager = getDeploymentsManager();
        $result = $manager->delete('acme', 'my-deployment');

        expect($result)->toBeTrue();
    });

});
