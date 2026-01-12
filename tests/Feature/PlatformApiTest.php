<?php

declare(strict_types=1);

use Cjmellor\FalAi\Drivers\Fal\FalDriver;
use Cjmellor\FalAi\Requests\Platform\DeleteRequestPayloadsRequest;
use Cjmellor\FalAi\Requests\Platform\EstimateCostRequest;
use Cjmellor\FalAi\Requests\Platform\GetAnalyticsRequest;
use Cjmellor\FalAi\Requests\Platform\GetPricingRequest;
use Cjmellor\FalAi\Requests\Platform\GetUsageRequest;
use Saloon\Http\Faking\MockResponse;
use Saloon\Laravel\Facades\Saloon;

beforeEach(function (): void {
    config([
        'fal-ai.drivers.fal.api_key' => 'test-api-key',
        'fal-ai.drivers.fal.base_url' => 'https://queue.fal.run',
        'fal-ai.drivers.fal.sync_url' => 'https://fal.run',
        'fal-ai.drivers.fal.platform_base_url' => 'https://api.fal.ai',
    ]);
});

function createFalDriverForPlatformTests(): FalDriver
{
    return new FalDriver([
        'api_key' => 'test-api-key',
        'base_url' => 'https://queue.fal.run',
        'sync_url' => 'https://fal.run',
        'platform_base_url' => 'https://api.fal.ai',
        'default_model' => 'test-model',
    ]);
}

describe('Platform Pricing API', function (): void {

    it('can get pricing for endpoints using fluent interface', function (): void {
        Saloon::fake([
            GetPricingRequest::class => MockResponse::fixture('Platform/pricing-multiple-endpoints'),
        ]);

        $driver = createFalDriverForPlatformTests();

        $response = $driver->platform()->pricing()
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
        Saloon::fake([
            GetPricingRequest::class => MockResponse::fixture('Platform/pricing-single-endpoint'),
        ]);

        $driver = createFalDriverForPlatformTests();

        $response = $driver->platform()->pricing()
            ->forEndpoint('fal-ai/flux/dev')
            ->get();

        expect($response->successful())->toBeTrue()
            ->and($response->getPriceFor('fal-ai/flux/dev'))->not->toBeNull()
            ->and($response->getUnitPriceFor('fal-ai/flux/dev'))->toBe(0.025);
    });

    it('can chain multiple forEndpoint calls', function (): void {
        Saloon::fake([
            GetPricingRequest::class => MockResponse::fixture('Platform/pricing-multiple-endpoints'),
        ]);

        $driver = createFalDriverForPlatformTests();

        $response = $driver->platform()->pricing()
            ->forEndpoint('fal-ai/flux/dev')
            ->forEndpoint('fal-ai/flux/schnell')
            ->get();

        expect($response->successful())->toBeTrue()
            ->and($response->prices)->toHaveCount(2);
    });

    it('returns null for non-existent endpoint pricing', function (): void {
        Saloon::fake([
            GetPricingRequest::class => MockResponse::fixture('Platform/pricing-single-endpoint'),
        ]);

        $driver = createFalDriverForPlatformTests();

        $response = $driver->platform()->pricing()
            ->forEndpoints(['fal-ai/flux/dev'])
            ->get();

        expect($response->getPriceFor('non-existent-endpoint'))->toBeNull()
            ->and($response->getUnitPriceFor('non-existent-endpoint'))->toBeNull();
    });

});

