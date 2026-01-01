<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Connectors;

use Saloon\Http\Auth\TokenAuthenticator;
use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;

class FalConnector extends Connector
{
    use AlwaysThrowOnErrors;

    private ?string $baseUrlOverride = null;

    public function __construct(?string $baseUrlOverride = null)
    {
        $this->baseUrlOverride = $baseUrlOverride;
    }

    public function resolveBaseUrl(): string
    {
        return $this->baseUrlOverride ?? config()->string(key: 'fal-ai.base_url');
    }

    protected function defaultAuth(): TokenAuthenticator
    {
        return new TokenAuthenticator(
            token: config()->string(key: 'fal-ai.api_key'),
            prefix: 'Key',
        );
    }
}
