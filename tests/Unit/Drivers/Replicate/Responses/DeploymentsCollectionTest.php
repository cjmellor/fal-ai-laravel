<?php

declare(strict_types=1);

use Cjmellor\FalAi\Drivers\Replicate\ReplicateDriver;
use Cjmellor\FalAi\Drivers\Replicate\Requests\Deployments\ListDeploymentsRequest;
use Cjmellor\FalAi\Drivers\Replicate\Responses\DeploymentResponse;
use Cjmellor\FalAi\Drivers\Replicate\Responses\DeploymentsCollection;
use Saloon\Http\Faking\MockResponse;
use Saloon\Laravel\Facades\Saloon;

covers(DeploymentsCollection::class);

beforeEach(function (): void {
    config([
        'fal-ai.drivers.replicate.api_key' => 'test-replicate-key',
        'fal-ai.drivers.replicate.base_url' => 'https://api.replicate.com',
    ]);
});

function createDeploymentsCollectionFromMock(array $data): DeploymentsCollection
{
    Saloon::fake([
        ListDeploymentsRequest::class => MockResponse::make($data, 200),
    ]);

    $driver = new ReplicateDriver([
        'api_key' => 'test-key',
        'base_url' => 'https://api.replicate.com',
    ]);

    return $driver->deployments()->list();
}

describe('DeploymentsCollection pagination', function (): void {

    it('returns results as array of DeploymentResponse', function (): void {
        $collection = createDeploymentsCollectionFromMock([
            'results' => [
                ['owner' => 'acme', 'name' => 'deployment-one'],
                ['owner' => 'acme', 'name' => 'deployment-two'],
            ],
            'next' => null,
            'previous' => null,
        ]);

        $results = $collection->results();

        expect($results)->toBeArray()
            ->and($results)->toHaveCount(2)
            ->and($results[0])->toBeInstanceOf(DeploymentResponse::class);

        expect($results)->sequence(
            fn ($item) => $item->name->toBe('deployment-one'),
            fn ($item) => $item->name->toBe('deployment-two'),
        );
    });

    it('returns empty array when no results', function (): void {
        $collection = createDeploymentsCollectionFromMock([
            'results' => [],
            'next' => null,
            'previous' => null,
        ]);

        expect($collection)->results()->toBeArray()
            ->and($collection)->results()->toBeEmpty();
    });

    it('returns next page URL', function (): void {
        $collection = createDeploymentsCollectionFromMock([
            'results' => [],
            'next' => 'https://api.replicate.com/v1/deployments?cursor=abc123',
            'previous' => null,
        ]);

        expect($collection)->next()->toBe('https://api.replicate.com/v1/deployments?cursor=abc123');
    });

    it('returns null when no next page', function (): void {
        $collection = createDeploymentsCollectionFromMock([
            'results' => [],
            'next' => null,
            'previous' => null,
        ]);

        expect($collection)->next()->toBeNull();
    });

    it('returns previous page URL', function (): void {
        $collection = createDeploymentsCollectionFromMock([
            'results' => [],
            'next' => null,
            'previous' => 'https://api.replicate.com/v1/deployments?cursor=xyz789',
        ]);

        expect($collection)->previous()->toBe('https://api.replicate.com/v1/deployments?cursor=xyz789');
    });

    it('hasMore returns true when next page exists', function (): void {
        $collection = createDeploymentsCollectionFromMock([
            'results' => [],
            'next' => 'https://api.replicate.com/v1/deployments?cursor=abc123',
            'previous' => null,
        ]);

        expect($collection)->hasMore()->toBeTrue();
    });

    it('hasMore returns false when no next page', function (): void {
        $collection = createDeploymentsCollectionFromMock([
            'results' => [],
            'next' => null,
            'previous' => null,
        ]);

        expect($collection)->hasMore()->toBeFalse();
    });

});

describe('DeploymentsCollection helpers', function (): void {

    it('returns count of deployments', function (): void {
        $collection = createDeploymentsCollectionFromMock([
            'results' => [
                ['owner' => 'acme', 'name' => 'deployment-one'],
                ['owner' => 'acme', 'name' => 'deployment-two'],
                ['owner' => 'acme', 'name' => 'deployment-three'],
            ],
            'next' => null,
            'previous' => null,
        ]);

        expect($collection)->count()->toBe(3);
    });

    it('returns zero count for empty collection', function (): void {
        $collection = createDeploymentsCollectionFromMock([
            'results' => [],
            'next' => null,
            'previous' => null,
        ]);

        expect($collection)->count()->toBe(0);
    });

    it('returns raw data as array', function (): void {
        $data = [
            'results' => [['owner' => 'acme', 'name' => 'test']],
            'next' => 'https://example.com/next',
            'previous' => null,
        ];

        $collection = createDeploymentsCollectionFromMock($data);

        expect($collection)->toArray()->toBe($data);
    });

    it('returns underlying Saloon response', function (): void {
        $collection = createDeploymentsCollectionFromMock([
            'results' => [],
            'next' => null,
            'previous' => null,
        ]);

        expect($collection)->getResponse()->toBeInstanceOf(\Saloon\Http\Response::class);
    });

});
