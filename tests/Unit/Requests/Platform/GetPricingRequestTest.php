<?php

declare(strict_types=1);

use Cjmellor\FalAi\Requests\Platform\GetPricingRequest;
use Saloon\Enums\Method;

beforeEach(function (): void {
    config([
        'fal-ai.api_key' => 'test-api-key',
        'fal-ai.platform_base_url' => 'https://api.fal.ai',
    ]);
});

describe('GetPricingRequest', function (): void {

    it('uses GET method', function (): void {
        $request = new GetPricingRequest(['fal-ai/flux/dev']);

        expect($request->getMethod())->toBe(Method::GET);
    });

    it('resolves to correct endpoint', function (): void {
        $request = new GetPricingRequest(['fal-ai/flux/dev']);

        expect($request->resolveEndpoint())->toBe('/v1/models/pricing');
    });

    it('builds query with single endpoint id', function (): void {
        $request = new GetPricingRequest(['fal-ai/flux/dev']);

        $query = $request->query();

        expect($query->get('endpoint_id'))->toBe('fal-ai/flux/dev');
    });

    it('builds query with multiple endpoint ids', function (): void {
        $request = new GetPricingRequest([
            'fal-ai/flux/dev',
            'fal-ai/flux/schnell',
            'fal-ai/fast-sdxl',
        ]);

        $query = $request->query();

        expect($query->get('endpoint_id'))->toBe('fal-ai/flux/dev,fal-ai/flux/schnell,fal-ai/fast-sdxl');
    });

    it('returns empty query when no endpoint ids provided', function (): void {
        $request = new GetPricingRequest([]);

        $query = $request->query();

        expect($query->all())->toBe([]);
    });

    it('can be constructed with default empty array', function (): void {
        $request = new GetPricingRequest();

        expect($request->resolveEndpoint())->toBe('/v1/models/pricing')
            ->and($request->query()->all())->toBe([]);
    });

});