describe('Platform Estimate Cost API', function (): void {

    it('can estimate cost using historical api price', function (): void {
        Saloon::fake([
            EstimateCostRequest::class => MockResponse::fixture('Platform/estimate-historical-api-price'),
        ]);

        $driver = createFalDriverForPlatformTests();

        $response = $driver->platform()->estimateCost()
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
        Saloon::fake([
            EstimateCostRequest::class => MockResponse::fixture('Platform/estimate-unit-price'),
        ]);

        $driver = createFalDriverForPlatformTests();

        $response = $driver->platform()->estimateCost()
            ->unitPrice()
            ->endpoint('fal-ai/flux/dev', unitQuantity: 100)
            ->estimate();

        expect($response->successful())->toBeTrue()
            ->and($response->estimateType)->toBe('unit_price')
            ->and($response->totalCost)->toBe(2.50);
    });

    it('can set multiple endpoints at once', function (): void {
        Saloon::fake([
            EstimateCostRequest::class => MockResponse::fixture('Platform/estimate-historical-api-price'),
        ]);

        $driver = createFalDriverForPlatformTests();

        $response = $driver->platform()->estimateCost()
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
        $driver = createFalDriverForPlatformTests();

        expect(fn (): Cjmellor\FalAi\Responses\EstimateCostResponse => $driver->platform()->estimateCost()
            ->historicalApiPrice()
            ->estimate()
        )->toThrow(InvalidArgumentException::class, 'At least one endpoint must be provided');
    });

    it('throws exception when endpoint has no quantity', function (): void {
        $driver = createFalDriverForPlatformTests();

        expect(fn (): Cjmellor\FalAi\Support\EstimateCostRequest => $driver->platform()->estimateCost()
            ->historicalApiPrice()
            ->endpoint('fal-ai/flux/dev')
        )->toThrow(InvalidArgumentException::class, 'Either callQuantity or unitQuantity must be provided');
    });

    it('defaults to historical api price estimate type', function (): void {
        $driver = createFalDriverForPlatformTests();

        $builder = $driver->platform()->estimateCost();

        expect($builder->estimateType)->toBe('historical_api_price');
    });

});

describe('Platform Pricing Request Builder', function (): void {

    it('throws exception when exceeding 50 endpoint limit with forEndpoints', function (): void {
        $driver = createFalDriverForPlatformTests();

        $endpoints = array_map(fn (int $i): string => "fal-ai/model-{$i}", range(1, 51));

        expect(fn (): Cjmellor\FalAi\Support\PricingRequest => $driver->platform()->pricing()
            ->forEndpoints($endpoints)
        )->toThrow(InvalidArgumentException::class, 'Maximum of 50 endpoint IDs allowed');
    });

    it('throws exception when exceeding 50 endpoint limit with forEndpoint', function (): void {
        $driver = createFalDriverForPlatformTests();

        $builder = $driver->platform()->pricing();

        // Add 50 endpoints
        for ($i = 1; $i <= 50; $i++) {
            $builder->forEndpoint("fal-ai/model-{$i}");
        }

        // 51st should throw
        expect(fn (): Cjmellor\FalAi\Support\PricingRequest => $builder->forEndpoint('fal-ai/model-51'))
            ->toThrow(InvalidArgumentException::class, 'Maximum of 50 endpoint IDs allowed');
    });

    it('can retrieve current endpoint ids', function (): void {
        $driver = createFalDriverForPlatformTests();

        $builder = $driver->platform()->pricing()
            ->forEndpoint('fal-ai/flux/dev')
            ->forEndpoint('fal-ai/flux/schnell');

        expect($builder->endpointIds)->toBe([
            'fal-ai/flux/dev',
            'fal-ai/flux/schnell',
        ]);
    });

});

describe('Platform Estimate Cost Request Builder', function (): void {

    it('can retrieve current endpoints', function (): void {
        $driver = createFalDriverForPlatformTests();

        $builder = $driver->platform()->estimateCost()
            ->endpoint('fal-ai/flux/dev', callQuantity: 100)
            ->endpoint('fal-ai/flux/schnell', unitQuantity: 50);

        expect($builder->configuredEndpoints)->toBe([
            'fal-ai/flux/dev' => ['call_quantity' => 100],
            'fal-ai/flux/schnell' => ['unit_quantity' => 50],
        ]);
    });

    it('can switch between estimate types', function (): void {
        $driver = createFalDriverForPlatformTests();

        $builder = $driver->platform()->estimateCost()
            ->historicalApiPrice();
        expect($builder->estimateType)->toBe('historical_api_price');

        $builder->unitPrice();
        expect($builder->estimateType)->toBe('unit_price');

        $builder->historicalApiPrice();
        expect($builder->estimateType)->toBe('historical_api_price');
    });

});

