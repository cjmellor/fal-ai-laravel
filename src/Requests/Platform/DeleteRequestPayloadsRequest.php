<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Requests\Platform;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class DeleteRequestPayloadsRequest extends Request
{
    protected Method $method = Method::DELETE;

    public function __construct(
        protected readonly string $requestId,
        protected readonly ?string $idempotencyKey = null,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/v1/models/requests/'.$this->requestId.'/payloads';
    }

    /**
     * @return array<string, string>
     */
    protected function defaultHeaders(): array
    {
        if ($this->idempotencyKey === null) {
            return [];
        }

        return [
            'Idempotency-Key' => $this->idempotencyKey,
        ];
    }
}
