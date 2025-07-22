<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Connectors;

use Saloon\Http\Auth\TokenAuthenticator;
use Saloon\Http\Connector;

class FalConnector extends Connector
{
    public function resolveBaseUrl(): string
    {
        return config()->string(key: 'fal-ai.base_url');
    }

    protected function defaultAuth(): TokenAuthenticator
    {
        return new TokenAuthenticator(
            token: config()->string(key: 'fal-ai.api_key'),
            prefix: 'Key',
        );
    }

    /**
     * The `HasJsonBody` trait automatically sets the Content-Type header to application/json.
     */
    // public function defaultHeaders(): array
    // {
    //     return [
    //         'Content-Type' => 'application/json',
    //     ];
    // }
}
