<?php

declare(strict_types=1);

use Cjmellor\FalAi\Requests\Platform\DeleteRequestPayloadsRequest;
use Saloon\Enums\Method;

beforeEach(function (): void {
    config([
        'fal-ai.api_key' => 'test-api-key',
        'fal-ai.platform_base_url' => 'https://api.fal.ai',
    ]);
});

describe('DeleteRequestPayloadsRequest', function (): void {

    it('uses DELETE method', function (): void {
        $request = new DeleteRequestPayloadsRequest('req_123456789');

        expect($request->getMethod())->toBe(Method::DELETE);
    });

    it('resolves to correct endpoint with request ID', function (): void {
        $request = new DeleteRequestPayloadsRequest('req_123456789');

        expect($request->resolveEndpoint())->toBe('/v1/models/requests/req_123456789/payloads');
    });

    it('resolves endpoint with UUID format request ID', function (): void {
        $request = new DeleteRequestPayloadsRequest('550e8400-e29b-41d4-a716-446655440000');

        expect($request->resolveEndpoint())->toBe('/v1/models/requests/550e8400-e29b-41d4-a716-446655440000/payloads');
    });

    it('adds Idempotency-Key header when set', function (): void {
        $request = new DeleteRequestPayloadsRequest('req_123456789', 'unique-key-123');

        $headers = $request->headers();

        expect($headers->get('Idempotency-Key'))->toBe('unique-key-123');
    });

    it('does not add Idempotency-Key header when not set', function (): void {
        $request = new DeleteRequestPayloadsRequest('req_123456789');

        $headers = $request->headers();

        expect($headers->get('Idempotency-Key'))->toBeNull();
    });

    it('can be constructed with null idempotency key', function (): void {
        $request = new DeleteRequestPayloadsRequest('req_123456789', null);

        $headers = $request->headers();

        expect($headers->get('Idempotency-Key'))->toBeNull();
    });

});
