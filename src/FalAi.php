<?php

declare(strict_types=1);

namespace Cjmellor\FalAi;

use Cjmellor\FalAi\Connectors\FalConnector;
use Cjmellor\FalAi\Requests\CancelRequest;
use Cjmellor\FalAi\Requests\FetchRequestStatusRequest;
use Cjmellor\FalAi\Requests\GetResultRequest;
use Cjmellor\FalAi\Requests\SubmitRequest;
use Cjmellor\FalAi\Responses\ResultResponse;
use Cjmellor\FalAi\Responses\StatusResponse;
use Cjmellor\FalAi\Responses\SubmitResponse;
use Cjmellor\FalAi\Support\FluentRequest;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Request;
use Saloon\Http\Response;

class FalAi
{
    protected FalConnector $connector;

    public function __construct()
    {
        $this->connector = new FalConnector;
    }

    /**
     * Create a fluent request builder with the specified model ID
     *
     * @param  string|null  $modelId  The model ID to use (optional, uses default_model from config if null)
     */
    public function model(?string $modelId = null): FluentRequest
    {
        return new FluentRequest($this, $this->resolveModelId($modelId));
    }

    /**
     * Run a request to the Fal.ai API
     *
     * @param  array  $data  The data to submit to the model
     * @param  string|null  $modelId  The model ID to use (optional, uses default_model from config if null)
     *
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function run(array $data, ?string $modelId = null): SubmitResponse
    {
        $response = $this->sendRequest(new SubmitRequest($this->resolveModelId($modelId), $data));

        return new SubmitResponse($response, $response->json());
    }

    /**
     * Run a request to the Fal.ai API with a custom base URL
     *
     * @param  array  $data  The data to submit to the model
     * @param  string|null  $modelId  The model ID to use (optional, uses default_model from config if null)
     * @param  string|null  $baseUrlOverride  The base URL to use for this request
     *
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function runWithBaseUrl(array $data, ?string $modelId = null, ?string $baseUrlOverride = null): SubmitResponse
    {
        $connector = $baseUrlOverride ? $this->createConnectorWithBaseUrl($baseUrlOverride) : $this->connector;
        $response = $connector->send(new SubmitRequest($this->resolveModelId($modelId), $data));

        return new SubmitResponse($response, $response->json());
    }

    /**
     * Get the status of a queued request
     *
     * @param  string  $requestId  The request ID returned from run()
     * @param  bool  $includeLogs  Whether to include logs in the response
     * @param  string|null  $modelId  The model ID (optional, uses default_model from config if null)
     *
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function status(string $requestId, bool $includeLogs = false, ?string $modelId = null): StatusResponse
    {
        $response = $this->sendRequest(new FetchRequestStatusRequest($requestId, $this->resolveModelId($modelId), $includeLogs));

        return new StatusResponse($response, $response->json());
    }

    /**
     * Get the result of a completed request
     *
     * @param  string  $requestId  The request ID to get result for
     * @param  string|null  $modelId  The model ID to use (optional, uses default_model from config if null)
     *
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function result(string $requestId, ?string $modelId = null): ResultResponse
    {
        $response = $this->sendRequest(new GetResultRequest($requestId, $this->resolveModelId($modelId)));

        return new ResultResponse($response, $response->json());
    }

    /**
     * Cancel a queued request that hasn't started processing
     *
     * @param  string  $requestId  The request ID to cancel
     * @param  string|null  $modelId  The model ID to use (optional, uses default_model from config if null)
     *
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function cancel(string $requestId, ?string $modelId = null): Response
    {
        return $this->sendRequest(new CancelRequest($requestId, $this->resolveModelId($modelId)));
    }

    /**
     * Send a request through the connector
     *
     * @throws FatalRequestException
     * @throws RequestException
     */
    private function sendRequest(Request $request): Response
    {
        return $this->connector->send($request);
    }

    /**
     * Create a connector with a custom base URL
     */
    private function createConnectorWithBaseUrl(string $baseUrl): FalConnector
    {
        return new FalConnector($baseUrl);
    }

    /**
     * Resolve the model ID using the provided value or config default
     */
    private function resolveModelId(?string $modelId): ?string
    {
        return $modelId ?? config('fal-ai.default_model') ?: null;
    }
}
