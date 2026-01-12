<?php

declare(strict_types=1);

use Cjmellor\FalAi\Drivers\Replicate\Requests\CancelPredictionRequest;
use Saloon\Enums\Method;

covers(CancelPredictionRequest::class);

describe('CancelPredictionRequest', function (): void {

    it('uses POST method', function (): void {
        $request = new CancelPredictionRequest('test-prediction-id');

        expect($request->getMethod())->toBe(Method::POST);
    });

    it('resolves correct endpoint with cancel suffix', function (): void {
        $request = new CancelPredictionRequest('abc123xyz');

        expect($request->resolveEndpoint())->toBe('/v1/predictions/abc123xyz/cancel');
    });

    it('handles various prediction ID formats', function (string $predictionId, string $expectedEndpoint): void {
        $request = new CancelPredictionRequest($predictionId);

        expect($request->resolveEndpoint())->toBe($expectedEndpoint);
    })->with([
        'simple ID' => ['abc123', '/v1/predictions/abc123/cancel'],
        'UUID format' => ['550e8400-e29b-41d4-a716-446655440000', '/v1/predictions/550e8400-e29b-41d4-a716-446655440000/cancel'],
        'alphanumeric' => ['prediction123abc', '/v1/predictions/prediction123abc/cancel'],
    ]);

});
