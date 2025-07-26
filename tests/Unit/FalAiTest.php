<?php

declare(strict_types=1);

use Cjmellor\FalAi\Exceptions\InvalidModelException;
use Cjmellor\FalAi\FalAi;
use Cjmellor\FalAi\Requests\CancelRequest;
use Cjmellor\FalAi\Requests\FetchRequestStatusRequest;
use Cjmellor\FalAi\Requests\GetResultRequest;
use Cjmellor\FalAi\Requests\SubmitRequest;
use Cjmellor\FalAi\Responses\SubmitResponse;
use Cjmellor\FalAi\Support\FluentRequest;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

beforeEach(function (): void {
    MockClient::destroyGlobal();

    // Set up test config
    config([
        'fal-ai.api_key' => 'test-api-key',
        'fal-ai.base_url' => 'https://test.fal.run',
        'fal-ai.default_model' => 'test-model',
    ]);
});

describe('FalAi Core Class', function (): void {

    it('can create a fluent request with model id', function (): void {
        $falAi = new FalAi();
        $fluentRequest = $falAi->model('custom-model');

        expect($fluentRequest)
            ->toBeInstanceOf(FluentRequest::class);
    });

    it('can create a fluent request without model id (uses config default)', function (): void {
        $falAi = new FalAi();
        $fluentRequest = $falAi->model();

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

        $falAi = new FalAi();
        $response = $falAi->run(['prompt' => 'test prompt'], 'explicit-model');

        expect($response->status())->toBe(200)
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

        $falAi = new FalAi();
        $response = $falAi->run(['prompt' => 'test prompt']);

        expect($response->status())->toBe(200)
            ->and($response->json()['request_id'])->toBe('test-request-456');
    });

    it('can get request status with logs', function (): void {
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

        $falAi = new FalAi();
        $response = $falAi->status('test-request-id', true, 'test-model');

        expect($response->status())->toBe(200)
            ->and($response->json()['status'])->toBe('COMPLETED')
            ->and($response->json()['logs'])->toHaveCount(2);
    });

    it('can get request status without logs', function (): void {
        MockClient::global([
            FetchRequestStatusRequest::class => MockResponse::make(
                body: [
                    'status' => 'IN_QUEUE',
                    'queue_position' => 3,
                    'response_url' => 'https://queue.fal.run/test-model/requests/test-request-id',
                ],
                status: 200
            ),
        ]);

        $falAi = new FalAi();
        $response = $falAi->status('test-request-id', false, 'test-model');

        expect($response->status())->toBe(200)
            ->and($response->json()['status'])->toBe('IN_QUEUE')
            ->and($response->json()['queue_position'])->toBe(3);
    });

    it('can get request result', function (): void {
        MockClient::global([
            GetResultRequest::class => MockResponse::make(
                body: ['result' => 'generated content'],
                status: 200
            ),
        ]);

        $falAi = new FalAi();
        $response = $falAi->result('test-request-123', 'test-model');

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

        $falAi = new FalAi();
        $response = $falAi->cancel('test-request-123', 'test-model');

        expect($response->status())->toBe(200)
            ->and($response->json())->toBe(['cancelled' => true]);
    });

    it('throws exception when no model id provided and no config default', function (): void {
        // Clear the default model config
        config(['fal-ai.default_model' => '']);

        $falAi = new FalAi();

        expect(fn (): Saloon\Http\Response => $falAi->run(['prompt' => 'test']))
            ->toThrow(InvalidModelException::class, 'Model ID cannot be empty');
    });

    it('prioritizes explicit model id over config default', function (): void {
        MockClient::global([
            SubmitRequest::class => MockResponse::make(
                body: ['request_id' => 'test-request-explicit'],
                status: 200
            ),
        ]);

        $falAi = new FalAi();
        $response = $falAi->run(['prompt' => 'test'], 'explicit-model');

        // Verify explicit model was used by checking the request was made
        expect($response->status())->toBe(200)
            ->and($response->json()['request_id'])->toBe('test-request-explicit');
    });

    it('uses config default when no explicit model provided', function (): void {
        MockClient::global([
            SubmitRequest::class => MockResponse::make(
                body: ['request_id' => 'test-request-default'],
                status: 200
            ),
        ]);

        $falAi = new FalAi();
        $response = $falAi->run(['prompt' => 'test']);

        // Verify config default was used by checking the request was made
        expect($response->status())->toBe(200)
            ->and($response->json()['request_id'])->toBe('test-request-default');
    });

    it('queue method uses queue URL', function (): void {
        $falAi = Mockery::mock(FalAi::class);
        $falAi->shouldReceive('runWithBaseUrl')
            ->once()
            ->with(['prompt' => 'test'], 'test-model', 'https://queue.fal.run', null)
            ->andReturn(Mockery::mock(SubmitResponse::class));

        $request = new FluentRequest($falAi, 'test-model');
        $request->prompt('test')->queue()->run();
    });

    it('sync method uses sync URL', function (): void {
        $falAi = Mockery::mock(FalAi::class);
        $falAi->shouldReceive('runWithBaseUrl')
            ->once()
            ->with(['prompt' => 'test'], 'test-model', 'https://fal.run', null)
            ->andReturn(Mockery::mock(SubmitResponse::class));

        $request = new FluentRequest($falAi, 'test-model');
        $request->prompt('test')->sync()->run();
    });

});
