<?php

declare(strict_types=1);

use Cjmellor\FalAi\FalAi;
use Cjmellor\FalAi\Responses\ResultResponse;
use Cjmellor\FalAi\Responses\StatusResponse;
use Cjmellor\FalAi\Responses\SubmitResponse;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

covers(FalAi::class);
covers(SubmitResponse::class);
covers(StatusResponse::class);
covers(ResultResponse::class);

beforeEach(function (): void {
    MockClient::destroyGlobal();

    config([
        'fal-ai.api_key' => 'test-api-key',
        'fal-ai.base_url' => 'https://test.fal.run',
        'fal-ai.default_model' => 'test-model',
    ]);

    $this->falAi = new FalAi();
});

describe('SubmitResponse Features', function (): void {
    beforeEach(function (): void {
        $this->submitResponseData = [
            'status' => 'IN_QUEUE',
            'request_id' => '8c24b4f5-ae1e-45fc-8858-e0be6efd2ed9',
            'response_url' => 'https://queue.fal.run/fal-ai/flux-1/requests/8c24b4f5-ae1e-45fc-8858-e0be6efd2ed9',
            'status_url' => 'https://queue.fal.run/fal-ai/flux-1/requests/8c24b4f5-ae1e-45fc-8858-e0be6efd2ed9/status',
            'cancel_url' => 'https://queue.fal.run/fal-ai/flux-1/requests/8c24b4f5-ae1e-45fc-8858-e0be6efd2ed9/cancel',
            'logs' => null,
            'metrics' => [],
            'queue_position' => 0,
        ];
    });

    it('provides direct property access to response data', function (): void {
        MockClient::global([
            Cjmellor\FalAi\Requests\SubmitRequest::class => MockResponse::make($this->submitResponseData, 200),
        ]);

        $response = $this->falAi->run(['prompt' => 'a cat'], 'fal-ai/flux-1');

        expect($response)
            ->toBeInstanceOf(SubmitResponse::class)
            ->request_id->toBe('8c24b4f5-ae1e-45fc-8858-e0be6efd2ed9')
            ->status_url->toBe('https://queue.fal.run/fal-ai/flux-1/requests/8c24b4f5-ae1e-45fc-8858-e0be6efd2ed9/status')
            ->cancel_url->toBe('https://queue.fal.run/fal-ai/flux-1/requests/8c24b4f5-ae1e-45fc-8858-e0be6efd2ed9/cancel')
            ->response_url->toBe('https://queue.fal.run/fal-ai/flux-1/requests/8c24b4f5-ae1e-45fc-8858-e0be6efd2ed9');
    });

    it('provides convenience methods for common operations', function (): void {
        MockClient::global([
            Cjmellor\FalAi\Requests\SubmitRequest::class => MockResponse::make($this->submitResponseData, 200),
        ]);

        $response = $this->falAi->run(['prompt' => 'a cat'], 'fal-ai/flux-1');

        expect($response)
            ->getRequestId()->toBe('8c24b4f5-ae1e-45fc-8858-e0be6efd2ed9')
            ->getStatusUrl()->toBe('https://queue.fal.run/fal-ai/flux-1/requests/8c24b4f5-ae1e-45fc-8858-e0be6efd2ed9/status')
            ->getCancelUrl()->toBe('https://queue.fal.run/fal-ai/flux-1/requests/8c24b4f5-ae1e-45fc-8858-e0be6efd2ed9/cancel')
            ->getResponseUrl()->toBe('https://queue.fal.run/fal-ai/flux-1/requests/8c24b4f5-ae1e-45fc-8858-e0be6efd2ed9');
    });

    it('maintains backward compatibility with json() method', function (): void {
        MockClient::global([
            Cjmellor\FalAi\Requests\SubmitRequest::class => MockResponse::make($this->submitResponseData, 200),
        ]);

        $response = $this->falAi->run(['prompt' => 'a cat'], 'fal-ai/flux-1');

        expect($response->json())
            ->toBe($this->submitResponseData)
            ->and($response->status())->toBe(200)
            ->and($response->successful())->toBeTrue();
    });
});

