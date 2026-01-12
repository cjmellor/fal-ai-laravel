<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Drivers\Replicate;

use InvalidArgumentException;
use Saloon\Http\Auth\TokenAuthenticator;
use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;

/**
 * Saloon connector for the Replicate API.
 *
 * Uses Bearer token authentication (standard OAuth2 style).
 */
class ReplicateConnector extends Connector
{
    use AlwaysThrowOnErrors;

    public function __construct(
        protected ?string $baseUrlOverride = null
    ) {}

    public function resolveBaseUrl(): string
    {
        return $this->baseUrlOverride ?? config()->string(
            key: 'fal-ai.drivers.replicate.base_url',
            default: 'https://api.replicate.com'
        );
    }

    protected function defaultAuth(): TokenAuthenticator
    {
        $apiKey = config()->string(key: 'fal-ai.drivers.replicate.api_key');

        throw_if(
            blank($apiKey),
            InvalidArgumentException::class,
            'Replicate API key is not configured. Set REPLICATE_API_KEY in your .env file.'
        );

        return new TokenAuthenticator(token: $apiKey, prefix: 'Bearer');
    }

    protected function defaultHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
        ];
    }
}
