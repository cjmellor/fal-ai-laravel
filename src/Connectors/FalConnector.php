<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Connectors;

use InvalidArgumentException;
use Saloon\Http\Auth\TokenAuthenticator;
use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;

class FalConnector extends Connector
{
    use AlwaysThrowOnErrors;

    public function __construct(
        protected ?string $baseUrlOverride = null
    ) {}

    public function resolveBaseUrl(): string
    {
        return $this->baseUrlOverride ?? config()->string(key: 'fal-ai.drivers.fal.base_url', default: 'https://queue.fal.run');
    }

    protected function defaultAuth(): TokenAuthenticator
    {
        $apiKey = config()->string(key: 'fal-ai.drivers.fal.api_key');

        throw_if(
            blank($apiKey),
            InvalidArgumentException::class,
            'Fal API key is not configured. Set FAL_API_KEY in your .env file.'
        );

        return new TokenAuthenticator(token: $apiKey, prefix: 'Key');
    }

    protected function defaultHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
        ];
    }
}
