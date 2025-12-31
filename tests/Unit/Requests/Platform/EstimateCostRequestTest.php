<?php

declare(strict_types=1);

use Cjmellor\FalAi\Requests\Platform\EstimateCostRequest;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;

beforeEach(function (): void {
    config([
        'fal-ai.api_key' => 'test-api-key',
        'fal-ai.platform_base_url' => 'https://api.fal.ai',
    ]);
});

describe('EstimateCostRequest', function (): void {

    it('uses POST method', function (): void {
        $request = new EstimateCostRequest('historical_api_price', [
            'fal-ai/flux/dev' => ['call_quantity' => 100],
        ]);

        expect($request->getMethod())->toBe(Method::POST);
    });

    it('implements HasBody interface', function (): void {
        $request = new EstimateCostRequest('historical_api_price', [
            'fal-ai/flux/dev' => ['call_quantity' => 100],
        ]);

        expect($request)->toBeInstanceOf(HasBody::class);
    });

    it('resolves to correct endpoint', function (): void {
        $request = new EstimateCostRequest('historical_api_price', []);

        expect($request->resolveEndpoint())->toBe('/v1/models/pricing/estimate');
    });

    it('builds body with historical api price estimate type', function (): void {
        $request = new EstimateCostRequest('historical_api_price', [
            'fal-ai/flux/dev' => ['call_quantity' => 100],
            'fal-ai/flux/schnell' => ['call_quantity' => 50],
        ]);

        $body = $request->defaultBody();

        expect($body)->toBe([
            'estimate_type' => 'historical_api_price',
            'endpoints' => [
                'fal-ai/flux/dev' => ['call_quantity' => 100],
                'fal-ai/flux/schnell' => ['call_quantity' => 50],
            ],
        ]);
    });

    it('builds body with unit price estimate type', function (): void {
        $request = new EstimateCostRequest('unit_price', [
            'fal-ai/flux/dev' => ['unit_quantity' => 100],
        ]);

        $body = $request->defaultBody();

        expect($body)->toBe([
            'estimate_type' => 'unit_price',
            'endpoints' => [
                'fal-ai/flux/dev' => ['unit_quantity' => 100],
            ],
        ]);
    });

    it('can handle empty endpoints array', function (): void {
        $request = new EstimateCostRequest('historical_api_price', []);

        $body = $request->defaultBody();

        expect($body)->toBe([
            'estimate_type' => 'historical_api_price',
            'endpoints' => [],
        ]);
    });

});
