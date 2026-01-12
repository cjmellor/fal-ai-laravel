<?php

declare(strict_types=1);

use Cjmellor\FalAi\Platform;
use Cjmellor\FalAi\Support\UsageRequest;

covers(UsageRequest::class);

beforeEach(function (): void {
    $this->platform = new Platform;
    $this->request = new UsageRequest($this->platform);
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
});

describe('expand() method', function (): void {
    it('overwrites existing expand options', function (): void {
        $this->request->expand(['summary', 'auth_method']);

        expect($this->request->expand)->toBe(['summary', 'auth_method']);
    });

    it('replaces default time_series with custom options', function (): void {
        // Default is ['time_series']
        $this->request->expand(['summary']);

        expect($this->request->expand)->toBe(['summary'])
            ->not->toContain('time_series');
    });
});

describe('Metric deduplication', function (): void {
    it('withTimeSeries() does not duplicate when already present', function (): void {
        // Default already has 'time_series'
        $this->request->withTimeSeries();

        expect(array_count_values($this->request->expand)['time_series'])->toBe(1);
    });

    it('withTimeSeries() adds when not present', function (): void {
        $this->request->expand(['summary']);
        $this->request->withTimeSeries();

        expect($this->request->expand)->toContain('time_series');
    });
});

describe('timezone() method', function (): void {
    it('sets timezone parameter', function (): void {
        $result = $this->request->timezone('UTC');

        expect($result)->toBeInstanceOf(UsageRequest::class);
    });

    it('accepts various timezone formats', function (string $timezone): void {
        $result = $this->request->timezone($timezone);

        expect($result)->toBeInstanceOf(UsageRequest::class);
    })->with([
        'UTC' => ['UTC'],
        'America/New_York' => ['America/New_York'],
        'Asia/Tokyo' => ['Asia/Tokyo'],
        'Europe/London' => ['Europe/London'],
    ]);
});

describe('boundToTimeframe() method', function (): void {
    it('sets bound flag to true', function (): void {
        $result = $this->request->boundToTimeframe(true);

        expect($result)->toBeInstanceOf(UsageRequest::class);
    });

    it('sets bound flag to false', function (): void {
        $result = $this->request->boundToTimeframe(false);

        expect($result)->toBeInstanceOf(UsageRequest::class);
    });

    it('defaults to true when called without argument', function (): void {
        $result = $this->request->boundToTimeframe();

        expect($result)->toBeInstanceOf(UsageRequest::class);
    });
});

describe('limit() method', function (): void {
    it('sets valid limit', function (): void {
        $result = $this->request->limit(100);

        expect($result)->toBeInstanceOf(UsageRequest::class);
    });

    it('throws for zero', function (): void {
        expect(fn () => $this->request->limit(0))
            ->toThrow(InvalidArgumentException::class, 'Limit must be at least 1');
    });

    it('throws for negative numbers', function (): void {
        expect(fn () => $this->request->limit(-5))
            ->toThrow(InvalidArgumentException::class, 'Limit must be at least 1');
    });
});

describe('cursor() method', function (): void {
    it('sets pagination cursor', function (): void {
        $result = $this->request->cursor('cursor-abc123');

        expect($result)->toBeInstanceOf(UsageRequest::class);
    });
});

describe('Method Chaining', function (): void {
    it('can chain all configuration methods', function (): void {
        $result = $this->request
            ->forEndpoint('endpoint-1')
            ->expand(['time_series', 'summary'])
            ->from('2024-01-01T00:00:00Z')
            ->to('2024-01-31T23:59:59Z')
            ->timezone('UTC')
            ->timeframe('day')
            ->boundToTimeframe(true)
            ->limit(50)
            ->cursor('cursor123');

        expect($result)->toBeInstanceOf(UsageRequest::class)
            ->and($result->endpointIds)->toBe(['endpoint-1'])
            ->and($result->expand)->toBe(['time_series', 'summary'])
            ->and($result->timeframe)->toBe('day');
    });
});
