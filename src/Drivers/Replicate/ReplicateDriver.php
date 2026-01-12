<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Drivers\Replicate;

use Cjmellor\FalAi\Contracts\DriverInterface;
use Cjmellor\FalAi\Drivers\Concerns\ResolvesModelId;
use Cjmellor\FalAi\Drivers\Replicate\Requests\CancelPredictionRequest;
use Cjmellor\FalAi\Drivers\Replicate\Requests\CreatePredictionRequest;
use Cjmellor\FalAi\Drivers\Replicate\Requests\GetPredictionRequest;
use Cjmellor\FalAi\Drivers\Replicate\Responses\PredictionResponse;
use Cjmellor\FalAi\Exceptions\PlatformNotSupportedException;
use Cjmellor\FalAi\Exceptions\RequestFailedException;
use Cjmellor\FalAi\Support\FluentRequest;

/**
 * Driver for the Replicate API.
 *
 * Provides model execution capabilities via Replicate's predictions API.
 * Does not support Platform APIs (pricing, usage, analytics) - those are Fal-only.
 */
class ReplicateDriver implements DriverInterface
{
    use ResolvesModelId;

    protected ReplicateConnector $connector;

    /** @var array<string, mixed> */
    protected array $config;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->connector = new ReplicateConnector($this->getBaseUrl());
    }

    /**
     * Get the driver name.
     */
    public function getName(): string
    {
        return 'replicate';
    }

    /**
     * Create a fluent request builder for this driver.
     */
    public function model(string $modelId): FluentRequest
    {
        return new FluentRequest($this, $modelId);
    }

    /**
     * Execute a model and return the prediction response.
     */
    public function run(FluentRequest $request): PredictionResponse
    {
        $webhookUrl = $request->getWebhook();

        $createRequest = new CreatePredictionRequest(
            version: $this->resolveModelId($request->getModel()),
            input: $request->getInput(),
            webhookUrl: $webhookUrl,
            webhookEventsFilter: $webhookUrl ? ['completed'] : [],
        );

        $response = $this->connector->send($createRequest);

        return new PredictionResponse($response, $response->json());
    }

    /**
     * Check the status of a running prediction.
     *
     * Note: Replicate combines status and result in one endpoint.
     * The $model parameter is ignored as Replicate uses prediction IDs globally.
     */
    public function status(string $requestId, ?string $model = null): PredictionResponse
    {
        $response = $this->connector->send(new GetPredictionRequest($requestId));

        return new PredictionResponse($response, $response->json());
    }

    /**
     * Get the result of a completed prediction.
     *
     * Note: Replicate combines status and result in one endpoint.
     * The $model parameter is ignored as Replicate uses prediction IDs globally.
     */
    public function result(string $requestId, ?string $model = null): PredictionResponse
    {
        // Replicate uses the same endpoint for status and result
        return $this->status($requestId, $model);
    }

    /**
     * Cancel a running prediction.
     *
     * The $model parameter is ignored as Replicate uses prediction IDs globally.
     */
    public function cancel(string $requestId, ?string $model = null): bool
    {
        $response = $this->connector->send(new CancelPredictionRequest($requestId));

        return $response->successful();
    }

    /**
     * Stream model execution.
     *
     * @throws RequestFailedException Replicate streaming requires polling or SSE which is not yet implemented
     */
    public function stream(FluentRequest $request): mixed
    {
        // For now, run normally - Replicate's streaming uses SSE which requires different handling
        // Users can poll using status() to get updates
        throw new RequestFailedException(
            'Streaming is not yet supported for the Replicate driver. Use run() and poll status() instead.'
        );
    }

    /**
     * Access Platform APIs.
     *
     * @throws PlatformNotSupportedException Replicate does not support Platform APIs
     */
    public function platform(): never
    {
        throw PlatformNotSupportedException::forDriver('replicate');
    }

    /**
     * Get the base URL for API requests.
     */
    protected function getBaseUrl(): string
    {
        return $this->config['base_url'] ?? 'https://api.replicate.com';
    }
}
