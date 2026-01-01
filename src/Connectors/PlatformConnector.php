<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Connectors;

use Saloon\Http\Auth\TokenAuthenticator;
use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;

class PlatformConnector extends Connector
{
    use AlwaysThrowOnErrors;

    public function resolveBaseUrl(): string
    {
        return config()->string(key: 'fal-ai.platform_base_url');
    }

    protected function defaultAuth(): TokenAuthenticator
    {
        return new TokenAuthenticator(
            token: config()->string(key: 'fal-ai.api_key'),
            prefix: 'Key',
        );
    }
}
