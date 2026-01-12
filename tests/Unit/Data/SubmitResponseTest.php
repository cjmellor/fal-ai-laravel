<?php

declare(strict_types=1);

use Cjmellor\FalAi\Data\SubmitResponse;

covers(SubmitResponse::class);

describe('SubmitResponse Data Class', function (): void {
    it('can be constructed with all parameters', function (): void {
        $response = new SubmitResponse(
            requestId: 'req-123',
            responseUrl: 'https://example.com/response',
            statusUrl: 'https://example.com/status',
            cancelUrl: 'https://example.com/cancel',
        );

        expect($response->requestId)->toBe('req-123')
            ->and($response->responseUrl)->toBe('https://example.com/response')
            ->and($response->statusUrl)->toBe('https://example.com/status')
            ->and($response->cancelUrl)->toBe('https://example.com/cancel');
    });

    describe('fromArray()', function (): void {
        it('creates instance from array', function (): void {
            $data = [
                'request_id' => 'req-456',
                'response_url' => 'https://queue.fal.run/response',
                'status_url' => 'https://queue.fal.run/status',
                'cancel_url' => 'https://queue.fal.run/cancel',
            ];

            $response = SubmitResponse::fromArray($data);

            expect($response->requestId)->toBe('req-456')
                ->and($response->responseUrl)->toBe('https://queue.fal.run/response')
                ->and($response->statusUrl)->toBe('https://queue.fal.run/status')
                ->and($response->cancelUrl)->toBe('https://queue.fal.run/cancel');
        });
    });

    describe('toArray()', function (): void {
        it('returns array with snake_case keys', function (): void {
            $response = new SubmitResponse(
                requestId: 'req-789',
                responseUrl: 'https://example.com/response',
                statusUrl: 'https://example.com/status',
                cancelUrl: 'https://example.com/cancel',
            );

            expect($response->toArray())->toBe([
                'request_id' => 'req-789',
                'response_url' => 'https://example.com/response',
                'status_url' => 'https://example.com/status',
                'cancel_url' => 'https://example.com/cancel',
            ]);
        });
    });

    it('round trips through fromArray and toArray', function (): void {
        $originalData = [
            'request_id' => 'round-trip-test',
            'response_url' => 'https://example.com/response',
            'status_url' => 'https://example.com/status',
            'cancel_url' => 'https://example.com/cancel',
        ];

        $response = SubmitResponse::fromArray($originalData);
        $arrayData = $response->toArray();

        expect($arrayData)->toBe($originalData);
    });
});
