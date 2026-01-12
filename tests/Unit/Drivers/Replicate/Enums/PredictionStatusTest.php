<?php

declare(strict_types=1);

use Cjmellor\FalAi\Drivers\Replicate\Enums\PredictionStatus;

describe('PredictionStatus Enum', function (): void {
    it('has correct string values for each case', function (): void {
        expect(PredictionStatus::Starting->value)->toBe('starting')
            ->and(PredictionStatus::Processing->value)->toBe('processing')
            ->and(PredictionStatus::Succeeded->value)->toBe('succeeded')
            ->and(PredictionStatus::Failed->value)->toBe('failed')
            ->and(PredictionStatus::Canceled->value)->toBe('canceled');
    });

    it('can be created from string values', function (string $value, PredictionStatus $expected): void {
        expect(PredictionStatus::from($value))->toBe($expected);
    })->with([
        'starting' => ['starting', PredictionStatus::Starting],
        'processing' => ['processing', PredictionStatus::Processing],
        'succeeded' => ['succeeded', PredictionStatus::Succeeded],
        'failed' => ['failed', PredictionStatus::Failed],
        'canceled' => ['canceled', PredictionStatus::Canceled],
    ]);

    describe('tryFromString()', function (): void {
        it('returns the correct status for valid strings', function (string $value, PredictionStatus $expected): void {
            expect(PredictionStatus::tryFromString($value))->toBe($expected);
        })->with([
            'starting' => ['starting', PredictionStatus::Starting],
            'processing' => ['processing', PredictionStatus::Processing],
            'succeeded' => ['succeeded', PredictionStatus::Succeeded],
            'failed' => ['failed', PredictionStatus::Failed],
            'canceled' => ['canceled', PredictionStatus::Canceled],
        ]);

        it('returns null for null input', function (): void {
            expect(PredictionStatus::tryFromString(null))->toBeNull();
        });

        it('returns null for invalid strings', function (string $invalid): void {
            expect(PredictionStatus::tryFromString($invalid))->toBeNull();
        })->with([
            'empty string' => [''],
            'invalid status' => ['invalid'],
            'uppercase' => ['SUCCEEDED'],
            'mixed case' => ['Starting'],
            'with spaces' => [' succeeded '],
        ]);
    });

    describe('isTerminal()', function (): void {
        it('returns true for terminal states', function (PredictionStatus $status): void {
            expect($status->isTerminal())->toBeTrue();
        })->with([
            'Succeeded' => [PredictionStatus::Succeeded],
            'Failed' => [PredictionStatus::Failed],
            'Canceled' => [PredictionStatus::Canceled],
        ]);

        it('returns false for non-terminal states', function (PredictionStatus $status): void {
            expect($status->isTerminal())->toBeFalse();
        })->with([
            'Starting' => [PredictionStatus::Starting],
            'Processing' => [PredictionStatus::Processing],
        ]);
    });

    describe('isRunning()', function (): void {
        it('returns true for non-terminal states', function (PredictionStatus $status): void {
            expect($status->isRunning())->toBeTrue();
        })->with([
            'Starting' => [PredictionStatus::Starting],
            'Processing' => [PredictionStatus::Processing],
        ]);

        it('returns false for terminal states', function (PredictionStatus $status): void {
            expect($status->isRunning())->toBeFalse();
        })->with([
            'Succeeded' => [PredictionStatus::Succeeded],
            'Failed' => [PredictionStatus::Failed],
            'Canceled' => [PredictionStatus::Canceled],
        ]);

        it('is always the opposite of isTerminal()', function (PredictionStatus $status): void {
            expect($status->isRunning())->toBe(! $status->isTerminal());
        })->with([
            'Starting' => [PredictionStatus::Starting],
            'Processing' => [PredictionStatus::Processing],
            'Succeeded' => [PredictionStatus::Succeeded],
            'Failed' => [PredictionStatus::Failed],
            'Canceled' => [PredictionStatus::Canceled],
        ]);
    });

    describe('isSuccessful()', function (): void {
        it('returns true only for Succeeded status', function (): void {
            expect(PredictionStatus::Succeeded->isSuccessful())->toBeTrue();
        });

        it('returns false for all other statuses', function (PredictionStatus $status): void {
            expect($status->isSuccessful())->toBeFalse();
        })->with([
            'Starting' => [PredictionStatus::Starting],
            'Processing' => [PredictionStatus::Processing],
            'Failed' => [PredictionStatus::Failed],
            'Canceled' => [PredictionStatus::Canceled],
        ]);
    });

    describe('isFailed()', function (): void {
        it('returns true only for Failed status', function (): void {
            expect(PredictionStatus::Failed->isFailed())->toBeTrue();
        });

        it('returns false for all other statuses', function (PredictionStatus $status): void {
            expect($status->isFailed())->toBeFalse();
        })->with([
            'Starting' => [PredictionStatus::Starting],
            'Processing' => [PredictionStatus::Processing],
            'Succeeded' => [PredictionStatus::Succeeded],
            'Canceled' => [PredictionStatus::Canceled],
        ]);
    });

    describe('isCanceled()', function (): void {
        it('returns true only for Canceled status', function (): void {
            expect(PredictionStatus::Canceled->isCanceled())->toBeTrue();
        });

        it('returns false for all other statuses', function (PredictionStatus $status): void {
            expect($status->isCanceled())->toBeFalse();
        })->with([
            'Starting' => [PredictionStatus::Starting],
            'Processing' => [PredictionStatus::Processing],
            'Succeeded' => [PredictionStatus::Succeeded],
            'Failed' => [PredictionStatus::Failed],
        ]);
    });
});
