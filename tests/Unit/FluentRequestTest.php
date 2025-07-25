<?php

declare(strict_types=1);

use Cjmellor\FalAi\FalAi;
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

// Helper function to create fresh FluentRequest instances
function createFluentRequest(): FluentRequest
{
    return new FluentRequest(new FalAi(), 'fal-ai/fast-sdxl');
}

describe('FluentRequest Dynamic Methods', function (): void {
    it('can set data using dynamic methods', function (): void {
        $request = createFluentRequest()->prompt('A beautiful sunset');

        expect($request->toArray())->toBe([
            'prompt' => 'A beautiful sunset',
        ]);
    });

    it('can use queue method in fluent chain', function (): void {
        $request = createFluentRequest()
            ->prompt('Test queue request')
            ->queue()
            ->imageSize('512x512');

        expect($request->toArray())->toBe([
            'prompt' => 'Test queue request',
            'image_size' => '512x512',
        ]);
    });

    it('can use sync method in fluent chain', function (): void {
        $request = createFluentRequest()
            ->prompt('Test sync request')
            ->sync()
            ->numImages(2);

        expect($request->toArray())->toBe([
            'prompt' => 'Test sync request',
            'num_images' => 2,
        ]);
    });

    it('can switch from queue to sync in the same request chain', function (): void {
        $request = createFluentRequest()
            ->prompt('Test switching')
            ->queue()
            ->imageSize('512x512')
            ->sync()
            ->numImages(2);

        expect($request->toArray())->toBe([
            'prompt' => 'Test switching',
            'image_size' => '512x512',
            'num_images' => 2,
        ]);
    });

    it('queue method sets correct queue URL', function (): void {
        $request = createFluentRequest()->queue();

        expect($request->getBaseUrlOverride())->toBe('https://queue.fal.run');
    });

    it('sync method sets correct sync URL', function (): void {
        $request = createFluentRequest()->sync();

        expect($request->getBaseUrlOverride())->toBe('https://fal.run');
    });

    it('converts camelCase methods to snake_case keys', function (): void {
        $request = createFluentRequest()
            ->imageSize('1024x1024')
            ->numInferenceSteps(50)
            ->guidanceScale(7.5);

        expect($request->toArray())->toBe([
            'image_size' => '1024x1024',
            'num_inference_steps' => 50,
            'guidance_scale' => 7.5,
        ]);
    });

    it('can set multiple data values with with() method', function (): void {
        $request = createFluentRequest()->with([
            'prompt' => 'A beautiful sunset',
            'image_size' => '512x512',
            'num_inference_steps' => 25,
        ]);

        expect($request->toArray())->toBe([
            'prompt' => 'A beautiful sunset',
            'image_size' => '512x512',
            'num_inference_steps' => 25,
        ]);
    });
});

describe('FluentRequest Immutable Methods', function (): void {
    it('can create immutable copies with withImmutable()', function (): void {
        $original = createFluentRequest()->prompt('Original prompt');
        $copy = $original->withImmutable(['prompt' => 'New prompt']);

        expect($original->toArray())->toBe(['prompt' => 'Original prompt'])
            ->and($copy->toArray())->toBe(['prompt' => 'New prompt'])
            ->and($original)->not->toBe($copy);
    });

    it('can create immutable copies with dynamic immutable methods', function (): void {
        $original = createFluentRequest()->prompt('Original prompt');
        $copy = $original->promptImmutable('New prompt');

        expect($original->toArray())->toBe(['prompt' => 'Original prompt'])
            ->and($copy->toArray())->toBe(['prompt' => 'New prompt'])
            ->and($original)->not->toBe($copy);
    });

    it('maintains original instance when using immutable methods', function (): void {
        $original = createFluentRequest()
            ->prompt('Original')
            ->imageSize('1024x1024');

        $copy = $original->promptImmutable('Modified');

        expect($original->toArray())->toBe([
            'prompt' => 'Original',
            'image_size' => '1024x1024',
        ])->and($copy->toArray())->toBe([
            'prompt' => 'Modified',
            'image_size' => '1024x1024',
        ]);

    });
});

describe('FluentRequest Chaining', function (): void {
    it('can chain multiple method calls', function (): void {
        $request = createFluentRequest()
            ->prompt('A beautiful landscape')
            ->imageSize('1024x1024')
            ->numInferenceSteps(50)
            ->guidanceScale(7.5)
            ->seed(12345);

        expect($request->toArray())->toBe([
            'prompt' => 'A beautiful landscape',
            'image_size' => '1024x1024',
            'num_inference_steps' => 50,
            'guidance_scale' => 7.5,
            'seed' => 12345,
        ]);
    });

    it('can submit data through fluent interface', function (): void {
        MockClient::global([
            MockResponse::make(['request_id' => 'test-123']),
        ]);

        $response = createFluentRequest()
            ->prompt('Test prompt')
            ->imageSize('512x512')
            ->run();

        expect($response->status())->toBe(200)
            ->and($response->json()['request_id'])->toBe('test-123');
    });
});

describe('FluentRequest Data Handling', function (): void {
    it('returns correct data array', function (): void {
        $request = createFluentRequest()
            ->prompt('Test')
            ->with(['additional' => 'data']);

        expect($request->toArray())->toBe([
            'prompt' => 'Test',
            'additional' => 'data',
        ]);
    });

    it('returns correct JSON', function (): void {
        $request = createFluentRequest()->prompt('Test');

        expect($request->toJson())->toBe(json_encode(['prompt' => 'Test']));
    });
});

describe('FluentRequest Conditional Methods', function (): void {
    it('works with when() method - condition true', function (): void {
        $request = createFluentRequest()
            ->prompt('A beautiful sunset')
            ->when(true, function ($req) {
                return $req->negativePrompt('ugly, blurry');
            });

        expect($request->toArray())->toBe([
            'prompt' => 'A beautiful sunset',
            'negative_prompt' => 'ugly, blurry',
        ]);
    });

    it('works with when() method - condition false', function (): void {
        $request = createFluentRequest()
            ->prompt('A beautiful sunset')
            ->when(false, function ($req) {
                return $req->negativePrompt('ugly, blurry');
            });

        expect($request->toArray())->toBe([
            'prompt' => 'A beautiful sunset',
        ]);
    });

    it('works with unless() method - condition false', function (): void {
        $request = createFluentRequest()
            ->prompt('A beautiful sunset')
            ->unless(false, function ($req) {
                return $req->negativePrompt('ugly, blurry');
            });

        expect($request->toArray())->toBe([
            'prompt' => 'A beautiful sunset',
            'negative_prompt' => 'ugly, blurry',
        ]);
    });

    it('works with unless() method - condition true', function (): void {
        $request = createFluentRequest()
            ->prompt('A beautiful sunset')
            ->unless(true, function ($req) {
                return $req->negativePrompt('ugly, blurry');
            });

        expect($request->toArray())->toBe([
            'prompt' => 'A beautiful sunset',
        ]);
    });
});
