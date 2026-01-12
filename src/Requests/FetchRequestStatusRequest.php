<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Requests;

use Cjmellor\FalAi\Exceptions\InvalidModelException;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Throwable;

class FetchRequestStatusRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected readonly ?string $requestId,
        protected readonly ?string $modelId = null,
        protected readonly bool $includeLogs = false,
    ) {}

    /**
     * @throws Throwable
     */
    public function resolveEndpoint(): string
    {
        $modelId = $this->modelId ?? config()->string(key: 'fal-ai.default_model');

        throw_if(
            condition: blank($modelId),
            exception: new InvalidModelException('Model ID cannot be empty')
        );

        $endpoint = "{$modelId}/requests/{$this->requestId}/status";

        if ($this->includeLogs) {
            $endpoint .= '?logs=1';
        }

        return $endpoint;
    }
}
