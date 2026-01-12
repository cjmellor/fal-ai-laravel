<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Drivers\Fal;

use Cjmellor\FalAi\Connectors\FalConnector;
use Cjmellor\FalAi\Contracts\DriverInterface;
use Cjmellor\FalAi\Contracts\SupportsPlatform;
use Cjmellor\FalAi\Drivers\Concerns\ResolvesModelId;
use Cjmellor\FalAi\Enums\RequestMode;
use Cjmellor\FalAi\Platform;
use Cjmellor\FalAi\Requests\CancelRequest;
use Cjmellor\FalAi\Requests\FetchRequestStatusRequest;
use Cjmellor\FalAi\Requests\GetResultRequest;
use Cjmellor\FalAi\Requests\StreamRequest;
use Cjmellor\FalAi\Requests\SubmitRequest;
use Cjmellor\FalAi\Responses\ResultResponse;
use Cjmellor\FalAi\Responses\StatusResponse;
use Cjmellor\FalAi\Responses\StreamResponse;
use Cjmellor\FalAi\Responses\SubmitResponse;
use Cjmellor\FalAi\Support\FluentRequest;

class FalDriver implements DriverInterface, SupportsPlatform
{
    use ResolvesModelId;

    protected FalConnector $connector;

    protected FalConnector $syncConnector;

    /** @var array<string, mixed> */
    protected array $config;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->connector = new FalConnector($this->getBaseUrl());
        $this->syncConnector = new FalConnector($this->getSyncUrl());
    }

    /**
     * Get the driver name.
     */
    public function getName(): string
    {
        return 'fal';
    }

    /**
     * Create a fluent request builder for this driver.
     */
    public function model(string $modelId): FluentRequest
    {
        return new FluentRequest($this, $modelId);
    }

    /**
     * Execute a model and return the result/status.
     */
    public function run(FluentRequest $request): SubmitResponse
    {
        $connector = $this->getConnectorForMode($request->getMode());

        $submitRequest = new SubmitRequest(
            $this->resolveModelId($request->getModel()),
            $request->getInput(),
            $request->getWebhook()
        );

        $response = $connector->send($submitRequest);

        return new SubmitResponse($response, $response->json());
    }

    /**
     * Check the status of a running request.
     */
    public function status(string $requestId, ?string $model = null): StatusResponse
    {
        $response = $this->connector->send(
            new FetchRequestStatusRequest($requestId, $this->resolveModelId($model))
        );

        return new StatusResponse($response, $response->json());
    }

    /**
     * Get the result of a completed request.
     */
    public function result(string $requestId, ?string $model = null): ResultResponse
    {
        $response = $this->connector->send(
            new GetResultRequest($requestId, $this->resolveModelId($model))
        );

        return new ResultResponse($response, $response->json());
    }

    /**
     * Cancel a running request.
     */
    public function cancel(string $requestId, ?string $model = null): bool
    {
        $response = $this->connector->send(
            new CancelRequest($requestId, $this->resolveModelId($model))
        );

        return $response->successful();
    }

    /**
     * Stream model execution.
     */
    public function stream(FluentRequest $request): StreamResponse
    {
        $response = $this->syncConnector->send(
            new StreamRequest($this->resolveModelId($request->getModel()), $request->getInput())
        );

        return new StreamResponse($response);
    }

    /**
     * Access platform APIs (pricing, usage, analytics).
     */
    public function platform(): Platform
    {
        return new Platform;
    }

    /**
     * Get the base URL for queue operations.
     */
    protected function getBaseUrl(): string
    {
        return $this->config['base_url'] ?? 'https://queue.fal.run';
    }

    /**
     * Get the sync URL for synchronous operations and streaming.
     */
    protected function getSyncUrl(): string
    {
        return $this->config['sync_url'] ?? 'https://fal.run';
    }

    /**
     * Get the appropriate connector based on the request mode.
     */
    protected function getConnectorForMode(RequestMode $mode): FalConnector
    {
        return match ($mode) {
            RequestMode::Sync, RequestMode::Stream => $this->syncConnector,
            RequestMode::Queue => $this->connector,
        };
    }
}
