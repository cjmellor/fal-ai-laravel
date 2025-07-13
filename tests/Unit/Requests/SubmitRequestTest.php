<?php

declare(strict_types=1);

use Cjmellor\FalAi\Exceptions\InvalidModelException;
use Cjmellor\FalAi\Requests\SubmitRequest;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;

beforeEach(function (): void {
    // Set up test config
    config([
        'fal-ai.api_key' => 'test-api-key',
        'fal-ai.base_url' => 'https://test.fal.run',
        'fal-ai.default_model' => 'test-default-model',
    ]);
});

describe('SubmitRequest', function (): void {

    it('uses POST method', function (): void {
        $request = new SubmitRequest('test-model', ['prompt' => 'test']);

        expect($request->getMethod())->toBe(Method::POST);
    });

    it('resolves endpoint with explicit model id', function (): void {
        $request = new SubmitRequest('explicit-model', ['prompt' => 'test']);

        expect($request->resolveEndpoint())->toBe('explicit-model');
    });

    it('resolves endpoint with config default model id', function (): void {
        $request = new SubmitRequest(null, ['prompt' => 'test']);

        expect($request->resolveEndpoint())->toBe('test-default-model');
    });

    it('throws InvalidModelException when model id is empty', function (): void {
        // Clear the default model config
        config(['fal-ai.default_model' => '']);

        $request = new SubmitRequest(null, ['prompt' => 'test']);

        expect(fn (): string => $request->resolveEndpoint())
            ->toThrow(InvalidModelException::class, 'Model ID cannot be empty');
    });

    it('throws InvalidModelException when model id is explicitly empty string', function (): void {
        $request = new SubmitRequest('', ['prompt' => 'test']);

        expect(fn (): string => $request->resolveEndpoint())
            ->toThrow(InvalidModelException::class, 'Model ID cannot be empty');
    });

    it('returns correct body data', function (): void {
        $testData = [
            'prompt' => 'A beautiful sunset',
            'image_size' => '1024x1024',
            'num_inference_steps' => 50,
            'guidance_scale' => 7.5,
        ];

        $request = new SubmitRequest('test-model', $testData);

        expect($request->defaultBody())->toBe($testData);
    });

    it('returns empty array when no data provided', function (): void {
        $request = new SubmitRequest('test-model');

        expect($request->defaultBody())->toBe([]);
    });

    it('implements HasBody interface', function (): void {
        $request = new SubmitRequest('test-model', ['prompt' => 'test']);

        expect($request)->toBeInstanceOf(HasBody::class);
    });

    it('can construct with different parameter combinations', function (): void {
        // Test construction with model and data - verify through behavior
        $testData = ['prompt' => 'test prompt'];
        $request = new SubmitRequest('test-model', $testData);

        expect($request->resolveEndpoint())->toBe('test-model')
            ->and($request->defaultBody())->toBe($testData);
    });

    it('can construct with default parameters', function (): void {
        // Test construction with defaults - verify through behavior
        $request = new SubmitRequest();

        expect($request->resolveEndpoint())->toBe('test-default-model')
            ->and($request->defaultBody())->toBe([]);
    });

});
