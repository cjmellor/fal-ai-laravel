<?php

declare(strict_types=1);

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

// Note: createFluentRequest() function is now defined globally in tests/Pest.php

describe('FluentRequest Dynamic Methods', function (): void {
    it('handles dynamic methods with proper conversion and chaining', function (string $method, mixed $value, string $expectedKey): void {
        $request = createFluentRequest()->{$method}($value);

        expect($request)
            ->toBeInstanceOf(FluentRequest::class)
            ->toArray()->toHaveKey($expectedKey)
            ->and($request->toArray()[$expectedKey])->toBe($value)
            ->and($request->toJson())->toContain(json_encode($value));
    })->with([
        'prompt' => ['prompt', 'A beautiful sunset', 'prompt'],
        'imageSize' => ['imageSize', '1024x1024', 'image_size'],
        'numInferenceSteps' => ['numInferenceSteps', 50, 'num_inference_steps'],
        'guidanceScale' => ['guidanceScale', 7.5, 'guidance_scale'],
        'negativePrompt' => ['negativePrompt', 'ugly, blurry', 'negative_prompt'],
        'numImages' => ['numImages', 4, 'num_images'],
        'seed' => ['seed', 12345, 'seed'],
    ]);

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

    it('sets correct base URLs for different execution modes', function (string $method, string $expectedUrl): void {
        $request = createFluentRequest()->{$method}();

        expect($request)
            ->toBeInstanceOf(FluentRequest::class)
            ->getBaseUrlOverride()->toBe($expectedUrl)
            ->and($request->toArray())->toBeArray();
    })->with([
        'queue method' => ['queue', 'https://queue.fal.run'],
        'sync method' => ['sync', 'https://fal.run'],
    ]);

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
    it('creates proper immutable copies maintaining original state', function (string $method, mixed $originalValue, mixed $newValue): void {
        $original = createFluentRequest()->{$method}($originalValue);
        $immutableMethod = $method.'Immutable';
        $copy = $original->{$immutableMethod}($newValue);

        expect($original)
            ->not->toBe($copy)
            ->toBeInstanceOf(FluentRequest::class)
            ->toArray()->toContain($originalValue)
            ->and($copy)
            ->toBeInstanceOf(FluentRequest::class)
            ->toArray()->toContain($newValue)
            ->and($original->toArray())->not->toContain($newValue)
            ->and($copy->toArray())->not->toContain($originalValue);
    })->with([
        'prompt' => ['prompt', 'Original prompt', 'New prompt'],
        'imageSize' => ['imageSize', '512x512', '1024x1024'],
        'seed' => ['seed', 12345, 67890],
        'guidanceScale' => ['guidanceScale', 6.0, 8.5],
    ]);

    it('supports withImmutable for bulk updates', function (): void {
        $original = createFluentRequest()->prompt('Original prompt');
        $copy = $original->withImmutable(['prompt' => 'New prompt', 'seed' => 999]);

        expect($original)
            ->not->toBe($copy)
            ->toArray()->toBe(['prompt' => 'Original prompt'])
            ->and($copy->toArray())->toBe(['prompt' => 'New prompt', 'seed' => 999]);
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
    it('handles conditional methods correctly based on conditions', function (string $method, bool $condition, bool $shouldExecute): void {
        $executed = false;
        $testKey = 'conditional_test';
        $testValue = 'executed';

        $request = createFluentRequest()
            ->prompt('Base prompt')
            ->{$method}($condition, function ($req) use (&$executed, $testKey, $testValue) {
                $executed = true;

                return $req->with([$testKey => $testValue]);
            });

        expect($executed)->toBe($shouldExecute)
            ->and($request->toArray())
            ->when($shouldExecute, fn ($arr) => $arr->toHaveKey($testKey))
            ->when(! $shouldExecute, fn ($arr) => $arr->not->toHaveKey($testKey))
            ->toHaveKey('prompt'); // Base prompt should always be there
    })->with([
        'when true executes' => ['when', true, true],
        'when false skips' => ['when', false, false],
        'unless true skips' => ['unless', true, false],
        'unless false executes' => ['unless', false, true],
    ]);

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