describe('Platform Usage API', function (): void {

    it('can get usage data using fluent interface', function (): void {
        Saloon::fake([
            GetUsageRequest::class => MockResponse::fixture('Platform/usage-single-endpoint'),
        ]);

        $driver = createFalDriverForPlatformTests();

        $response = $driver->platform()->usage()
            ->forEndpoint('fal-ai/flux/dev')
            ->get();

        expect($response->successful())->toBeTrue()
            ->and($response->timeSeries)->toHaveCount(1)
            ->and($response->timeSeries[0]['results'][0]['endpoint_id'])->toBe('fal-ai/flux/dev')
            ->and($response->timeSeries[0]['results'][0]['quantity'])->toBe(10)
            ->and($response->hasMore)->toBeFalse()
            ->and($response->nextCursor)->toBeNull();
    });

    it('can get usage with date range', function (): void {
        Saloon::fake([
            GetUsageRequest::class => MockResponse::fixture('Platform/usage-date-range'),
        ]);

        $driver = createFalDriverForPlatformTests();

        $response = $driver->platform()->usage()
            ->forEndpoint('fal-ai/flux/dev')
            ->between('2025-01-10T00:00:00Z', '2025-01-12T00:00:00Z')
            ->timeframe('day')
            ->get();

        expect($response->successful())->toBeTrue()
            ->and($response->timeSeries)->toHaveCount(2)
            ->and($response->getTotalQuantity())->toBe(13)
            ->and($response->getTotalCost())->toBe(0.325);
    });

    it('can get total cost for specific endpoint', function (): void {
        Saloon::fake([
            GetUsageRequest::class => MockResponse::fixture('Platform/usage-multiple-endpoints'),
        ]);

        $driver = createFalDriverForPlatformTests();

        $response = $driver->platform()->usage()
            ->forEndpoints(['fal-ai/flux/dev', 'fal-ai/flux/schnell'])
            ->get();

        expect($response->getTotalCostFor('fal-ai/flux/dev'))->toBe(0.25)
            ->and($response->getTotalCostFor('fal-ai/flux/schnell'))->toBe(0.06)
            ->and($response->getTotalQuantityFor('fal-ai/flux/dev'))->toBe(10)
            ->and($response->getTotalQuantityFor('fal-ai/flux/schnell'))->toBe(20);
    });

    it('can get usage for specific endpoint from time series', function (): void {
        Saloon::fake([
            GetUsageRequest::class => MockResponse::fixture('Platform/usage-single-endpoint'),
        ]);

        $driver = createFalDriverForPlatformTests();

        $response = $driver->platform()->usage()
            ->forEndpoint('fal-ai/flux/dev')
            ->get();

        $usageData = $response->getUsageFor('fal-ai/flux/dev');

        expect($usageData)->toHaveCount(1)
            ->and($usageData[0]['bucket'])->toBe('2025-01-15T00:00:00-05:00')
            ->and($usageData[0]['quantity'])->toBe(10);
    });

    it('can include summary data', function (): void {
        Saloon::fake([
            GetUsageRequest::class => MockResponse::fixture('Platform/usage-with-summary'),
        ]);

        $driver = createFalDriverForPlatformTests();

        $response = $driver->platform()->usage()
            ->forEndpoint('fal-ai/flux/dev')
            ->withSummary()
            ->get();

        expect($response->summary)->not->toBeNull()
            ->and($response->summary['total_cost'])->toBe(5.5);
    });

});

