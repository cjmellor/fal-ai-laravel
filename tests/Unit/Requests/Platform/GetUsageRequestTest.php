<?php

declare(strict_types=1);

use Cjmellor\FalAi\Requests\Platform\GetUsageRequest;
use Saloon\Enums\Method;

beforeEach(function (): void {
    config([
        'fal-ai.api_key' => 'test-api-key',
        'fal-ai.platform_base_url' => 'https://api.fal.ai',
    ]);
});

describe('GetUsageRequest', function (): void {

    it('uses GET method', function (): void {
        $request = new GetUsageRequest(['fal-ai/flux/dev']);

        expect($request->getMethod())->toBe(Method::GET);
    });

    it('resolves to correct endpoint', function (): void {
        $request = new GetUsageRequest(['fal-ai/flux/dev']);

        expect($request->resolveEndpoint())->toBe('/v1/models/usage');
    });

    it('builds query with single endpoint id', function (): void {
        $request = new GetUsageRequest(['fal-ai/flux/dev']);

        $query = $request->query();

        expect($query->get('endpoint_id'))->toBe('fal-ai/flux/dev');
    });

    it('builds query with multiple endpoint ids', function (): void {
        $request = new GetUsageRequest([
            'fal-ai/flux/dev',
            'fal-ai/flux/schnell',
        ]);

        $query = $request->query();

        expect($query->get('endpoint_id'))->toBe('fal-ai/flux/dev,fal-ai/flux/schnell');
    });

    it('includes expand parameter by default', function (): void {
        $request = new GetUsageRequest([]);

        $query = $request->query();

        expect($query->get('expand'))->toBe('time_series');
    });

    it('includes custom expand parameters', function (): void {
        $request = new GetUsageRequest(
            endpointIds: [],
            expand: ['time_series', 'summary', 'auth_method'],
        );

        $query = $request->query();

        expect($query->get('expand'))->toBe('time_series,summary,auth_method');
    });

    it('includes date range parameters', function (): void {
        $request = new GetUsageRequest(
            endpointIds: ['fal-ai/flux/dev'],
            start: '2025-01-01T00:00:00Z',
            end: '2025-01-15T00:00:00Z',
        );

        $query = $request->query();

        expect($query->get('start'))->toBe('2025-01-01T00:00:00Z')
            ->and($query->get('end'))->toBe('2025-01-15T00:00:00Z');
    });

    it('includes timezone parameter', function (): void {
        $request = new GetUsageRequest(
            endpointIds: [],
            timezone: 'America/New_York',
        );

        $query = $request->query();

        expect($query->get('timezone'))->toBe('America/New_York');
    });

    it('includes timeframe parameter', function (): void {
        $request = new GetUsageRequest(
            endpointIds: [],
            timeframe: 'day',
        );

        $query = $request->query();

        expect($query->get('timeframe'))->toBe('day');
    });

    it('includes bound_to_timeframe parameter', function (): void {
        $request = new GetUsageRequest(
            endpointIds: [],
            boundToTimeframe: false,
        );

        $query = $request->query();

        expect($query->get('bound_to_timeframe'))->toBe('false');
    });

    it('includes limit and cursor parameters', function (): void {
        $request = new GetUsageRequest(
            endpointIds: [],
            limit: 50,
            cursor: 'Mg==',
        );

        $query = $request->query();

        expect($query->get('limit'))->toBe(50)
            ->and($query->get('cursor'))->toBe('Mg==');
    });

    it('can be constructed with default empty array', function (): void {
        $request = new GetUsageRequest();

        expect($request->resolveEndpoint())->toBe('/v1/models/usage')
            ->and($request->query()->get('expand'))->toBe('time_series');
    });

});
