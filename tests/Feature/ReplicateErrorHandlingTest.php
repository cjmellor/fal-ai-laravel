<?php

declare(strict_types=1);

use Cjmellor\FalAi\Drivers\Replicate\ReplicateConnector;
use Cjmellor\FalAi\Drivers\Replicate\ReplicateDriver;
use Cjmellor\FalAi\Drivers\Replicate\Requests\CancelPredictionRequest;
use Cjmellor\FalAi\Drivers\Replicate\Requests\CreatePredictionRequest;
use Cjmellor\FalAi\Drivers\Replicate\Requests\GetPredictionRequest;
use Saloon\Exceptions\Request\Statuses\ForbiddenException;
use Saloon\Exceptions\Request\Statuses\InternalServerErrorException;
use Saloon\Exceptions\Request\Statuses\NotFoundException;
use Saloon\Exceptions\Request\Statuses\ServiceUnavailableException;
use Saloon\Exceptions\Request\Statuses\TooManyRequestsException;
use Saloon\Exceptions\Request\Statuses\UnauthorizedException;
use Saloon\Exceptions\Request\Statuses\UnprocessableEntityException;
use Saloon\Http\Faking\MockResponse;
use Saloon\Laravel\Facades\Saloon;

covers(ReplicateConnector::class);
covers(ReplicateDriver::class);

beforeEach(function (): void {
    config([
        'fal-ai.drivers.replicate.api_key' => 'test-api-key',
        'fal-ai.drivers.replicate.base_url' => 'https://api.replicate.com',
    ]);
});

function createReplicateDriverForErrorTests(): ReplicateDriver
{
    return new ReplicateDriver([
        'api_key' => 'test-api-key',
        'base_url' => 'https://api.replicate.com',
        'default_model' => 'owner/model:abc123',
    ]);
}

describe('Replicate Create Prediction Error Handling', function (): void {
    it('throws UnauthorizedException on 401 response', function (): void {
        Saloon::fake([
            CreatePredictionRequest::class => MockResponse::make(['error' => 'Unauthorized'], 401),
        ]);

        $driver = createReplicateDriverForErrorTests();

        expect(fn () => $driver->model('owner/model:abc123')->prompt('test')->run())
            ->toThrow(UnauthorizedException::class);
    });

    it('throws ForbiddenException on 403 response', function (): void {
        Saloon::fake([
            CreatePredictionRequest::class => MockResponse::make(['error' => 'Forbidden'], 403),
        ]);

        $driver = createReplicateDriverForErrorTests();

        expect(fn () => $driver->model('owner/model:abc123')->prompt('test')->run())
            ->toThrow(ForbiddenException::class);
    });

    it('throws NotFoundException on 404 response', function (): void {
        Saloon::fake([
            CreatePredictionRequest::class => MockResponse::make(['error' => 'Model not found'], 404),
        ]);

        $driver = createReplicateDriverForErrorTests();

        expect(fn () => $driver->model('owner/non-existent:abc123')->prompt('test')->run())
            ->toThrow(NotFoundException::class);
    });

    it('throws UnprocessableEntityException on 422 response', function (): void {
        Saloon::fake([
            CreatePredictionRequest::class => MockResponse::make(['error' => 'Validation failed'], 422),
        ]);

        $driver = createReplicateDriverForErrorTests();

        expect(fn () => $driver->model('owner/model:abc123')->with(['invalid' => 'data'])->run())
            ->toThrow(UnprocessableEntityException::class);
    });

    it('throws TooManyRequestsException on 429 response', function (): void {
        Saloon::fake([
            CreatePredictionRequest::class => MockResponse::make(['error' => 'Rate limit exceeded'], 429),
        ]);

        $driver = createReplicateDriverForErrorTests();

        expect(fn () => $driver->model('owner/model:abc123')->prompt('test')->run())
            ->toThrow(TooManyRequestsException::class);
    });

    it('throws InternalServerErrorException on 500 response', function (): void {
        Saloon::fake([
            CreatePredictionRequest::class => MockResponse::make(['error' => 'Internal server error'], 500),
        ]);

        $driver = createReplicateDriverForErrorTests();

        expect(fn () => $driver->model('owner/model:abc123')->prompt('test')->run())
            ->toThrow(InternalServerErrorException::class);
    });

    it('throws ServiceUnavailableException on 503 response', function (): void {
        Saloon::fake([
            CreatePredictionRequest::class => MockResponse::make(['error' => 'Service unavailable'], 503),
        ]);

        $driver = createReplicateDriverForErrorTests();

        expect(fn () => $driver->model('owner/model:abc123')->prompt('test')->run())
            ->toThrow(ServiceUnavailableException::class);
    });
});

describe('Replicate Get Prediction Error Handling', function (): void {
    it('throws NotFoundException on 404 response for status', function (): void {
        Saloon::fake([
            GetPredictionRequest::class => MockResponse::make(['error' => 'Prediction not found'], 404),
        ]);

        $driver = createReplicateDriverForErrorTests();

        expect(fn () => $driver->status('non-existent-id'))
            ->toThrow(NotFoundException::class);
    });

    it('throws UnauthorizedException on 401 response for status', function (): void {
        Saloon::fake([
            GetPredictionRequest::class => MockResponse::make(['error' => 'Unauthorized'], 401),
        ]);

        $driver = createReplicateDriverForErrorTests();

        expect(fn () => $driver->status('some-id'))
            ->toThrow(UnauthorizedException::class);
    });
});

describe('Replicate Cancel Prediction Error Handling', function (): void {
    it('throws NotFoundException on 404 response for cancel', function (): void {
        Saloon::fake([
            CancelPredictionRequest::class => MockResponse::make(['error' => 'Prediction not found'], 404),
        ]);

        $driver = createReplicateDriverForErrorTests();

        expect(fn () => $driver->cancel('non-existent-id'))
            ->toThrow(NotFoundException::class);
    });
});

describe('Successful Replicate requests do not throw', function (): void {
    it('does not throw on successful prediction creation', function (): void {
        Saloon::fake([
            CreatePredictionRequest::class => MockResponse::fixture('Replicate/create-prediction-success'),
        ]);

        $driver = createReplicateDriverForErrorTests();
        $response = $driver->model('owner/model:abc123')->prompt('test')->run();

        expect($response->successful())->toBeTrue()
            ->and($response->id)->not->toBeEmpty();
    });

    it('does not throw on successful prediction status', function (): void {
        Saloon::fake([
            GetPredictionRequest::class => MockResponse::fixture('Replicate/get-prediction-succeeded'),
        ]);

        $driver = createReplicateDriverForErrorTests();
        $response = $driver->status('test-prediction-id');

        expect($response->successful())->toBeTrue()
            ->and($response->isSucceeded())->toBeTrue();
    });
});
