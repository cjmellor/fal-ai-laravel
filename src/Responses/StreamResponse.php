<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Responses;

use Generator;
use HosmelQ\SSE\SSEProtocolException;
use Saloon\Http\Response;

readonly class StreamResponse
{
    public function __construct(
        private Response $response
    ) {}

    /**
     * Get the underlying Saloon response
     */
    public function getResponse(): Response
    {
        return $this->response;
    }

    /**
     * Execute the streaming request and return the final result
     * This method provides consistency with the non-streaming API's run() method
     *
     * @return array|null The final result data or null if no data received
     *
     * @throws SSEProtocolException
     */
    public function run(): ?array
    {
        return $this->getResult();
    }

    /**
     * Get the final result from the stream (blocks until stream is complete)
     * Returns the last event data, which typically contains the final result
     *
     * @return array|null The final result data or null if no data received
     *
     * @throws SSEProtocolException
     */
    public function getResult(): ?array
    {
        $lastResult = null;

        foreach ($this->stream() as $data) {
            $lastResult = $data;
        }

        return $lastResult;
    }

    /**
     * Stream the events as they come in
     *
     * @return Generator<array> Generator yielding decoded JSON data from each SSE event
     *
     * @throws SSEProtocolException
     */
    public function stream(): Generator
    {
        foreach ($this->response->asEventSource()->events() as $event) {
            if ($event->data !== null && $event->data !== '') {
                $data = json_decode($event->data, associative: true);

                if ($data !== null) {
                    yield $data;
                }
            }
        }
    }

    /**
     * Collect all streamed data into an array (blocks until stream is complete)
     *
     * @return array Array of all event data received
     *
     * @throws SSEProtocolException
     */
    public function collect(): array
    {
        return iterator_to_array($this->stream());
    }
}
