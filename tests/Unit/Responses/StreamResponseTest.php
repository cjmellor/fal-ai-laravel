<?php

declare(strict_types=1);

use Cjmellor\FalAi\Responses\StreamResponse;
use HosmelQ\SSE\EventSource;
use Saloon\Http\Response;

covers(StreamResponse::class);

/**
 * Simple SSE Event value object for testing
 */
class TestSSEEvent
{
    public function __construct(public ?string $data) {}
}

/**
 * Simple EventSource stub that can be returned from mocked Response
 */
class TestEventSource
{
    public function __construct(private array $events) {}

    public function events(): Generator
    {
        foreach ($this->events as $event) {
            yield $event;
        }
    }
}

beforeEach(function (): void {
    $this->mockResponse = Mockery::mock(Response::class);
});

afterEach(function (): void {
    Mockery::close();
});

describe('StreamResponse', function (): void {
    it('provides access to the underlying Saloon response', function (): void {
        $streamResponse = new StreamResponse($this->mockResponse);

        expect($streamResponse->getResponse())->toBe($this->mockResponse);
    });

    describe('stream()', function (): void {
        it('yields decoded JSON events', function (): void {
            $events = [
                new TestSSEEvent('{"step": 1, "progress": 0.5}'),
                new TestSSEEvent('{"step": 2, "progress": 1.0}'),
            ];

            $eventSource = new TestEventSource($events);
            $this->mockResponse->shouldReceive('asEventSource')->andReturn($eventSource);

            $streamResponse = new StreamResponse($this->mockResponse);

            $results = [];
            foreach ($streamResponse->stream() as $data) {
                $results[] = $data;
            }

            expect($results)->toHaveCount(2)
                ->and($results[0])->toBe(['step' => 1, 'progress' => 0.5])
                ->and($results[1])->toBe(['step' => 2, 'progress' => 1.0]);
        });

        it('skips events with null data', function (): void {
            $events = [
                new TestSSEEvent('{"valid": true}'),
                new TestSSEEvent(null),
                new TestSSEEvent('{"also_valid": true}'),
            ];

            $eventSource = new TestEventSource($events);
            $this->mockResponse->shouldReceive('asEventSource')->andReturn($eventSource);

            $streamResponse = new StreamResponse($this->mockResponse);
            $results = iterator_to_array($streamResponse->stream());

            expect($results)->toHaveCount(2);
        });

        it('skips events with empty string data', function (): void {
            $events = [
                new TestSSEEvent('{"valid": true}'),
                new TestSSEEvent(''),
                new TestSSEEvent('{"also_valid": true}'),
            ];

            $eventSource = new TestEventSource($events);
            $this->mockResponse->shouldReceive('asEventSource')->andReturn($eventSource);

            $streamResponse = new StreamResponse($this->mockResponse);
            $results = iterator_to_array($streamResponse->stream());

            expect($results)->toHaveCount(2);
        });

        it('skips events with invalid JSON', function (): void {
            $events = [
                new TestSSEEvent('{"valid": true}'),
                new TestSSEEvent('not valid json'),
                new TestSSEEvent('{"also_valid": true}'),
            ];

            $eventSource = new TestEventSource($events);
            $this->mockResponse->shouldReceive('asEventSource')->andReturn($eventSource);

            $streamResponse = new StreamResponse($this->mockResponse);
            $results = iterator_to_array($streamResponse->stream());

            expect($results)->toHaveCount(2);
        });
    });

    describe('getResult()', function (): void {
        it('returns the last event data', function (): void {
            $events = [
                new TestSSEEvent('{"step": 1}'),
                new TestSSEEvent('{"step": 2}'),
                new TestSSEEvent('{"step": 3, "final": true}'),
            ];

            $eventSource = new TestEventSource($events);
            $this->mockResponse->shouldReceive('asEventSource')->andReturn($eventSource);

            $streamResponse = new StreamResponse($this->mockResponse);

            expect($streamResponse->getResult())->toBe(['step' => 3, 'final' => true]);
        });

        it('returns null for empty stream', function (): void {
            $eventSource = new TestEventSource([]);
            $this->mockResponse->shouldReceive('asEventSource')->andReturn($eventSource);

            $streamResponse = new StreamResponse($this->mockResponse);

            expect($streamResponse->getResult())->toBeNull();
        });

        it('returns null when all events have invalid data', function (): void {
            $events = [
                new TestSSEEvent(null),
                new TestSSEEvent(''),
                new TestSSEEvent('invalid json'),
            ];

            $eventSource = new TestEventSource($events);
            $this->mockResponse->shouldReceive('asEventSource')->andReturn($eventSource);

            $streamResponse = new StreamResponse($this->mockResponse);

            expect($streamResponse->getResult())->toBeNull();
        });
    });

    describe('run()', function (): void {
        it('is an alias for getResult()', function (): void {
            $events = [
                new TestSSEEvent('{"data": "final"}'),
            ];

            $eventSource = new TestEventSource($events);
            $this->mockResponse->shouldReceive('asEventSource')->andReturn($eventSource);

            $streamResponse = new StreamResponse($this->mockResponse);

            expect($streamResponse->run())->toBe(['data' => 'final']);
        });
    });

    describe('collect()', function (): void {
        it('returns all events as an array', function (): void {
            $events = [
                new TestSSEEvent('{"step": 1}'),
                new TestSSEEvent('{"step": 2}'),
                new TestSSEEvent('{"step": 3}'),
            ];

            $eventSource = new TestEventSource($events);
            $this->mockResponse->shouldReceive('asEventSource')->andReturn($eventSource);

            $streamResponse = new StreamResponse($this->mockResponse);

            expect($streamResponse->collect())->toBe([
                ['step' => 1],
                ['step' => 2],
                ['step' => 3],
            ]);
        });

        it('returns empty array for empty stream', function (): void {
            $eventSource = new TestEventSource([]);
            $this->mockResponse->shouldReceive('asEventSource')->andReturn($eventSource);

            $streamResponse = new StreamResponse($this->mockResponse);

            expect($streamResponse->collect())->toBe([]);
        });
    });
});
