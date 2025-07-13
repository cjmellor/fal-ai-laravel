<?php

declare(strict_types=1);

use Cjmellor\FalAi\FalAi;
use Cjmellor\FalAi\Requests\CancelRequest;
use Cjmellor\FalAi\Requests\FetchRequestStatusRequest;
use Cjmellor\FalAi\Requests\GetResultRequest;
use Cjmellor\FalAi\Requests\SubmitRequest;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

beforeEach(function (): void {
    MockClient::destroyGlobal();

    config([
        'fal-ai.api_key' => 'test-api-key',
        'fal-ai.base_url' => 'https://test.fal.run',
        'fal-ai.default_model' => 'test-model',
    ]);
});

describe('End-to-End Workflow Tests', function (): void {

    it('can submit request and get response (mocked)', function (): void {
        MockClient::global([
            SubmitRequest::class => MockResponse::make([
                'request_id' => 'test-request-123',
                'response_url' => 'https://queue.fal.run/test-model/requests/test-request-123',
                'status_url' => 'https://queue.fal.run/test-model/requests/test-request-123/status',
                'cancel_url' => 'https://queue.fal.run/test-model/requests/test-request-123/cancel',
            ], 200),
        ]);

        $falAi = new FalAi();

        $requestData = [
            'prompt' => 'Generate an image of a sunset',
            'image_size' => '512x512',
        ];

        // Act
        $response = $falAi->run($requestData, 'test-model');

        // Assert
        expect($response->status())->toBe(200)
            ->and($response->json()['request_id'])->toBe('test-request-123')
            ->and($response->json()['response_url'])->toBe('https://queue.fal.run/test-model/requests/test-request-123')
            ->and($response->json()['status_url'])->toBe('https://queue.fal.run/test-model/requests/test-request-123/status')
            ->and($response->json()['cancel_url'])->toBe('https://queue.fal.run/test-model/requests/test-request-123/cancel');
    });

    it('can check status of submitted request (mocked)', function (): void {
        MockClient::global([
            FetchRequestStatusRequest::class => MockResponse::make([
                'status' => 'IN_PROGRESS',
                'logs' => [],
                'partial' => null,
            ], 200),
        ]);

        $falAi = new FalAi();

        $requestId = 'test-request-123';

        // Act
        $response = $falAi->status($requestId, false, 'test-model');

        // Assert
        expect($response->status())->toBe(200)
            ->and($response->json()['status'])->toBe('IN_PROGRESS')
            ->and($response->json()['logs'])->toBe([])
            ->and($response->json()['partial'])->toBeNull();
    });

    it('can get result of completed request (mocked)', function (): void {
        MockClient::global([
            GetResultRequest::class => MockResponse::make([
                'images' => [
                    ['url' => 'https://example.com/generated-image.jpg'],
                ],
                'timings' => [
                    'inference' => 2.5,
                ],
                'seed' => 12345,
            ], 200),
        ]);

        $falAi = new FalAi();
        $requestId = 'test-request-123';

        $response = $falAi->result($requestId, 'test-model');

        // Assert
        $result = $response->json();
        expect($result)
            ->toBeArray()
            ->and($result['images'])->toHaveCount(1)
            ->and($result['images'][0]['url'])->toBe('https://example.com/generated-image.jpg')
            ->and($result['timings']['inference'])->toBe(2.5)
            ->and($result['seed'])->toBe(12345);
    });

    it('can cancel queued request (mocked)', function (): void {
        MockClient::global([
            CancelRequest::class => MockResponse::make([
                'cancelled' => true,
                'request_id' => 'test-request-123',
            ], 200),
        ]);

        $falAi = new FalAi();
        $response = $falAi->cancel('test-request-123', 'test-model');

        expect($response->status())->toBe(200)
            ->and($response->json()['cancelled'])->toBeTrue()
            ->and($response->json()['request_id'])->toBe('test-request-123');
    });

    it('can use fluent interface to submit request (mocked)', function (): void {
        MockClient::global([
            SubmitRequest::class => MockResponse::make([
                'request_id' => 'fluent-request-456',
                'response_url' => 'https://queue.fal.run/test-model/requests/fluent-request-456',
                'status_url' => 'https://queue.fal.run/test-model/requests/fluent-request-456/status',
                'cancel_url' => 'https://queue.fal.run/test-model/requests/fluent-request-456/cancel',
            ], 200),
        ]);

        $falAi = new FalAi();

        // Act - Submit using fluent interface
        $response = $falAi
            ->model('test-model')
            ->with([
                'prompt' => 'A beautiful landscape with mountains',
                'image_size' => '1024x1024',
            ])
            ->run();

        // Assert
        expect($response->status())->toBe(200)
            ->and($response->json()['request_id'])->toBe('fluent-request-456')
            ->and($response->json()['response_url'])->toContain('fluent-request-456');
    });
})->group('feature', 'workflow');
