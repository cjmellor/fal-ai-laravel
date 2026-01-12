<?php

declare(strict_types=1);

use Cjmellor\FalAi\Drivers\Replicate\Requests\CreatePredictionRequest;
use Cjmellor\FalAi\Exceptions\InvalidModelException;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;

beforeEach(function (): void {
    config([
        'fal-ai.drivers.replicate.default_model' => 'owner/model:abc123def456version',
    ]);
});

describe('CreatePredictionRequest', function (): void {

    it('uses POST method', function (): void {
        $request = new CreatePredictionRequest('abc123version', ['audio' => 'https://example.com/audio.mp3']);

        expect($request->getMethod())->toBe(Method::POST);
    });

    it('resolves endpoint to /v1/predictions', function (): void {
        $request = new CreatePredictionRequest('abc123version', []);

        expect($request->resolveEndpoint())->toBe('/v1/predictions');
    });

    it('returns body with version and input', function (): void {
        $input = ['audio' => 'https://example.com/audio.mp3'];
        $request = new CreatePredictionRequest('abc123version', $input);

        $body = $request->defaultBody();

        expect($body)->toHaveKey('version')
            ->and($body['version'])->toBe('abc123version')
            ->and($body)->toHaveKey('input')
            ->and($body['input'])->toBe($input);
    });

    it('extracts version id from owner/model:version format', function (): void {
        $request = new CreatePredictionRequest('owner/model:abc123def456version', ['audio' => 'test']);

        $body = $request->defaultBody();

        expect($body['version'])->toBe('abc123def456version');
    });

    it('uses version directly when no colon present', function (): void {
        $request = new CreatePredictionRequest('abc123def456version', ['audio' => 'test']);

        $body = $request->defaultBody();

        expect($body['version'])->toBe('abc123def456version');
    });

    it('falls back to config default model when version is null', function (): void {
        $request = new CreatePredictionRequest(null, ['audio' => 'test']);

        $body = $request->defaultBody();

        expect($body['version'])->toBe('abc123def456version');
    });

    it('throws InvalidModelException when version is empty', function (): void {
        config(['fal-ai.drivers.replicate.default_model' => '']);

        $request = new CreatePredictionRequest(null, ['audio' => 'test']);

        expect(fn () => $request->defaultBody())
            ->toThrow(InvalidModelException::class, 'Model version cannot be empty');
    });

    it('includes webhook url in body when provided', function (): void {
        $request = new CreatePredictionRequest(
            version: 'abc123version',
            input: ['audio' => 'test'],
            webhookUrl: 'https://example.com/webhook',
        );

        $body = $request->defaultBody();

        expect($body)->toHaveKey('webhook')
            ->and($body['webhook'])->toBe('https://example.com/webhook');
    });

    it('excludes webhook from body when not provided', function (): void {
        $request = new CreatePredictionRequest('abc123version', ['audio' => 'test']);

        $body = $request->defaultBody();

        expect($body)->not->toHaveKey('webhook');
    });

    it('includes webhook events filter when provided', function (): void {
        $request = new CreatePredictionRequest(
            version: 'abc123version',
            input: ['audio' => 'test'],
            webhookUrl: 'https://example.com/webhook',
            webhookEventsFilter: ['completed'],
        );

        $body = $request->defaultBody();

        expect($body)->toHaveKey('webhook_events_filter')
            ->and($body['webhook_events_filter'])->toBe(['completed']);
    });

    it('excludes webhook events filter when empty', function (): void {
        $request = new CreatePredictionRequest(
            version: 'abc123version',
            input: ['audio' => 'test'],
            webhookUrl: null,
            webhookEventsFilter: [],
        );

        $body = $request->defaultBody();

        expect($body)->not->toHaveKey('webhook_events_filter');
    });

    it('implements HasBody interface', function (): void {
        $request = new CreatePredictionRequest('abc123version', ['audio' => 'test']);

        expect($request)->toBeInstanceOf(HasBody::class);
    });

});
