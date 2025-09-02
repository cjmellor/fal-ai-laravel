<?php

declare(strict_types=1);

use Cjmellor\FalAi\Exceptions\InvalidModelException;
use Cjmellor\FalAi\Requests\StreamRequest;
use Saloon\Enums\Method;

beforeEach(function (): void {
    config([
        'fal-ai.default_model' => 'test-model',
    ]);
});

describe('StreamRequest', function (): void {
    it('uses POST method', function (): void {
        $request = new StreamRequest('test-model', ['prompt' => 'test']);
        
        expect($request->getMethod())->toBe(Method::POST);
    });

    it('resolves endpoint with /stream suffix', function (): void {
        $request = new StreamRequest('fal-ai/flux-1', ['prompt' => 'test']);
        
        expect($request->resolveEndpoint())->toBe('fal-ai/flux-1/stream');
    });

    it('uses default model from config when model is null', function (): void {
        $request = new StreamRequest(null, ['prompt' => 'test']);
        
        expect($request->resolveEndpoint())->toBe('test-model/stream');
    });

    it('throws exception when model is empty', function (): void {
        config(['fal-ai.default_model' => '']);

        $request = new StreamRequest(null, ['prompt' => 'test']);
        
        expect(fn() => $request->resolveEndpoint())
            ->toThrow(InvalidModelException::class, 'Model ID cannot be empty');
    });

    it('includes correct request data in body', function (): void {
        $data = ['prompt' => 'test prompt', 'image_size' => '512x512'];

        $request = new StreamRequest('test-model', $data);
        
        expect($request->defaultBody())->toBe($data);
    });

    it('has server-sent events headers', function (): void {
        $request = new StreamRequest('test-model', ['prompt' => 'test']);
        
        $headers = $request->headers()->all();
        
        expect($headers)->toHaveKey('Accept')
            ->and($headers['Accept'])->toContain('text/event-stream');
    });
});