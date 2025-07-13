<?php

declare(strict_types=1);

use Cjmellor\FalAi\Exceptions\InvalidModelException;
use Cjmellor\FalAi\Requests\GetResultRequest;
use Saloon\Enums\Method;

beforeEach(function (): void {
    // Set up test config
    config([
        'fal-ai.api_key' => 'test-api-key',
        'fal-ai.base_url' => 'https://test.fal.run',
        'fal-ai.default_model' => 'test-default-model',
    ]);
});

describe('GetResultRequest', function (): void {

    it('uses GET method', function (): void {
        $request = new GetResultRequest('test-request-id', 'test-model');

        expect($request->getMethod())->toBe(Method::GET);
    });

    it('resolves endpoint with request id and model id', function (): void {
        $request = new GetResultRequest('test-request-123', 'explicit-model');

        expect($request->resolveEndpoint())->toBe('explicit-model/requests/test-request-123');
    });

    it('resolves endpoint with config default model id', function (): void {
        $request = new GetResultRequest('test-request-456', null);

        expect($request->resolveEndpoint())->toBe('test-default-model/requests/test-request-456');
    });

    it('throws InvalidModelException when model id is empty', function (): void {
        // Clear the default model config
        config(['fal-ai.default_model' => '']);

        $request = new GetResultRequest('test-request-id', null);

        expect(fn (): string => $request->resolveEndpoint())
            ->toThrow(InvalidModelException::class, 'Model ID cannot be empty');
    });

    it('throws InvalidModelException when model id is explicitly empty string', function (): void {
        $request = new GetResultRequest('test-request-id', '');

        expect(fn (): string => $request->resolveEndpoint())
            ->toThrow(InvalidModelException::class, 'Model ID cannot be empty');
    });

    it('can construct with different parameter combinations', function (): void {
        // Test construction with all parameters - verify through behavior
        $request = new GetResultRequest('test-request-id', 'test-model');

        expect($request->resolveEndpoint())->toBe('test-model/requests/test-request-id');
    });

    it('can construct with default parameters', function (): void {
        // Test construction with defaults - verify through behavior
        $request = new GetResultRequest('test-request-id');

        expect($request->resolveEndpoint())->toBe('test-default-model/requests/test-request-id');
    });

});