describe('Platform Usage Request Builder', function (): void {

    it('throws exception when exceeding 50 endpoint limit', function (): void {
        $driver = createFalDriverForPlatformTests();

        $endpoints = array_map(fn (int $i): string => "fal-ai/model-{$i}", range(1, 51));

        expect(fn (): Cjmellor\FalAi\Support\UsageRequest => $driver->platform()->usage()
            ->forEndpoints($endpoints)
        )->toThrow(InvalidArgumentException::class, 'Maximum of 50 endpoint IDs allowed');
    });

    it('throws exception for invalid timeframe', function (): void {
        $driver = createFalDriverForPlatformTests();

        expect(fn (): Cjmellor\FalAi\Support\UsageRequest => $driver->platform()->usage()
            ->timeframe('invalid')
        )->toThrow(InvalidArgumentException::class, 'Invalid timeframe');
    });

    it('throws exception for limit less than 1', function (): void {
        $driver = createFalDriverForPlatformTests();

        expect(fn (): Cjmellor\FalAi\Support\UsageRequest => $driver->platform()->usage()
            ->limit(0)
        )->toThrow(InvalidArgumentException::class, 'Limit must be at least 1');
    });

    it('can chain expand options', function (): void {
        $driver = createFalDriverForPlatformTests();

        $builder = $driver->platform()->usage()
            ->withTimeSeries()
            ->withSummary()
            ->withAuthMethod();

        expect($builder->expand)->toContain('time_series')
            ->and($builder->expand)->toContain('summary')
            ->and($builder->expand)->toContain('auth_method');
    });

    it('can set date range with from and to', function (): void {
        Saloon::fake([
            GetUsageRequest::class => MockResponse::fixture('Platform/analytics-empty'),
        ]);

        $driver = createFalDriverForPlatformTests();

        // Just verify the builder works - the actual query params are tested in unit tests
        $response = $driver->platform()->usage()
            ->from('2025-01-01T00:00:00Z')
            ->to('2025-01-15T00:00:00Z')
            ->get();

        expect($response->successful())->toBeTrue();
    });

});

describe('Platform Analytics API', function (): void {

    it('can get analytics data using fluent interface', function (): void {
        Saloon::fake([
            GetAnalyticsRequest::class => MockResponse::fixture('Platform/analytics-basic'),
        ]);

        $driver = createFalDriverForPlatformTests();

        $response = $driver->platform()->analytics()
            ->forEndpoint('fal-ai/flux/dev')
            ->get();

        expect($response->successful())->toBeTrue()
            ->and($response->timeSeries)->toHaveCount(1)
            ->and($response->timeSeries[0]['results'][0]['request_count'])->toBe(1500)
            ->and($response->hasMore)->toBeFalse();
    });

    it('can get analytics with latency metrics', function (): void {
        Saloon::fake([
            GetAnalyticsRequest::class => MockResponse::fixture('Platform/analytics-with-latency'),
        ]);

        $driver = createFalDriverForPlatformTests();

        $response = $driver->platform()->analytics()
            ->forEndpoint('fal-ai/flux/dev')
            ->withAllLatencyMetrics()
            ->get();

        expect($response->successful())->toBeTrue()
            ->and($response->timeSeries[0]['results'][0]['p50_duration'])->toBe(2.5)
            ->and($response->timeSeries[0]['results'][0]['p90_duration'])->toBe(4.8);
    });

    it('can calculate total requests and successes', function (): void {
        Saloon::fake([
            GetAnalyticsRequest::class => MockResponse::fixture('Platform/analytics-multiple-buckets'),
        ]);

        $driver = createFalDriverForPlatformTests();

        $response = $driver->platform()->analytics()
            ->forEndpoint('fal-ai/flux/dev')
            ->withSuccessCount()
            ->withErrorCount()
            ->get();

        expect($response->getTotalRequests())->toBe(300)
            ->and($response->getTotalSuccesses())->toBe(285)
            ->and($response->getTotalErrors())->toBe(15);
    });

    it('can calculate success rate', function (): void {
        Saloon::fake([
            GetAnalyticsRequest::class => MockResponse::fixture('Platform/analytics-success-rate'),
        ]);

        $driver = createFalDriverForPlatformTests();

        $response = $driver->platform()->analytics()
            ->forEndpoint('fal-ai/flux/dev')
            ->withSuccessCount()
            ->get();

        expect($response->getSuccessRate())->toBe(95.0)
            ->and($response->getSuccessRateFor('fal-ai/flux/dev'))->toBe(95.0);
    });

    it('returns zero success rate when no requests', function (): void {
        Saloon::fake([
            GetAnalyticsRequest::class => MockResponse::fixture('Platform/analytics-empty'),
        ]);

        $driver = createFalDriverForPlatformTests();

        $response = $driver->platform()->analytics()
            ->forEndpoint('fal-ai/flux/dev')
            ->get();

        expect($response->getSuccessRate())->toBe(0.0);
    });

    it('can get analytics for specific endpoint from time series', function (): void {
        Saloon::fake([
            GetAnalyticsRequest::class => MockResponse::fixture('Platform/analytics-multiple-endpoints'),
        ]);

        $driver = createFalDriverForPlatformTests();

        $response = $driver->platform()->analytics()
            ->forEndpoints(['fal-ai/flux/dev', 'fal-ai/flux/schnell'])
            ->get();

        $devAnalytics = $response->getAnalyticsFor('fal-ai/flux/dev');
        $schnellAnalytics = $response->getAnalyticsFor('fal-ai/flux/schnell');

        expect($devAnalytics)->toHaveCount(1)
            ->and($devAnalytics[0]['request_count'])->toBe(100)
            ->and($schnellAnalytics[0]['request_count'])->toBe(200);
    });

});