describe('StatusResponse Features', function (): void {
    dataset('status_responses', [
        'in_queue' => ['IN_QUEUE', 5, ['isInQueue' => true, 'isInProgress' => false, 'isCompleted' => false]],
        'in_progress' => ['IN_PROGRESS', null, ['isInQueue' => false, 'isInProgress' => true, 'isCompleted' => false]],
        'completed' => ['COMPLETED', null, ['isInQueue' => false, 'isInProgress' => false, 'isCompleted' => true]],
    ]);

    it('provides fluent status checking methods', function (string $status, ?int $queuePosition, array $expectedMethods): void {
        $statusData = [
            'status' => $status,
            'request_id' => '8c24b4f5-ae1e-45fc-8858-e0be6efd2ed9',
            'response_url' => 'https://queue.fal.run/fal-ai/flux-1/requests/8c24b4f5-ae1e-45fc-8858-e0be6efd2ed9',
            'status_url' => 'https://queue.fal.run/fal-ai/flux-1/requests/8c24b4f5-ae1e-45fc-8858-e0be6efd2ed9/status',
            'cancel_url' => 'https://queue.fal.run/fal-ai/flux-1/requests/8c24b4f5-ae1e-45fc-8858-e0be6efd2ed9/cancel',
            'logs' => null,
            'metrics' => ['inference_time' => 0.3668229579925537],
            'queue_position' => $queuePosition,
        ];

        MockClient::global([
            Cjmellor\FalAi\Requests\FetchRequestStatusRequest::class => MockResponse::make($statusData, 200),
        ]);

        $response = $this->falAi->status('8c24b4f5-ae1e-45fc-8858-e0be6efd2ed9', false, 'fal-ai/flux-1');

        expect($response)
            ->toBeInstanceOf(StatusResponse::class)
            ->status->toBe($status)
            ->queue_position->toBe($queuePosition)
            ->isInQueue()->toBe($expectedMethods['isInQueue'])
            ->isInProgress()->toBe($expectedMethods['isInProgress'])
            ->isCompleted()->toBe($expectedMethods['isCompleted']);
    })->with('status_responses');

    it('provides direct property access and convenience methods', function (): void {
        $statusData = [
            'status' => 'COMPLETED',
            'request_id' => '8c24b4f5-ae1e-45fc-8858-e0be6efd2ed9',
            'response_url' => 'https://queue.fal.run/fal-ai/flux-1/requests/8c24b4f5-ae1e-45fc-8858-e0be6efd2ed9',
            'status_url' => 'https://queue.fal.run/fal-ai/flux-1/requests/8c24b4f5-ae1e-45fc-8858-e0be6efd2ed9/status',
            'cancel_url' => 'https://queue.fal.run/fal-ai/flux-1/requests/8c24b4f5-ae1e-45fc-8858-e0be6efd2ed9/cancel',
            'logs' => null,
            'metrics' => ['inference_time' => 0.3668229579925537],
            'queue_position' => null,
        ];

        MockClient::global([
            Cjmellor\FalAi\Requests\FetchRequestStatusRequest::class => MockResponse::make($statusData, 200),
        ]);

        $response = $this->falAi->status('8c24b4f5-ae1e-45fc-8858-e0be6efd2ed9', false, 'fal-ai/flux-1');

        expect($response)
            ->getQueuePosition()->toBeNull()
            ->getResponseUrl()->toBe('https://queue.fal.run/fal-ai/flux-1/requests/8c24b4f5-ae1e-45fc-8858-e0be6efd2ed9')
            ->getMetrics()->toBe(['inference_time' => 0.3668229579925537])
            ->getLogs()->toBeNull();
    });
});

