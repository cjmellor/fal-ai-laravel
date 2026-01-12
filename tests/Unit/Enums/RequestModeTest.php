<?php

declare(strict_types=1);

use Cjmellor\FalAi\Enums\RequestMode;

describe('RequestMode Enum', function (): void {
    it('has correct string values for each case', function (): void {
        expect(RequestMode::Queue->value)->toBe('queue')
            ->and(RequestMode::Sync->value)->toBe('sync')
            ->and(RequestMode::Stream->value)->toBe('stream');
    });

    it('can be created from string values', function (string $value, RequestMode $expected): void {
        expect(RequestMode::from($value))->toBe($expected);
    })->with([
        'queue' => ['queue', RequestMode::Queue],
        'sync' => ['sync', RequestMode::Sync],
        'stream' => ['stream', RequestMode::Stream],
    ]);

    describe('isAsync()', function (): void {
        it('returns true only for Queue mode', function (): void {
            expect(RequestMode::Queue->isAsync())->toBeTrue();
        });

        it('returns false for Sync and Stream modes', function (RequestMode $mode): void {
            expect($mode->isAsync())->toBeFalse();
        })->with([
            'Sync' => [RequestMode::Sync],
            'Stream' => [RequestMode::Stream],
        ]);
    });

    describe('isImmediate()', function (): void {
        it('returns true for Sync and Stream modes', function (RequestMode $mode): void {
            expect($mode->isImmediate())->toBeTrue();
        })->with([
            'Sync' => [RequestMode::Sync],
            'Stream' => [RequestMode::Stream],
        ]);

        it('returns false for Queue mode', function (): void {
            expect(RequestMode::Queue->isImmediate())->toBeFalse();
        });
    });

    it('has mutually exclusive async and immediate states for each mode', function (RequestMode $mode): void {
        // A mode cannot be both async and immediate at the same time
        expect($mode->isAsync())->not->toBe($mode->isImmediate());
    })->with([
        'Queue' => [RequestMode::Queue],
        'Sync' => [RequestMode::Sync],
        'Stream' => [RequestMode::Stream],
    ]);
});
