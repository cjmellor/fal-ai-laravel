<?php

declare(strict_types=1);

use Cjmellor\FalAi\Data\StatusResponse;

covers(StatusResponse::class);

describe('StatusResponse Data Class', function (): void {
    it('can be constructed with all parameters', function (): void {
        $response = new StatusResponse(
            status: 'IN_QUEUE',
            queuePosition: 5,
            responseUrl: 'https://example.com/response',
            logs: ['log1', 'log2'],
        );

        expect($response->status)->toBe('IN_QUEUE')
            ->and($response->queuePosition)->toBe(5)
            ->and($response->responseUrl)->toBe('https://example.com/response')
            ->and($response->logs)->toBe(['log1', 'log2']);
    });

    it('can be constructed with only required status parameter', function (): void {
        $response = new StatusResponse(status: 'COMPLETED');

        expect($response->status)->toBe('COMPLETED')
            ->and($response->queuePosition)->toBeNull()
            ->and($response->responseUrl)->toBeNull()
            ->and($response->logs)->toBeNull();
    });

    describe('fromArray()', function (): void {
        it('creates instance from complete array', function (): void {
            $data = [
                'status' => 'IN_PROGRESS',
                'queue_position' => 3,
                'response_url' => 'https://example.com/response',
                'logs' => ['log entry'],
            ];

            $response = StatusResponse::fromArray($data);

            expect($response->status)->toBe('IN_PROGRESS')
                ->and($response->queuePosition)->toBe(3)
                ->and($response->responseUrl)->toBe('https://example.com/response')
                ->and($response->logs)->toBe(['log entry']);
        });

        it('creates instance from minimal array with only status', function (): void {
            $data = ['status' => 'COMPLETED'];

            $response = StatusResponse::fromArray($data);

            expect($response->status)->toBe('COMPLETED')
                ->and($response->queuePosition)->toBeNull()
                ->and($response->responseUrl)->toBeNull()
                ->and($response->logs)->toBeNull();
        });
    });

    describe('toArray()', function (): void {
        it('returns complete array when all fields are present', function (): void {
            $response = new StatusResponse(
                status: 'IN_QUEUE',
                queuePosition: 5,
                responseUrl: 'https://example.com/response',
                logs: ['log1'],
            );

            expect($response->toArray())->toBe([
                'status' => 'IN_QUEUE',
                'queue_position' => 5,
                'response_url' => 'https://example.com/response',
                'logs' => ['log1'],
            ]);
        });

        it('excludes null fields from array', function (): void {
            $response = new StatusResponse(status: 'COMPLETED');

            expect($response->toArray())->toBe(['status' => 'COMPLETED']);
        });

        it('only includes non-null optional fields', function (): void {
            $response = new StatusResponse(
                status: 'IN_PROGRESS',
                queuePosition: null,
                responseUrl: 'https://example.com/response',
                logs: null,
            );

            expect($response->toArray())->toBe([
                'status' => 'IN_PROGRESS',
                'response_url' => 'https://example.com/response',
            ]);
        });
    });

    describe('status checkers', function (): void {
        it('isInQueue() returns true only for IN_QUEUE status', function (): void {
            $inQueue = new StatusResponse(status: 'IN_QUEUE');
            $inProgress = new StatusResponse(status: 'IN_PROGRESS');
            $completed = new StatusResponse(status: 'COMPLETED');

            expect($inQueue->isInQueue())->toBeTrue()
                ->and($inProgress->isInQueue())->toBeFalse()
                ->and($completed->isInQueue())->toBeFalse();
        });

        it('isInProgress() returns true only for IN_PROGRESS status', function (): void {
            $inQueue = new StatusResponse(status: 'IN_QUEUE');
            $inProgress = new StatusResponse(status: 'IN_PROGRESS');
            $completed = new StatusResponse(status: 'COMPLETED');

            expect($inQueue->isInProgress())->toBeFalse()
                ->and($inProgress->isInProgress())->toBeTrue()
                ->and($completed->isInProgress())->toBeFalse();
        });

        it('isCompleted() returns true only for COMPLETED status', function (): void {
            $inQueue = new StatusResponse(status: 'IN_QUEUE');
            $inProgress = new StatusResponse(status: 'IN_PROGRESS');
            $completed = new StatusResponse(status: 'COMPLETED');

            expect($inQueue->isCompleted())->toBeFalse()
                ->and($inProgress->isCompleted())->toBeFalse()
                ->and($completed->isCompleted())->toBeTrue();
        });
    });

    it('handles empty logs array', function (): void {
        $response = new StatusResponse(
            status: 'IN_PROGRESS',
            logs: [],
        );

        expect($response->logs)->toBe([])
            ->and($response->toArray())->toBe([
                'status' => 'IN_PROGRESS',
                'logs' => [],
            ]);
    });

    it('handles zero queue position', function (): void {
        $response = new StatusResponse(
            status: 'IN_QUEUE',
            queuePosition: 0,
        );

        expect($response->queuePosition)->toBe(0)
            ->and($response->toArray())->toBe([
                'status' => 'IN_QUEUE',
                'queue_position' => 0,
            ]);
    });
});
