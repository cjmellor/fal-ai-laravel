<?php

declare(strict_types=1);

use Cjmellor\FalAi\FalAi;
use Cjmellor\FalAi\Requests\Platform\EstimateCostRequest;
use Cjmellor\FalAi\Requests\Platform\GetPricingRequest;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

beforeEach(function (): void {
    MockClient::destroyGlobal();

    config([
        'fal-ai.api_key' => 'test-api-key',
        'fal-ai.base_url' => 'https://queue.fal.run',
        'fal-ai.platform_base_url' => 'https://api.fal.ai',
    ]);
});

describe('Platform Pricing API', function (): void {

    it('can get pricing for endpoints using fluent interface', function (): void {
        MockClient::global([
            GetPricingRequest::class => MockResponse::make([
                'prices' => [
                    ['endpoint_id' => 'fal-ai/flux/dev', 'unit_price' => 0.025, 'unit' => 'image', 'currency' => 'USD'],
                    ['endpoint_id' => 'fal-ai/flux/schnell', 'unit_price' => 0.003, 'unit' => 'image', 'currency' => 'USD'],
                ],
                'next_cursor' => null,
                'has_more' => false,
            ], 200),
        ]);

        $falAi = new FalAi();

        $response = $falAi->platform()->pricing()
            ->forEndpoints(['fal-ai/flux/dev', 'fal-ai/flux/schnell'])
            ->get();

        expect($response->successful())->toBeTrue()
            ->and($response->prices)->toHaveCount(2)
            ->and($response->prices[0]['endpoint_id'])->toBe('fal-ai/flux/dev')
            ->and($response->prices[0]['unit_price'])->toBe(0.025)
            ->and($response->prices[1]['endpoint_id'])->toBe('fal-ai/flux/schnell')
            ->and($response->hasMore)->toBeFalse()
            ->and($response->nextCursor)->toBeNull();
    });

    it('can get pricing for a single endpoint', function (): void {
        MockClient::global([
            GetPricingRequest::class => MockResponse::make([
                'prices' => [
                    ['endpoint_id' => 'fal-ai/flux/dev', 'unit_price' => 0.025, 'unit' => 'image', 'currency' => 'USD'],
                ],
                'next_cursor' => null,
                'has_more' => false,
            ], 200),
        ]);

        $falAi = new FalAi();

        $response = $falAi->platform()->pricing()
            ->forEndpoint('fal-ai/flux/dev')
            ->get();

        expect($response->successful())->toBeTrue()
            ->and($response->getPriceFor('fal-ai/flux/dev'))->not->toBeNull()
            ->and($response->getUnitPriceFor('fal-ai/flux/dev'))->toBe(0.025);
    });

    it('can chain multiple forEndpoint calls', function (): void {
        MockClient::global([
            GetPricingRequest::class => MockResponse::make([
                'prices' => [
                    ['endpoint_id' => 'fal-ai/flux/dev', 'unit_price' => 0.025, 'unit' => 'image', 'currency' => 'USD'],
                    ['endpoint_id' => 'fal-ai/flux/schnell', 'unit_price' => 0.003, 'unit' => 'image', 'currency' => 'USD'],
                ],
                'next_cursor' => null,
                'has_more' => false,
            ], 200),
        ]);

        $falAi = new FalAi();

        $response = $falAi->platform()->pricing()
            ->forEndpoint('fal-ai/flux/dev')
            ->forEndpoint('fal-ai/flux/schnell')
            ->get();

        expect($response->successful())->toBeTrue()
            ->and($response->prices)->toHaveCount(2);
    });

    it('returns null for non-existent endpoint pricing', function (): void {
        MockClient::global([
            GetPricingRequest::class => MockResponse::make([
                'prices' => [
                    ['endpoint_id' => 'fal-ai/flux/dev', 'unit_price' => 0.025, 'unit' => 'image', 'currency' => 'USD'],
                ],
                'next_cursor' => null,
                'has_more' => false,
            ], 200),
        ]);

        $falAi = new FalAi();

        $response = $falAi->platform()->pricing()
            ->forEndpoints(['fal-ai/flux/dev'])
            ->get();

        expect($response->getPriceFor('non-existent-endpoint'))->toBeNull()
            ->and($response->getUnitPriceFor('non-existent-endpoint'))->toBeNull();
    });

});