describe('ResultResponse Features', function (): void {
    beforeEach(function (): void {
        $this->resultData = [
            'images' => [
                [
                    'url' => 'https://v3.fal.media/files/elephant/wda6SlJHUKZWvs1bxa97e.jpeg',
                    'width' => 1024,
                    'height' => 768,
                    'content_type' => 'image/jpeg',
                    'file_name' => 'generated_image.jpeg',
                    'file_size' => 245760,
                ],
            ],
            'timings' => [
                'inference' => 0.13436310458928347,
                'queue' => 0.05,
                'total' => 0.18436310458928347,
            ],
            'seed' => 2131857352,
            'has_nsfw_concepts' => [false],
            'prompt' => 'a photo of a cat',
        ];
    });

    it('provides direct property access to result data', function (): void {
        MockClient::global([
            Cjmellor\FalAi\Requests\GetResultRequest::class => MockResponse::make($this->resultData, 200),
        ]);

        $response = $this->falAi->result('8c24b4f5-ae1e-45fc-8858-e0be6efd2ed9', 'fal-ai/flux-1');

        expect($response)
            ->toBeInstanceOf(ResultResponse::class)
            ->seed->toBe(2131857352)
            ->prompt->toBe('a photo of a cat')
            ->images->toHaveCount(1)
            ->timings->toBe([
                'inference' => 0.13436310458928347,
                'queue' => 0.05,
                'total' => 0.18436310458928347,
            ])
            ->hasNsfwConcepts->toBe([false]);
    });

    dataset('image_access_properties', [
        'firstImageUrl' => 'firstImageUrl',
        'primaryImage' => 'primaryImage',
        'mainImageUrl' => 'mainImageUrl',
        'imageUrl' => 'imageUrl',
    ]);

    it('provides multiple ways to access the primary image', function (string $property): void {
        MockClient::global([
            Cjmellor\FalAi\Requests\GetResultRequest::class => MockResponse::make($this->resultData, 200),
        ]);

        $response = $this->falAi->result('8c24b4f5-ae1e-45fc-8858-e0be6efd2ed9', 'fal-ai/flux-1');
        $expectedUrl = 'https://v3.fal.media/files/elephant/wda6SlJHUKZWvs1bxa97e.jpeg';

        if (in_array($property, ['firstImageUrl', 'mainImageUrl', 'imageUrl'])) {
            expect($response->$property)->toBe($expectedUrl);
        } else {
            expect($response->$property)->toBe($this->resultData['images'][0]);
        }
    })->with('image_access_properties');

    it('provides convenient image metadata access', function (): void {
        MockClient::global([
            Cjmellor\FalAi\Requests\GetResultRequest::class => MockResponse::make($this->resultData, 200),
        ]);

        $response = $this->falAi->result('8c24b4f5-ae1e-45fc-8858-e0be6efd2ed9', 'fal-ai/flux-1');

        expect($response)
            ->images->toHaveCount(1)
            ->width->toBe(1024)
            ->height->toBe(768)
            ->contentType->toBe('image/jpeg')
            ->fileName->toBe('generated_image.jpeg')
            ->fileSize->toBe(245760);
    });

    it('provides convenient generation metadata access', function (): void {
        MockClient::global([
            Cjmellor\FalAi\Requests\GetResultRequest::class => MockResponse::make($this->resultData, 200),
        ]);

        $response = $this->falAi->result('8c24b4f5-ae1e-45fc-8858-e0be6efd2ed9', 'fal-ai/flux-1');

        expect($response)
            ->seed->toBe(2131857352)
            ->prompt->toBe('a photo of a cat')
            ->inferenceTime->toBe(0.13436310458928347)
            ->hasNsfwConcepts->toBe([false])
            ->timings->toBe([
                'inference' => 0.13436310458928347,
                'queue' => 0.05,
                'total' => 0.18436310458928347,
            ]);
    });

    it('handles missing image data gracefully', function (): void {
        $emptyResultData = [
            'images' => [],
            'timings' => ['inference' => 0.1],
            'seed' => 123,
            'has_nsfw_concepts' => [false],
            'prompt' => 'test prompt',
        ];

        MockClient::global([
            Cjmellor\FalAi\Requests\GetResultRequest::class => MockResponse::make($emptyResultData, 200),
        ]);

        $response = $this->falAi->result('test-id', 'fal-ai/flux-1');

        expect($response)
            ->firstImageUrl->toBeNull()
            ->firstImage->toBeNull()
            ->primaryImage->toBeNull()
            ->mainImageUrl->toBeNull()
            ->imageUrl->toBeNull()
            ->width->toBeNull()
            ->height->toBeNull()
            ->contentType->toBeNull()
            ->fileName->toBeNull()
            ->fileSize->toBeNull();
    });
});

