<?php

declare(strict_types=1);

use Cjmellor\FalAi\Drivers\Replicate\Requests\GetPredictionRequest;
use Saloon\Enums\Method;

covers(GetPredictionRequest::class);

describe('GetPredictionRequest', function (): void {

    it('uses GET method', function (): void {
        $request = new GetPredictionRequest('test-prediction-id');

        expect($request->getMethod())->toBe(Method::GET);
    });

    it('resolves correct endpoint with prediction ID', function (): void {
        $request = new GetPredictionRequest('abc123xyz');

        expect($request->resolveEndpoint())->toBe('/v1/predictions/abc123xyz');
    });

    it('handles various prediction ID formats', function (string $predictionId, string $expectedEndpoint): void {
        $request = new GetPredictionRequest($predictionId);

        expect($request->resolveEndpoint())->toBe($expectedEndpoint);
    })->with([
        'simple ID' => ['abc123', '/v1/predictions/abc123'],
        'UUID format' => ['550e8400-e29b-41d4-a716-446655440000', '/v1/predictions/550e8400-e29b-41d4-a716-446655440000'],
        'alphanumeric' => ['prediction123abc', '/v1/predictions/prediction123abc'],
    ]);

});
