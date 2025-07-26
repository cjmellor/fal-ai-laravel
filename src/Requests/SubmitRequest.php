<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Requests;

use Cjmellor\FalAi\Exceptions\InvalidModelException;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;
use Throwable;

class SubmitRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected readonly ?string $modelId = null,
        protected readonly array $data = [],
        protected readonly ?string $webhookUrl = null,
    ) {}

    /**
     * @throws Throwable
     */
    public function resolveEndpoint(): string
    {
        $modelId = $this->modelId ?? config()->string(key: 'fal-ai.default_model');

        throw_if(
            condition: empty($modelId),
            exception: new InvalidModelException('Model ID cannot be empty')
        );

        return $modelId;
    }

    public function defaultBody(): array
    {
        return $this->data;
    }

    public function defaultQuery(): array
    {
        $query = [];

        if ($this->webhookUrl) {
            $query['fal_webhook'] = $this->webhookUrl;
        }

        return $query;
    }
}