describe('Platform Analytics Request Builder', function (): void {

    it('throws exception when no endpoint provided', function (): void {
        $driver = createFalDriverForPlatformTests();

        expect(fn (): Cjmellor\FalAi\Responses\AnalyticsResponse => $driver->platform()->analytics()->get())
            ->toThrow(InvalidArgumentException::class, 'At least one endpoint ID is required for analytics');
    });

    it('throws exception when exceeding 50 endpoint limit', function (): void {
        $driver = createFalDriverForPlatformTests();

        $endpoints = array_map(fn (int $i): string => "fal-ai/model-{$i}", range(1, 51));

        expect(fn (): Cjmellor\FalAi\Support\AnalyticsRequest => $driver->platform()->analytics()
            ->forEndpoints($endpoints)
        )->toThrow(InvalidArgumentException::class, 'Maximum of 50 endpoint IDs allowed');
    });

    it('throws exception for invalid timeframe', function (): void {
        $driver = createFalDriverForPlatformTests();

        expect(fn (): Cjmellor\FalAi\Support\AnalyticsRequest => $driver->platform()->analytics()
            ->timeframe('yearly')
        )->toThrow(InvalidArgumentException::class, 'Invalid timeframe');
    });

    it('can chain metric options', function (): void {
        $driver = createFalDriverForPlatformTests();

        $builder = $driver->platform()->analytics()
            ->forEndpoint('fal-ai/flux/dev')
            ->withRequestCount()
            ->withSuccessCount()
            ->withAllErrors()
            ->withP90Duration();

        $expand = $builder->expand;

        expect($expand)->toContain('request_count')
            ->and($expand)->toContain('success_count')
            ->and($expand)->toContain('user_error_count')
            ->and($expand)->toContain('error_count')
            ->and($expand)->toContain('p90_duration');
    });

    it('can use withAllMetrics helper', function (): void {
        $driver = createFalDriverForPlatformTests();

        $builder = $driver->platform()->analytics()
            ->forEndpoint('fal-ai/flux/dev')
            ->withAllMetrics();

        $expand = $builder->expand;

        expect($expand)->toContain('time_series')
            ->and($expand)->toContain('request_count')
            ->and($expand)->toContain('success_count')
            ->and($expand)->toContain('user_error_count')
            ->and($expand)->toContain('error_count')
            ->and($expand)->toContain('p50_duration')
            ->and($expand)->toContain('p90_duration');
    });

});

