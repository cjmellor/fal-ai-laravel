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
        return config()->string(key: 'fal-ai.drivers.fal.platform_base_url', default: 'https://api.fal.ai');
    }

    protected function defaultAuth(): TokenAuthenticator
    {
        return new TokenAuthenticator(
            token: config()->string(key: 'fal-ai.drivers.fal.api_key'),
            prefix: 'Key',
        );
    }
}
