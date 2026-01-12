<?php

declare(strict_types=1);

use Cjmellor\FalAi\Platform;
use Cjmellor\FalAi\Support\AnalyticsRequest;

covers(AnalyticsRequest::class);

beforeEach(function (): void {
    $this->platform = new Platform;
    $this->request = new AnalyticsRequest($this->platform);
});

describe('Endpoint Management', function (): void {
    it('throws when adding 51st endpoint via forEndpoint()', function (): void {
        // Add 50 endpoints
        for ($i = 1; $i <= 50; $i++) {
            $this->request->forEndpoint("endpoint-{$i}");
        }

        expect($this->request->endpointIds)->toHaveCount(50);

        // Attempt to add 51st should throw
        expect(fn () => $this->request->forEndpoint('endpoint-51'))
            ->toThrow(InvalidArgumentException::class, 'Maximum of 50 endpoint IDs allowed');
    });

    it('can add up to 50 endpoints one by one', function (): void {
        for ($i = 1; $i <= 50; $i++) {
            $this->request->forEndpoint("endpoint-{$i}");
        }

        expect($this->request->endpointIds)->toHaveCount(50);
    });
});

describe('expand() method', function (): void {
    it('overwrites existing expand options', function (): void {
        $this->request->expand(['custom_metric_1', 'custom_metric_2']);

        expect($this->request->expand)->toBe(['custom_metric_1', 'custom_metric_2']);
    });

    it('can set custom expand options that replace defaults', function (): void {
        // Default is ['time_series', 'request_count']
        $this->request->expand(['success_count']);

        expect($this->request->expand)->toBe(['success_count'])
            ->not->toContain('time_series')
            ->not->toContain('request_count');
    });
});

describe('Metric deduplication', function (): void {
    it('withTimeSeries() does not duplicate when already present', function (): void {
        // Default already has 'time_series'
        $this->request->withTimeSeries();

        expect(array_count_values($this->request->expand)['time_series'])->toBe(1);
    });

    it('withTimeSeries() adds when not present', function (): void {
        $this->request->expand(['success_count']);
        $this->request->withTimeSeries();

        expect($this->request->expand)->toContain('time_series');
    });

    it('withRequestCount() does not duplicate when already present', function (): void {
        // Default already has 'request_count'
        $this->request->withRequestCount();

        expect(array_count_values($this->request->expand)['request_count'])->toBe(1);
    });

    it('withRequestCount() adds when not present', function (): void {
        $this->request->expand(['success_count']);
        $this->request->withRequestCount();

        expect($this->request->expand)->toContain('request_count');
    });
});

describe('Date/Time Methods', function (): void {
    it('from() sets start date', function (): void {
        $result = $this->request->from('2024-01-01T00:00:00Z');

        expect($result)->toBeInstanceOf(AnalyticsRequest::class);
    });

    it('to() sets end date', function (): void {
        $result = $this->request->to('2024-01-31T23:59:59Z');

        expect($result)->toBeInstanceOf(AnalyticsRequest::class);
    });

    it('between() sets both start and end dates', function (): void {
        $result = $this->request->between('2024-01-01T00:00:00Z', '2024-01-31T23:59:59Z');

        expect($result)->toBeInstanceOf(AnalyticsRequest::class);
    });

    it('timezone() sets timezone', function (): void {
        $result = $this->request->timezone('America/New_York');

        expect($result)->toBeInstanceOf(AnalyticsRequest::class);
    });
});

describe('Aggregation Methods', function (): void {
    it('timeframe() accepts valid timeframes', function (string $timeframe): void {
        $result = $this->request->timeframe($timeframe);

        expect($result)->toBeInstanceOf(AnalyticsRequest::class)
            ->and($result->timeframe)->toBe($timeframe);
    })->with([
        'minute' => ['minute'],
        'hour' => ['hour'],
        'day' => ['day'],
        'week' => ['week'],
        'month' => ['month'],
    ]);

    it('timeframe() throws for invalid timeframe', function (): void {
        expect(fn () => $this->request->timeframe('invalid'))
            ->toThrow(InvalidArgumentException::class, 'Invalid timeframe');
    });

    it('boundToTimeframe() sets bound flag to true', function (): void {
        $result = $this->request->boundToTimeframe(true);

        expect($result)->toBeInstanceOf(AnalyticsRequest::class);
    });

    it('boundToTimeframe() sets bound flag to false', function (): void {
        $result = $this->request->boundToTimeframe(false);

        expect($result)->toBeInstanceOf(AnalyticsRequest::class);
    });
});

describe('Pagination Methods', function (): void {
    it('limit() sets valid limit', function (): void {
        $result = $this->request->limit(100);

        expect($result)->toBeInstanceOf(AnalyticsRequest::class);
    });

    it('limit() throws for zero', function (): void {
        expect(fn () => $this->request->limit(0))
            ->toThrow(InvalidArgumentException::class, 'Limit must be at least 1');
    });

    it('limit() throws for negative numbers', function (): void {
        expect(fn () => $this->request->limit(-10))
            ->toThrow(InvalidArgumentException::class, 'Limit must be at least 1');
    });

    it('cursor() sets pagination cursor', function (): void {
        $result = $this->request->cursor('abc123');

        expect($result)->toBeInstanceOf(AnalyticsRequest::class);
    });
});

describe('Method Chaining', function (): void {
    it('can chain all configuration methods', function (): void {
        $result = $this->request
            ->forEndpoint('endpoint-1')
            ->expand(['time_series', 'request_count', 'success_count'])
            ->from('2024-01-01T00:00:00Z')
            ->to('2024-01-31T23:59:59Z')
            ->timezone('UTC')
            ->timeframe('day')
            ->boundToTimeframe(true)
            ->limit(50)
            ->cursor('cursor123');

        expect($result)->toBeInstanceOf(AnalyticsRequest::class)
            ->and($result->endpointIds)->toBe(['endpoint-1'])
            ->and($result->expand)->toBe(['time_series', 'request_count', 'success_count'])
            ->and($result->timeframe)->toBe('day');
    });
});
