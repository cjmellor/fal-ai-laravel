<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Drivers\Concerns;

/**
 * Provides model ID resolution from config defaults.
 */
trait ResolvesModelId
{
    /**
     * Resolve the model ID using the provided value or config default.
     */
    protected function resolveModelId(?string $modelId): ?string
    {
        return $modelId ?? $this->config['default_model'] ?? null;
    }
}
