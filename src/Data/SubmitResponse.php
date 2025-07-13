<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Data;

class SubmitResponse
{
    public function __construct(
        public readonly string $requestId,
        public readonly string $responseUrl,
        public readonly string $statusUrl,
        public readonly string $cancelUrl,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            requestId: $data['request_id'],
            responseUrl: $data['response_url'],
            statusUrl: $data['status_url'],
            cancelUrl: $data['cancel_url'],
        );
    }

    public function toArray(): array
    {
        return [
            'request_id' => $this->requestId,
            'response_url' => $this->responseUrl,
            'status_url' => $this->statusUrl,
            'cancel_url' => $this->cancelUrl,
        ];
    }
}
