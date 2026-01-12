<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Requests;

use Cjmellor\FalAi\Exceptions\InvalidModelException;
use HosmelQ\SSE\Saloon\Traits\HasServerSentEvents;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;
use Throwable;

class StreamRequest extends Request implements HasBody
{
    use HasJsonBody, HasServerSentEvents {
        HasServerSentEvents::defaultHeaders as defaultSSEHeaders;
    }

    protected Method $method = Method::POST;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        protected readonly ?string $modelId = null,
        /** @var array<string, mixed> */
        protected readonly array $data = [],
    ) {}

    /**
     * @throws Throwable
     */
    public function resolveEndpoint(): string
    {
        $modelId = $this->modelId ?? config()->string(key: 'fal-ai.default_model');

        throw_if(
            condition: blank($modelId),
            exception: new InvalidModelException(message: 'Model ID cannot be empty')
        );

        // Always append /stream to the endpoint for streaming requests
        return "$modelId/stream";
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultBody(): array
    {
        return $this->data;
    }
}
