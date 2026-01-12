<?php

declare(strict_types=1);

use Cjmellor\FalAi\Drivers\Fal\FalDriver;
use Cjmellor\FalAi\Exceptions\InvalidModelException;
use Cjmellor\FalAi\Requests\CancelRequest;
use Cjmellor\FalAi\Requests\FetchRequestStatusRequest;
use Cjmellor\FalAi\Requests\GetResultRequest;
use Cjmellor\FalAi\Requests\SubmitRequest;
use Cjmellor\FalAi\Responses\StatusResponse;
use Cjmellor\FalAi\Responses\SubmitResponse;
use Cjmellor\FalAi\Support\FluentRequest;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

beforeEach(function (): void {
    MockClient::destroyGlobal();
});

function createFalDriver(): FalDriver
{
    return new FalDriver([
        'api_key' => 'test-api-key',
        'base_url' => 'https://queue.fal.run',
        'sync_url' => 'https://fal.run',
        'platform_base_url' => 'https://api.fal.ai',
        'default_model' => 'test-model',
    ]);
}

describe('FalDriver Core Class', function (): void {

    it('can create a fluent request with model id', function (): void {
        $driver = createFalDriver();
        $fluentRequest = $driver->model('custom-model');

        expect($fluentRequest)
            ->toBeInstanceOf(FluentRequest::class);
    });

    it('can submit data with explicit model id', function (): void {
        MockClient::global([
            SubmitRequest::class => MockResponse::make(
                body: [
                    'request_id' => 'test-request-123',
                    'response_url' => 'https://queue.fal.run/explicit-model/requests/test-request-123',
                    'status_url' => 'https://queue.fal.run/explicit-model/requests/test-request-123/status',
                    'cancel_url' => 'https://queue.fal.run/explicit-model/requests/test-request-123/cancel',
                ],
                status: 200
            ),
        ]);

        $driver = createFalDriver();
        $response = $driver->model('explicit-model')->prompt('test prompt')->run();

        expect($response)
            ->toBeInstanceOf(SubmitResponse::class)
            ->status()->toBe(200)
            ->and($response->json()['request_id'])->toBe('test-request-123');
    });

    it('can submit data with config default model id', function (): void {
        MockClient::global([
            SubmitRequest::class => MockResponse::make(
                body: [
                    'request_id' => 'test-request-456',
                    'response_url' => 'https://queue.fal.run/test-model/requests/test-request-456',
                    'status_url' => 'https://queue.fal.run/test-model/requests/test-request-456/status',
                    'cancel_url' => 'https://queue.fal.run/test-model/requests/test-request-456/cancel',
                ],
                status: 200
            ),
        ]);

        $driver = createFalDriver();
        // Using null model uses config default
        $request = new FluentRequest($driver, null);
        $response = $request->prompt('test prompt')->run();

        expect($response)
            ->toBeInstanceOf(SubmitResponse::class)
            ->status()->toBe(200)
            ->and($response->json()['request_id'])->toBe('test-request-456');
    });

    it('can get request status', function (): void {
        MockClient::global([
            FetchRequestStatusRequest::class => MockResponse::make(
                body: [
                    'status' => 'COMPLETED',
                    'logs' => [
                        ['message' => 'Processing started', 'level' => 'INFO', 'timestamp' => '2024-01-01T00:00:00Z'],
                        ['message' => 'Processing completed', 'level' => 'INFO', 'timestamp' => '2024-01-01T00:01:00Z'],
                    ],
                    'response_url' => 'https://queue.fal.run/test-model/requests/test-request-id',
                ],
                status: 200
            ),
        ]);

        $driver = createFalDriver();
        $response = $driver->status('test-request-id', 'test-model');

        expect($response)
            ->toBeInstanceOf(StatusResponse::class)
            ->status()->toBe(200)
            ->and($response->json()['status'])->toBe('COMPLETED');
    });

    it('can get request result', function (): void {
        MockClient::global([
            GetResultRequest::class => MockResponse::make(
                body: ['result' => 'generated content'],
                status: 200
            ),
        ]);

        $driver = createFalDriver();
        $response = $driver->result('test-request-123', 'test-model');

        expect($response->status())->toBe(200)
            ->and($response->json())->toBe(['result' => 'generated content']);
    });

    it('can cancel a request', function (): void {
        MockClient::global([
            CancelRequest::class => MockResponse::make(
                body: ['cancelled' => true],
                status: 200
            ),
        ]);

        $driver = createFalDriver();
        $result = $driver->cancel('test-request-123', 'test-model');

        expect($result)->toBeTrue();
    });

    it('throws exception when no model id provided and no config default', function (): void {
        $driver = new FalDriver([
            'api_key' => 'test-api-key',
            'base_url' => 'https://queue.fal.run',
            'sync_url' => 'https://fal.run',
            'default_model' => '', // No default model
        ]);

        $request = new FluentRequest($driver, null);

        expect(fn () => $request->prompt('test')->run())
            ->toThrow(InvalidModelException::class, 'Model ID cannot be empty');
    });

    it('prioritizes explicit model id over config default', function (): void {
        MockClient::global([
            SubmitRequest::class => MockResponse::make(
                body: ['request_id' => 'test-request-explicit'],
                status: 200
            ),
        ]);

        $driver = createFalDriver();
        $response = $driver->model('explicit-model')->prompt('test')->run();

        expect($response->status())->toBe(200)
            ->and($response->json()['request_id'])->toBe('test-request-explicit');
    });

    it('returns driver name', function (): void {
        $driver = createFalDriver();

        expect($driver->getName())->toBe('fal');
    });

    it('queue mode uses queue URL', function (): void {
        MockClient::global([
            SubmitRequest::class => MockResponse::make(
                body: ['request_id' => 'queue-request'],
                status: 200
            ),
        ]);

        $driver = createFalDriver();
        $response = $driver->model('test-model')->prompt('test')->queue()->run();

        expect($response->status())->toBe(200);
    });

    it('sync mode uses sync URL', function (): void {
        MockClient::global([
            SubmitRequest::class => MockResponse::make(
                body: ['request_id' => 'sync-request'],
                status: 200
            ),
        ]);

        $driver = createFalDriver();
        $response = $driver->model('test-model')->prompt('test')->sync()->run();

        expect($response->status())->toBe(200);
    });

});
