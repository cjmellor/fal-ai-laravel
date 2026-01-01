<?php

declare(strict_types=1);

use Cjmellor\FalAi\Requests\Platform\GetAnalyticsRequest;
use Saloon\Enums\Method;

beforeEach(function (): void {
    config([
        'fal-ai.api_key' => 'test-api-key',
        'fal-ai.platform_base_url' => 'https://api.fal.ai',
    ]);
});

describe('GetAnalyticsRequest', function (): void {

    it('uses GET method', function (): void {
        $request = new GetAnalyticsRequest(['fal-ai/flux/dev']);

        expect($request->getMethod())->toBe(Method::GET);
    });

    it('resolves to correct endpoint', function (): void {
        $request = new GetAnalyticsRequest(['fal-ai/flux/dev']);

        expect($request->resolveEndpoint())->toBe('/v1/models/analytics');
    });

    it('builds query with single endpoint id', function (): void {
        $request = new GetAnalyticsRequest(['fal-ai/flux/dev']);

        $query = $request->query();

        expect($query->get('endpoint_id'))->toBe('fal-ai/flux/dev');
    });

    it('builds query with multiple endpoint ids', function (): void {
        $request = new GetAnalyticsRequest([
            'fal-ai/flux/dev',
            'fal-ai/flux/schnell',
        ]);

        $query = $request->query();

        expect($query->get('endpoint_id'))->toBe('fal-ai/flux/dev,fal-ai/flux/schnell');
    });

    it('includes default expand parameters', function (): void {
        $request = new GetAnalyticsRequest(['fal-ai/flux/dev']);

        $query = $request->query();

        expect($query->get('expand'))->toBe('time_series,request_count');
    });

    it('includes custom expand parameters', function (): void {
        $request = new GetAnalyticsRequest(
            endpointIds: ['fal-ai/flux/dev'],
            expand: ['time_series', 'request_count', 'success_count', 'p90_duration'],
        );

        $query = $request->query();

        expect($query->get('expand'))->toBe('time_series,request_count,success_count,p90_duration');
    });

    it('includes date range parameters', function (): void {
        $request = new GetAnalyticsRequest(
            endpointIds: ['fal-ai/flux/dev'],
            start: '2025-01-01T00:00:00Z',
            end: '2025-01-15T00:00:00Z',
        );

        $query = $request->query();

        expect($query->get('start'))->toBe('2025-01-01T00:00:00Z')
            ->and($query->get('end'))->toBe('2025-01-15T00:00:00Z');
    });

    it('includes timezone parameter', function (): void {
        $request = new GetAnalyticsRequest(
            endpointIds: ['fal-ai/flux/dev'],
            timezone: 'America/New_York',
        );

        $query = $request->query();

        expect($query->get('timezone'))->toBe('America/New_York');
    });

    it('includes timeframe parameter', function (): void {
        $request = new GetAnalyticsRequest(
            endpointIds: ['fal-ai/flux/dev'],
            timeframe: 'hour',
        );

        $query = $request->query();

        expect($query->get('timeframe'))->toBe('hour');
    });

    it('includes bound_to_timeframe parameter', function (): void {
        $request = new GetAnalyticsRequest(
            endpointIds: ['fal-ai/flux/dev'],
            boundToTimeframe: true,
        );

        $query = $request->query();

        expect($query->get('bound_to_timeframe'))->toBe('true');
    });

    it('includes limit and cursor parameters', function (): void {
        $request = new GetAnalyticsRequest(
            endpointIds: ['fal-ai/flux/dev'],
            limit: 100,
            cursor: 'abc123',
        );

        $query = $request->query();

        expect($query->get('limit'))->toBe(100)
            ->and($query->get('cursor'))->toBe('abc123');
    });

});
