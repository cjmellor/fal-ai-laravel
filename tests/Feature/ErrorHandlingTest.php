<?php

declare(strict_types=1);

use Cjmellor\FalAi\Connectors\FalConnector;
use Cjmellor\FalAi\Connectors\PlatformConnector;
use Cjmellor\FalAi\Drivers\Fal\FalDriver;
use Cjmellor\FalAi\Requests\Platform\GetPricingRequest;
use Cjmellor\FalAi\Requests\SubmitRequest;
use Saloon\Exceptions\Request\Statuses\ForbiddenException;
use Saloon\Exceptions\Request\Statuses\InternalServerErrorException;
use Saloon\Exceptions\Request\Statuses\NotFoundException;
use Saloon\Exceptions\Request\Statuses\ServiceUnavailableException;
use Saloon\Exceptions\Request\Statuses\TooManyRequestsException;
use Saloon\Exceptions\Request\Statuses\UnauthorizedException;
use Saloon\Exceptions\Request\Statuses\UnprocessableEntityException;
use Saloon\Http\Faking\MockResponse;
use Saloon\Laravel\Facades\Saloon;

covers(FalConnector::class);
covers(PlatformConnector::class);
covers(FalDriver::class);

beforeEach(function (): void {
    config([
        'fal-ai.drivers.fal.api_key' => 'test-api-key',
        'fal-ai.drivers.fal.base_url' => 'https://queue.fal.run',
        'fal-ai.drivers.fal.sync_url' => 'https://fal.run',
        'fal-ai.drivers.fal.platform_base_url' => 'https://api.fal.ai',
    ]);
});

function createFalDriverForErrorTests(): FalDriver
{
    return new FalDriver([
        'api_key' => 'test-api-key',
        'base_url' => 'https://queue.fal.run',
        'sync_url' => 'https://fal.run',
        'platform_base_url' => 'https://api.fal.ai',
        'default_model' => 'test-model',
    ]);
}

describe('Model API Error Handling', function (): void {
    it('throws UnauthorizedException on 401 response', function (): void {
        Saloon::fake([
            SubmitRequest::class => MockResponse::make(['error' => 'Unauthorized'], 401),
        ]);

        $driver = createFalDriverForErrorTests();

        expect(fn (): Cjmellor\FalAi\Responses\SubmitResponse => $driver->model('fal-ai/flux/dev')->prompt('test')->run())
            ->toThrow(UnauthorizedException::class);
    });

    it('throws ForbiddenException on 403 response', function (): void {
        Saloon::fake([
            SubmitRequest::class => MockResponse::make(['error' => 'Forbidden'], 403),
        ]);

        $driver = createFalDriverForErrorTests();

        expect(fn (): Cjmellor\FalAi\Responses\SubmitResponse => $driver->model('fal-ai/flux/dev')->prompt('test')->run())
            ->toThrow(ForbiddenException::class);
    });

    it('throws NotFoundException on 404 response', function (): void {
        Saloon::fake([
            SubmitRequest::class => MockResponse::make(['error' => 'Not Found'], 404),
        ]);

        $driver = createFalDriverForErrorTests();

        expect(fn (): Cjmellor\FalAi\Responses\SubmitResponse => $driver->model('fal-ai/non-existent-model')->prompt('test')->run())
            ->toThrow(NotFoundException::class);
    });

    it('throws UnprocessableEntityException on 422 response', function (): void {
        Saloon::fake([
            SubmitRequest::class => MockResponse::make(['error' => 'Validation failed'], 422),
        ]);

        $driver = createFalDriverForErrorTests();

        expect(fn (): Cjmellor\FalAi\Responses\SubmitResponse => $driver->model('fal-ai/flux/dev')->with(['invalid' => 'data'])->run())
            ->toThrow(UnprocessableEntityException::class);
    });

    it('throws TooManyRequestsException on 429 response', function (): void {
        Saloon::fake([
            SubmitRequest::class => MockResponse::make(['error' => 'Rate limit exceeded'], 429),
        ]);

        $driver = createFalDriverForErrorTests();

        expect(fn (): Cjmellor\FalAi\Responses\SubmitResponse => $driver->model('fal-ai/flux/dev')->prompt('test')->run())
            ->toThrow(TooManyRequestsException::class);
    });

    it('throws InternalServerErrorException on 500 response', function (): void {
        Saloon::fake([
            SubmitRequest::class => MockResponse::make(['error' => 'Internal server error'], 500),
        ]);

        $driver = createFalDriverForErrorTests();

        expect(fn (): Cjmellor\FalAi\Responses\SubmitResponse => $driver->model('fal-ai/flux/dev')->prompt('test')->run())
            ->toThrow(InternalServerErrorException::class);
    });

    it('throws ServiceUnavailableException on 503 response', function (): void {
        Saloon::fake([
            SubmitRequest::class => MockResponse::make(['error' => 'Service unavailable'], 503),
        ]);

        $driver = createFalDriverForErrorTests();

        expect(fn (): Cjmellor\FalAi\Responses\SubmitResponse => $driver->model('fal-ai/flux/dev')->prompt('test')->run())
            ->toThrow(ServiceUnavailableException::class);
    });
});

