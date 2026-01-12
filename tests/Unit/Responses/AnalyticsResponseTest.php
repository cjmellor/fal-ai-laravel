<?php

declare(strict_types=1);

use Cjmellor\FalAi\Responses\AnalyticsResponse;
use Saloon\Http\Response;

covers(AnalyticsResponse::class);

beforeEach(function (): void {
    $this->mockResponse = Mockery::mock(Response::class);
    $this->mockResponse->shouldReceive('json')->andReturn([]);
    $this->mockResponse->shouldReceive('status')->andReturn(200);
    $this->mockResponse->shouldReceive('successful')->andReturn(true);
    $this->mockResponse->shouldReceive('failed')->andReturn(false);
});

afterEach(function (): void {
    Mockery::close();
});

describe('getTotalUserErrors()', function (): void {
    it('calculates total user errors across all buckets', function (): void {
        $data = [
            'time_series' => [
                [
                    'bucket' => '2024-01-01T00:00:00Z',
                    'results' => [
                        ['endpoint_id' => 'endpoint1', 'user_error_count' => 5],
                        ['endpoint_id' => 'endpoint2', 'user_error_count' => 3],
                    ],
                ],
                [
                    'bucket' => '2024-01-02T00:00:00Z',
                    'results' => [
                        ['endpoint_id' => 'endpoint1', 'user_error_count' => 2],
                    ],
                ],
            ],
        ];

        $response = new AnalyticsResponse($this->mockResponse, $data);

        expect($response->getTotalUserErrors())->toBe(10);
    });

    it('returns zero when no user errors exist', function (): void {
        $data = [
            'time_series' => [
                [
                    'bucket' => '2024-01-01T00:00:00Z',
                    'results' => [
                        ['endpoint_id' => 'endpoint1', 'request_count' => 100],
                    ],
                ],
            ],
        ];

        $response = new AnalyticsResponse($this->mockResponse, $data);

        expect($response->getTotalUserErrors())->toBe(0);
    });

    it('returns zero for empty time series', function (): void {
        $response = new AnalyticsResponse($this->mockResponse, ['time_series' => []]);

        expect($response->getTotalUserErrors())->toBe(0);
    });

    it('handles missing results in bucket gracefully', function (): void {
        $data = [
            'time_series' => [
                ['bucket' => '2024-01-01T00:00:00Z'],
            ],
        ];

        $response = new AnalyticsResponse($this->mockResponse, $data);

        expect($response->getTotalUserErrors())->toBe(0);
    });
});

describe('getSuccessRateFor()', function (): void {
    it('returns 0.0 when endpoint has zero requests', function (): void {
        $data = [
            'time_series' => [
                [
                    'bucket' => '2024-01-01T00:00:00Z',
                    'results' => [
                        ['endpoint_id' => 'other-endpoint', 'request_count' => 100, 'success_count' => 95],
                    ],
                ],
            ],
        ];

        $response = new AnalyticsResponse($this->mockResponse, $data);

        expect($response->getSuccessRateFor('missing-endpoint'))->toBe(0.0);
    });

    it('returns 0.0 when time series is empty', function (): void {
        $response = new AnalyticsResponse($this->mockResponse, ['time_series' => []]);

        expect($response->getSuccessRateFor('any-endpoint'))->toBe(0.0);
    });

    it('calculates correct success rate for endpoint with requests', function (): void {
        $data = [
            'time_series' => [
                [
                    'bucket' => '2024-01-01T00:00:00Z',
                    'results' => [
                        ['endpoint_id' => 'my-endpoint', 'request_count' => 100, 'success_count' => 80],
                    ],
                ],
            ],
        ];

        $response = new AnalyticsResponse($this->mockResponse, $data);

        expect($response->getSuccessRateFor('my-endpoint'))->toBe(80.0);
    });

    it('aggregates across multiple buckets for same endpoint', function (): void {
        $data = [
            'time_series' => [
                [
                    'bucket' => '2024-01-01T00:00:00Z',
                    'results' => [
                        ['endpoint_id' => 'my-endpoint', 'request_count' => 50, 'success_count' => 40],
                    ],
                ],
                [
                    'bucket' => '2024-01-02T00:00:00Z',
                    'results' => [
                        ['endpoint_id' => 'my-endpoint', 'request_count' => 50, 'success_count' => 50],
                    ],
                ],
            ],
        ];

        $response = new AnalyticsResponse($this->mockResponse, $data);

        // 90 successes out of 100 requests = 90%
        expect($response->getSuccessRateFor('my-endpoint'))->toBe(90.0);
    });
});