describe('Platform Delete Request Payloads API', function (): void {

    it('can delete payloads for a request ID', function (): void {
        Saloon::fake([
            DeleteRequestPayloadsRequest::class => MockResponse::fixture('Platform/delete-payloads-success'),
        ]);

        $driver = createFalDriverForPlatformTests();

        $response = $driver->platform()
            ->deleteRequestPayloads('req_123456789')
            ->delete();

        expect($response->successful())->toBeTrue()
            ->and($response->cdnDeleteResults)->toHaveCount(2)
            ->and($response->cdnDeleteResults[0]['link'])->toBe('https://v3.fal.media/files/abc123/output.png')
            ->and($response->cdnDeleteResults[0]['exception'])->toBeNull();
    });

    it('can set idempotency key', function (): void {
        Saloon::fake([
            DeleteRequestPayloadsRequest::class => MockResponse::fixture('Platform/delete-payloads-success'),
        ]);

        $driver = createFalDriverForPlatformTests();

        $builder = $driver->platform()
            ->deleteRequestPayloads('req_123456789')
            ->withIdempotencyKey('unique-key-123');

        expect($builder->idempotencyKey)->toBe('unique-key-123');

        $response = $builder->delete();

        expect($response->successful())->toBeTrue();
    });

    it('returns hasErrors false for successful deletions', function (): void {
        Saloon::fake([
            DeleteRequestPayloadsRequest::class => MockResponse::fixture('Platform/delete-payloads-success'),
        ]);

        $driver = createFalDriverForPlatformTests();

        $response = $driver->platform()
            ->deleteRequestPayloads('req_123456789')
            ->delete();

        expect($response->hasErrors())->toBeFalse();
    });

    it('returns hasErrors true when exceptions present', function (): void {
        Saloon::fake([
            DeleteRequestPayloadsRequest::class => MockResponse::fixture('Platform/delete-payloads-partial-failure'),
        ]);

        $driver = createFalDriverForPlatformTests();

        $response = $driver->platform()
            ->deleteRequestPayloads('req_123456789')
            ->delete();

        expect($response->hasErrors())->toBeTrue();
    });

    it('filters successful deletions correctly', function (): void {
        Saloon::fake([
            DeleteRequestPayloadsRequest::class => MockResponse::fixture('Platform/delete-payloads-partial-failure'),
        ]);

        $driver = createFalDriverForPlatformTests();

        $response = $driver->platform()
            ->deleteRequestPayloads('req_123456789')
            ->delete();

        $successful = $response->getSuccessfulDeletions();

        expect($successful)->toHaveCount(1)
            ->and($successful[0]['link'])->toBe('https://v3.fal.media/files/abc123/output.png')
            ->and($successful[0]['exception'])->toBeNull();
    });

    it('filters failed deletions correctly', function (): void {
        Saloon::fake([
            DeleteRequestPayloadsRequest::class => MockResponse::fixture('Platform/delete-payloads-partial-failure'),
        ]);

        $driver = createFalDriverForPlatformTests();

        $response = $driver->platform()
            ->deleteRequestPayloads('req_123456789')
            ->delete();

        $failed = $response->getFailedDeletions();

        expect($failed)->toHaveCount(2)
            ->and($failed[0]['exception'])->toBe('File not found')
            ->and($failed[1]['exception'])->toBe('Access denied');
    });

    it('handles empty cdn delete results', function (): void {
        Saloon::fake([
            DeleteRequestPayloadsRequest::class => MockResponse::fixture('Platform/delete-payloads-empty'),
        ]);

        $driver = createFalDriverForPlatformTests();

        $response = $driver->platform()
            ->deleteRequestPayloads('req_123456789')
            ->delete();

        expect($response->successful())->toBeTrue()
            ->and($response->cdnDeleteResults)->toBeEmpty()
            ->and($response->hasErrors())->toBeFalse()
            ->and($response->getSuccessfulDeletions())->toBeEmpty()
            ->and($response->getFailedDeletions())->toBeEmpty();
    });

    it('can retrieve request ID from builder', function (): void {
        $driver = createFalDriverForPlatformTests();

        $builder = $driver->platform()
            ->deleteRequestPayloads('req_123456789');

        expect($builder->requestId)->toBe('req_123456789');
    });

});