describe('Platform Estimate Cost API', function (): void {

    it('can estimate cost using historical api price', function (): void {
        MockClient::global([
            EstimateCostRequest::class => MockResponse::make([
                'estimate_type' => 'historical_api_price',
                'total_cost' => 3.75,
                'currency' => 'USD',
            ], 200),
        ]);

        $falAi = new FalAi();

        $response = $falAi->platform()->estimateCost()
            ->historicalApiPrice()
            ->endpoint('fal-ai/flux/dev', callQuantity: 100)
            ->endpoint('fal-ai/flux/schnell', callQuantity: 50)
            ->estimate();

        expect($response->successful())->toBeTrue()
            ->and($response->estimateType)->toBe('historical_api_price')
            ->and($response->totalCost)->toBe(3.75)
            ->and($response->currency)->toBe('USD');
    });

    it('can estimate cost using unit price', function (): void {
        MockClient::global([
            EstimateCostRequest::class => MockResponse::make([
                'estimate_type' => 'unit_price',
                'total_cost' => 2.50,
                'currency' => 'USD',
            ], 200),
        ]);

        $falAi = new FalAi();

        $response = $falAi->platform()->estimateCost()
            ->unitPrice()
            ->endpoint('fal-ai/flux/dev', unitQuantity: 100)
            ->estimate();

        expect($response->successful())->toBeTrue()
            ->and($response->estimateType)->toBe('unit_price')
            ->and($response->totalCost)->toBe(2.50);
    });

    it('can set multiple endpoints at once', function (): void {
        MockClient::global([
            EstimateCostRequest::class => MockResponse::make([
                'estimate_type' => 'historical_api_price',
                'total_cost' => 3.75,
                'currency' => 'USD',
            ], 200),
        ]);

        $falAi = new FalAi();

        $response = $falAi->platform()->estimateCost()
            ->historicalApiPrice()
            ->endpoints([
                'fal-ai/flux/dev' => ['call_quantity' => 100],
                'fal-ai/flux/schnell' => ['call_quantity' => 50],
            ])
            ->estimate();

        expect($response->successful())->toBeTrue()
            ->and($response->totalCost)->toBe(3.75);
    });

    it('throws exception when no endpoints provided', function (): void {
        $falAi = new FalAi();

        expect(fn () => $falAi->platform()->estimateCost()
            ->historicalApiPrice()
            ->estimate()
        )->toThrow(InvalidArgumentException::class, 'At least one endpoint must be provided');
    });

    it('throws exception when endpoint has no quantity', function (): void {
        $falAi = new FalAi();

        expect(fn () => $falAi->platform()->estimateCost()
            ->historicalApiPrice()
            ->endpoint('fal-ai/flux/dev')
        )->toThrow(InvalidArgumentException::class, 'Either callQuantity or unitQuantity must be provided');
    });

    it('defaults to historical api price estimate type', function (): void {
        $falAi = new FalAi();

        $builder = $falAi->platform()->estimateCost();

        expect($builder->getEstimateType())->toBe('historical_api_price');
    });

});

describe('Platform Pricing Request Builder', function (): void {

    it('throws exception when exceeding 50 endpoint limit with forEndpoints', function (): void {
        $falAi = new FalAi();

        $endpoints = array_map(fn ($i) => "fal-ai/model-{$i}", range(1, 51));

        expect(fn () => $falAi->platform()->pricing()
            ->forEndpoints($endpoints)
        )->toThrow(InvalidArgumentException::class, 'Maximum of 50 endpoint IDs allowed');
    });

    it('throws exception when exceeding 50 endpoint limit with forEndpoint', function (): void {
        $falAi = new FalAi();

        $builder = $falAi->platform()->pricing();

        // Add 50 endpoints
        for ($i = 1; $i <= 50; $i++) {
            $builder->forEndpoint("fal-ai/model-{$i}");
        }

        // 51st should throw
        expect(fn () => $builder->forEndpoint('fal-ai/model-51'))
            ->toThrow(InvalidArgumentException::class, 'Maximum of 50 endpoint IDs allowed');
    });

    it('can retrieve current endpoint ids', function (): void {
        $falAi = new FalAi();

        $builder = $falAi->platform()->pricing()
            ->forEndpoint('fal-ai/flux/dev')
            ->forEndpoint('fal-ai/flux/schnell');

        expect($builder->getEndpointIds())->toBe([
            'fal-ai/flux/dev',
            'fal-ai/flux/schnell',
        ]);
    });

});

describe('Platform Estimate Cost Request Builder', function (): void {

    it('can retrieve current endpoints', function (): void {
        $falAi = new FalAi();

        $builder = $falAi->platform()->estimateCost()
            ->endpoint('fal-ai/flux/dev', callQuantity: 100)
            ->endpoint('fal-ai/flux/schnell', unitQuantity: 50);

        expect($builder->getEndpoints())->toBe([
            'fal-ai/flux/dev' => ['call_quantity' => 100],
            'fal-ai/flux/schnell' => ['unit_quantity' => 50],
        ]);
    });

    it('can switch between estimate types', function (): void {
        $falAi = new FalAi();

        $builder = $falAi->platform()->estimateCost()
            ->historicalApiPrice();
        expect($builder->getEstimateType())->toBe('historical_api_price');

        $builder->unitPrice();
        expect($builder->getEstimateType())->toBe('unit_price');

        $builder->historicalApiPrice();
        expect($builder->getEstimateType())->toBe('historical_api_price');
    });

});
