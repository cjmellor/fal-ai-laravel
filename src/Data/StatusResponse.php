<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Data;

class StatusResponse
{
    public function __construct(
        public readonly string $status,
        public readonly ?int $queuePosition = null,
        public readonly ?string $responseUrl = null,
        public readonly ?array $logs = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            status: $data['status'],
            queuePosition: $data['queue_position'] ?? null,
            responseUrl: $data['response_url'] ?? null,
            logs: $data['logs'] ?? null,
        );
    }

    public function toArray(): array
    {
        $result = ['status' => $this->status];

        if ($this->queuePosition !== null) {
            $result['queue_position'] = $this->queuePosition;
        }

        if ($this->responseUrl !== null) {
            $result['response_url'] = $this->responseUrl;
        }

        if ($this->logs !== null) {
            $result['logs'] = $this->logs;
        }

        return $result;
    }

    public function isInQueue(): bool
    {
        return $this->status === 'IN_QUEUE';
    }

    public function isInProgress(): bool
    {
        return $this->status === 'IN_PROGRESS';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'COMPLETED';
    }
}
