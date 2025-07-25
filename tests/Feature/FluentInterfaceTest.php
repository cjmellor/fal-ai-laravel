<?php

declare(strict_types=1);

use Cjmellor\FalAi\FalAi;
use Cjmellor\FalAi\Requests\SubmitRequest;
use Cjmellor\FalAi\Support\FluentRequest;
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

describe('Fluent Interface Feature Tests', function (): void {

    it('can build complex requests using fluent interface', function (): void {
        MockClient::global([
            SubmitRequest::class => MockResponse::make([
                'request_id' => 'complex-request-123',
                'response_url' => 'https://queue.fal.run/test-model/requests/complex-request-123',
            ], 200),
        ]);

        $falAi = new FalAi();

        $response = $falAi
            ->model('test-model')
            ->prompt('A majestic dragon soaring through storm clouds')
            ->imageSize('landscape_4_3')
            ->numImages(2)
            ->seed(42)
            ->guidanceScale(7.5)
            ->numInferenceSteps(50)
            ->negativePrompt('blurry, low quality')
            ->run();

        expect($response->status())->toBe(200)
            ->and($response->json()['request_id'])->toBe('complex-request-123');
    });

    it('can create immutable request templates', function (): void {
        $falAi = new FalAi();

        // Create a base template
        $baseTemplate = $falAi
            ->model('test-model')
            ->imageSize('square_hd')
            ->numImages(1)
            ->guidanceScale(7.0);

        // Create variations using immutable methods
        $dragonRequest = $baseTemplate->promptImmutable('A fierce dragon');
        $unicornRequest = $baseTemplate->promptImmutable('A magical unicorn');

        expect($baseTemplate->toArray())->not->toHaveKey('prompt')
            ->and($dragonRequest->toArray()['prompt'])->toBe('A fierce dragon')
            ->and($unicornRequest->toArray()['prompt'])->toBe('A magical unicorn')
            ->and($dragonRequest->toArray()['image_size'])->toBe('square_hd')
            ->and($unicornRequest->toArray()['image_size'])->toBe('square_hd');
    });

    it('can use conditional fluent methods', function (): void {
        $falAi = new FalAi();
        $useHighQuality = true;
        $addNegativePrompt = false;

        $request = $falAi
            ->model('test-model')
            ->prompt('A beautiful sunset')
            ->when($useHighQuality, function ($req) {
                return $req->numInferenceSteps(100)->guidanceScale(8.0);
            })
            ->when($addNegativePrompt, function ($req) {
                return $req->negativePrompt('ugly, blurry');
            });

        $data = $request->toArray();

        expect($data['prompt'])->toBe('A beautiful sunset')
            ->and($data['num_inference_steps'])->toBe(100)
            ->and($data['guidance_scale'])->toBe(8.0)
            ->and($data)->not->toHaveKey('negative_prompt');
    });

    it('can chain multiple operations', function (): void {
        MockClient::global([
            SubmitRequest::class => MockResponse::make([
                'request_id' => 'chained-request-456',
                'response_url' => 'https://queue.fal.run/test-model/requests/chained-request-456',
            ], 200),
        ]);

        $falAi = new FalAi();

        // Chain multiple operations in a single fluent call
        $response = $falAi
            ->model('test-model')
            ->with([
                'prompt' => 'Base prompt',
                'image_size' => '1024x1024',
            ])
            ->guidanceScale(7.5)
            ->when(true, function ($req) {
                return $req->seed(12345);
            })
            ->numInferenceSteps(30)
            ->run();

        expect($response->status())->toBe(200)
            ->and($response->json()['request_id'])->toBe('chained-request-456');
    });

    it('can handle dynamic method conversion', function (): void {
        $falAi = new FalAi();

        $request = $falAi
            ->model('test-model')
            ->prompt('Test prompt')
            ->imageSize('512x512')
            ->numImages(3)
            ->guidanceScale(6.5)
            ->seed(98765);

        $data = $request->toArray();

        expect($data['prompt'])->toBe('Test prompt')
            ->and($data['image_size'])->toBe('512x512')
            ->and($data['num_images'])->toBe(3)
            ->and($data['guidance_scale'])->toBe(6.5)
            ->and($data['seed'])->toBe(98765);
    });

    it('can build template patterns for reuse', function (): void {
        $falAi = new FalAi();

        // Create a high-quality template
        $highQualityTemplate = $falAi
            ->model('test-model')
            ->imageSize('landscape_4_3')
            ->numInferenceSteps(100)
            ->guidanceScale(8.0)
            ->numImages(1);

        // Create a fast template
        $fastTemplate = $falAi
            ->model('test-model')
            ->imageSize('square')
            ->numInferenceSteps(20)
            ->guidanceScale(6.0)
            ->numImages(4);

        // Use templates with different prompts
        $highQualityData = $highQualityTemplate
            ->promptImmutable('Detailed artwork')
            ->toArray();

        $fastData = $fastTemplate
            ->promptImmutable('Quick sketch')
            ->toArray();

        expect($highQualityData['num_inference_steps'])->toBe(100)
            ->and($highQualityData['num_images'])->toBe(1)
            ->and($fastData['num_inference_steps'])->toBe(20)
            ->and($fastData['num_images'])->toBe(4)
            ->and($highQualityData['prompt'])->toBe('Detailed artwork')
            ->and($fastData['prompt'])->toBe('Quick sketch');
    });

    it('maintains fluent interface consistency', function (): void {
        $falAi = new FalAi();

        $request = $falAi->model('test-model');

        // Every fluent method should return FluentRequest instance
        expect($request->prompt('Test'))->toBeInstanceOf(FluentRequest::class)
            ->and($request->imageSize('512x512'))->toBeInstanceOf(FluentRequest::class)
            ->and($request->with(['key' => 'value']))->toBeInstanceOf(FluentRequest::class)
            ->and($request->when(true, fn ($r): FluentRequest => $r))->toBeInstanceOf(FluentRequest::class)
            ->and($request->queue())->toBeInstanceOf(FluentRequest::class)
            ->and($request->sync())->toBeInstanceOf(FluentRequest::class);
    });

    it('can use queue method to explicitly set queue endpoint', function (): void {
        MockClient::global([
            SubmitRequest::class => MockResponse::make([
                'request_id' => 'queue-request-123',
                'response_url' => 'https://queue.fal.run/test-model/requests/queue-request-123',
            ], 200),
        ]);

        $falAi = new FalAi();

        $response = $falAi
            ->model('test-model')
            ->queue()
            ->prompt('A beautiful landscape')
            ->run();

        expect($response->status())->toBe(200)
            ->and($response->json()['request_id'])->toBe('queue-request-123');
    });

    it('can use sync method to set sync endpoint', function (): void {
        MockClient::global([
            SubmitRequest::class => MockResponse::make([
                'request_id' => 'sync-request-456',
                'response_url' => 'https://fal.run/test-model/requests/sync-request-456',
            ], 200),
        ]);

        $falAi = new FalAi();

        $response = $falAi
            ->model('test-model')
            ->sync()
            ->prompt('A quick generation')
            ->run();

        expect($response->status())->toBe(200)
            ->and($response->json()['request_id'])->toBe('sync-request-456');
    });

    it('can chain queue and sync methods with other fluent methods', function (): void {
        MockClient::global([
            SubmitRequest::class => MockResponse::make([
                'request_id' => 'chained-queue-789',
                'response_url' => 'https://queue.fal.run/test-model/requests/chained-queue-789',
            ], 200),
        ]);

        $falAi = new FalAi();

        $response = $falAi
            ->model('test-model')
            ->prompt('Complex chained request')
            ->imageSize('landscape_4_3')
            ->queue()
            ->numImages(2)
            ->guidanceScale(7.5)
            ->run();

        expect($response->status())->toBe(200)
            ->and($response->json()['request_id'])->toBe('chained-queue-789');
    });

    it('maintains backward compatibility when no queue/sync method is called', function (): void {
        MockClient::global([
            SubmitRequest::class => MockResponse::make([
                'request_id' => 'default-request-000',
                'response_url' => 'https://test.fal.run/test-model/requests/default-request-000',
            ], 200),
        ]);

        $falAi = new FalAi();

        // This should work exactly as before
        $response = $falAi
            ->model('test-model')
            ->prompt('Default behavior test')
            ->run();

        expect($response->status())->toBe(200)
            ->and($response->json()['request_id'])->toBe('default-request-000');
    });

    it('allows switching between queue and sync in the same chain', function (): void {
        $falAi = new FalAi();

        $request = $falAi
            ->model('test-model')
            ->prompt('Test prompt')
            ->queue()
            ->imageSize('512x512')
            ->sync(); // This should override the queue setting

        // We can't easily test the actual URL without mocking deeper,
        // but we can verify the methods are chainable
        expect($request)->toBeInstanceOf(FluentRequest::class);
    });

})->group('feature', 'fluent-interface');
