<?php

declare(strict_types=1);

use Cjmellor\FalAi\Drivers\Replicate\ReplicateDriver;
use Cjmellor\FalAi\Drivers\Replicate\Requests\Deployments\CreateDeploymentPredictionRequest;
use Cjmellor\FalAi\Drivers\Replicate\Responses\PredictionResponse;
use Cjmellor\FalAi\Drivers\Replicate\Support\DeploymentPredictionRequest;
use Saloon\Http\Faking\MockResponse;
use Saloon\Laravel\Facades\Saloon;

covers(DeploymentPredictionRequest::class);

beforeEach(function (): void {
    config([
        'fal-ai.drivers.replicate.api_key' => 'test-replicate-key',
        'fal-ai.drivers.replicate.base_url' => 'https://api.replicate.com',
    ]);
});

function getReplicateDriverForPredictions(): ReplicateDriver
{
    return new ReplicateDriver([
        'api_key' => 'test-key',
        'base_url' => 'https://api.replicate.com',
    ]);
}

describe('DeploymentPredictionRequest fluent interface', function (): void {

    it('allows chaining with() method', function (): void {
        $driver = getReplicateDriverForPredictions();
        $request = $driver->deployment('acme/my-deployment');

        $result = $request->with(['prompt' => 'A beautiful sunset']);

        expect($result)->toBeInstanceOf(DeploymentPredictionRequest::class);
    });

    it('allows chaining webhook() method', function (): void {
        $driver = getReplicateDriverForPredictions();
        $request = $driver->deployment('acme/my-deployment');

        $result = $request->webhook('https://example.com/hook');

        expect($result)->toBeInstanceOf(DeploymentPredictionRequest::class);
    });

    it('allows chaining multiple methods', function (): void {
        $driver = getReplicateDriverForPredictions();
        $request = $driver->deployment('acme/my-deployment');

        $result = $request
            ->with(['prompt' => 'test'])
            ->webhook('https://example.com/hook', ['start', 'completed']);

        expect($result)->toBeInstanceOf(DeploymentPredictionRequest::class);
    });

});

describe('DeploymentPredictionRequest run', function (): void {

    it('creates prediction via deployment', function (): void {
        Saloon::fake([
            CreateDeploymentPredictionRequest::class => MockResponse::fixture('Replicate/Deployments/create-deployment-prediction-success'),
        ]);

        $driver = getReplicateDriverForPredictions();
        $response = $driver->deployment('acme/my-deployment')
            ->with(['prompt' => 'A beautiful sunset'])
            ->run();

        expect($response)->toBeInstanceOf(PredictionResponse::class)
            ->and($response->id)->toBe('abc123xyz789prediction')
            ->and($response->predictionStatus)->toBe('starting');
    });

    it('creates prediction with webhook', function (): void {
        Saloon::fake([
            CreateDeploymentPredictionRequest::class => MockResponse::fixture('Replicate/Deployments/create-deployment-prediction-success'),
        ]);

        $driver = getReplicateDriverForPredictions();
        $response = $driver->deployment('acme/my-deployment')
            ->with(['prompt' => 'A beautiful sunset'])
            ->webhook('https://example.com/webhook')
            ->run();

        expect($response)->toBeInstanceOf(PredictionResponse::class);
    });

    it('creates prediction with custom webhook events', function (): void {
        Saloon::fake([
            CreateDeploymentPredictionRequest::class => MockResponse::fixture('Replicate/Deployments/create-deployment-prediction-success'),
        ]);

        $driver = getReplicateDriverForPredictions();
        $response = $driver->deployment('acme/my-deployment')
            ->with(['prompt' => 'A beautiful sunset'])
            ->webhook('https://example.com/webhook', ['start', 'output', 'completed'])
            ->run();

        expect($response)->toBeInstanceOf(PredictionResponse::class);
    });

    it('creates prediction without input', function (): void {
        Saloon::fake([
            CreateDeploymentPredictionRequest::class => MockResponse::fixture('Replicate/Deployments/create-deployment-prediction-success'),
        ]);

        $driver = getReplicateDriverForPredictions();
        $response = $driver->deployment('acme/my-deployment')->run();

        expect($response)->toBeInstanceOf(PredictionResponse::class);
    });

});

describe('ReplicateDriver deployment method', function (): void {

    it('parses owner/name format', function (): void {
        $driver = getReplicateDriverForPredictions();
        $request = $driver->deployment('acme/my-deployment');

        expect($request)->toBeInstanceOf(DeploymentPredictionRequest::class);
    });

    it('throws exception for invalid format', function (): void {
        $driver = getReplicateDriverForPredictions();

        expect(fn () => $driver->deployment('invalid-format'))
            ->toThrow(InvalidArgumentException::class, "Deployment must be in 'owner/name' format");
    });

    it('handles names with multiple slashes', function (): void {
        Saloon::fake([
            CreateDeploymentPredictionRequest::class => MockResponse::fixture('Replicate/Deployments/create-deployment-prediction-success'),
        ]);

        $driver = getReplicateDriverForPredictions();
        $response = $driver->deployment('acme/my/complex/name')
            ->with(['prompt' => 'test'])
            ->run();

        expect($response)->toBeInstanceOf(PredictionResponse::class);
    });

});
