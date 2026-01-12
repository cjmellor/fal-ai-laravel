<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Requests;

use Cjmellor\FalAi\Exceptions\InvalidModelException;
use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetResultRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected readonly ?string $requestId,
        protected readonly ?string $modelId = null,
    ) {}

    public function resolveEndpoint(): string
    {
        $modelId = $this->modelId ?? config()->string(key: 'fal-ai.default_model');

        throw_if(
            condition: blank($modelId),
            exception: new InvalidModelException('Model ID cannot be empty')
        );

        return "{$modelId}/requests/{$this->requestId}";
    }
}