describe('Platform API Error Handling', function (): void {
    it('throws UnauthorizedException on 401 response for pricing API', function (): void {
        Saloon::fake([
            GetPricingRequest::class => MockResponse::make(['error' => 'Unauthorized'], 401),
        ]);

        $driver = createFalDriverForErrorTests();

        expect(fn (): Cjmellor\FalAi\Responses\PricingResponse => $driver->platform()->pricing()->forEndpoint('fal-ai/flux/dev')->get())
            ->toThrow(UnauthorizedException::class);
    });

    it('throws ForbiddenException on 403 response for pricing API', function (): void {
        Saloon::fake([
            GetPricingRequest::class => MockResponse::make(['error' => 'Forbidden'], 403),
        ]);

        $driver = createFalDriverForErrorTests();

        expect(fn (): Cjmellor\FalAi\Responses\PricingResponse => $driver->platform()->pricing()->forEndpoint('fal-ai/flux/dev')->get())
            ->toThrow(ForbiddenException::class);
    });

    it('throws NotFoundException on 404 response for pricing API', function (): void {
        Saloon::fake([
            GetPricingRequest::class => MockResponse::make(['error' => 'Endpoint not found'], 404),
        ]);

        $driver = createFalDriverForErrorTests();

        expect(fn (): Cjmellor\FalAi\Responses\PricingResponse => $driver->platform()->pricing()->forEndpoint('fal-ai/non-existent')->get())
            ->toThrow(NotFoundException::class);
    });

    it('throws TooManyRequestsException on 429 response for pricing API', function (): void {
        Saloon::fake([
            GetPricingRequest::class => MockResponse::make(['error' => 'Rate limit exceeded'], 429),
        ]);

        $driver = createFalDriverForErrorTests();

        expect(fn (): Cjmellor\FalAi\Responses\PricingResponse => $driver->platform()->pricing()->forEndpoint('fal-ai/flux/dev')->get())
            ->toThrow(TooManyRequestsException::class);
    });

    it('throws InternalServerErrorException on 500 response for pricing API', function (): void {
        Saloon::fake([
            GetPricingRequest::class => MockResponse::make(['error' => 'Internal server error'], 500),
        ]);

        $driver = createFalDriverForErrorTests();

        expect(fn (): Cjmellor\FalAi\Responses\PricingResponse => $driver->platform()->pricing()->forEndpoint('fal-ai/flux/dev')->get())
            ->toThrow(InternalServerErrorException::class);
    });

    it('throws ServiceUnavailableException on 503 response for pricing API', function (): void {
        Saloon::fake([
            GetPricingRequest::class => MockResponse::make(['error' => 'Service unavailable'], 503),
        ]);

        $driver = createFalDriverForErrorTests();

        expect(fn (): Cjmellor\FalAi\Responses\PricingResponse => $driver->platform()->pricing()->forEndpoint('fal-ai/flux/dev')->get())
            ->toThrow(ServiceUnavailableException::class);
    });
});

describe('Successful requests do not throw', function (): void {
    it('does not throw on successful model API response', function (): void {
        Saloon::fake([
            SubmitRequest::class => MockResponse::make([
                'request_id' => 'test-123',
                'status_url' => 'https://queue.fal.run/status',
                'response_url' => 'https://queue.fal.run/response',
                'cancel_url' => 'https://queue.fal.run/cancel',
            ], 200),
        ]);

        $driver = createFalDriverForErrorTests();
        $response = $driver->model('fal-ai/flux/dev')->prompt('test')->run();

        expect($response->successful())->toBeTrue()
            ->and($response->requestId)->toBe('test-123');
    });

    it('does not throw on successful platform API response', function (): void {
        Saloon::fake([
            GetPricingRequest::class => MockResponse::make([
                'prices' => [
                    ['endpoint_id' => 'fal-ai/flux/dev', 'unit_price' => 0.025, 'unit' => 'image', 'currency' => 'USD'],
                ],
                'has_more' => false,
                'next_cursor' => null,
            ], 200),
        ]);

        $driver = createFalDriverForErrorTests();
        $response = $driver->platform()->pricing()->forEndpoint('fal-ai/flux/dev')->get();

        expect($response->successful())->toBeTrue()
            ->and($response->prices)->toHaveCount(1)
            ->and($response->getUnitPriceFor('fal-ai/flux/dev'))->toBe(0.025);
    });
});