describe('Fluent API Integration', function (): void {
    it('works seamlessly with the fluent API', function (): void {
        $submitData = [
            'status' => 'IN_QUEUE',
            'request_id' => '8c24b4f5-ae1e-45fc-8858-e0be6efd2ed9',
            'response_url' => 'https://queue.fal.run/fal-ai/flux-1/requests/8c24b4f5-ae1e-45fc-8858-e0be6efd2ed9',
            'status_url' => 'https://queue.fal.run/fal-ai/flux-1/requests/8c24b4f5-ae1e-45fc-8858-e0be6efd2ed9/status',
            'cancel_url' => 'https://queue.fal.run/fal-ai/flux-1/requests/8c24b4f5-ae1e-45fc-8858-e0be6efd2ed9/cancel',
            'logs' => null,
            'metrics' => [],
            'queue_position' => 0,
        ];

        MockClient::global([
            Cjmellor\FalAi\Requests\SubmitRequest::class => MockResponse::make($submitData, 200),
        ]);

        $response = $this->falAi
            ->model('fal-ai/flux-1/schnell')
            ->prompt('a beautiful sunset')
            ->imageSize('landscape_4_3')
            ->run();

        expect($response)
            ->toBeInstanceOf(SubmitResponse::class)
            ->request_id->toBe('8c24b4f5-ae1e-45fc-8858-e0be6efd2ed9')
            ->getRequestId()->toBe('8c24b4f5-ae1e-45fc-8858-e0be6efd2ed9');
    });
});

describe('Backward Compatibility', function (): void {
    it('maintains full backward compatibility for all response types', function (): void {
        // Submit response
        $submitData = [
            'request_id' => '8c24b4f5-ae1e-45fc-8858-e0be6efd2ed9',
            'status_url' => 'https://example.com/status',
        ];

        // Status response
        $statusData = ['status' => 'COMPLETED'];

        // Result response
        $resultData = ['images' => [], 'seed' => 123];

        // Set up all mocks at once
        MockClient::global([
            Cjmellor\FalAi\Requests\SubmitRequest::class => MockResponse::make($submitData, 200),
            Cjmellor\FalAi\Requests\FetchRequestStatusRequest::class => MockResponse::make($statusData, 200),
            Cjmellor\FalAi\Requests\GetResultRequest::class => MockResponse::make($resultData, 200),
        ]);

        $submitResponse = $this->falAi->run(['prompt' => 'test'], 'fal-ai/flux-1');
        $statusResponse = $this->falAi->status('test-id', false, 'fal-ai/flux-1');
        $resultResponse = $this->falAi->result('test-id', 'fal-ai/flux-1');

        // All old methods still work
        expect($submitResponse->json())->toBe($submitData);
        expect($submitResponse->status())->toBe(200);
        expect($submitResponse->successful())->toBeTrue();

        expect($statusResponse->json())->toBe($statusData);
        expect($statusResponse->status())->toBe(200);
        expect($statusResponse->successful())->toBeTrue();

        expect($resultResponse->json())->toBe($resultData);
        expect($resultResponse->status())->toBe(200);
        expect($resultResponse->successful())->toBeTrue();
    });
});
