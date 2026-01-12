<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Support;

use Cjmellor\FalAi\Contracts\DriverInterface;
use Cjmellor\FalAi\Enums\RequestMode;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Conditionable;
use InvalidArgumentException;
use JsonException;
use Throwable;

/**
 * Fluent request builder for AI model execution.
 *
 * Provides a chainable interface for building model requests.
 * Works with any driver implementing DriverInterface.
 *
 * @method self prompt(string $value) Set the prompt
 * @method self imageSize(string $value) Set the image size
 * @method self numImages(int $value) Set the number of images
 * @method self numOutputs(int $value) Set the number of outputs (Replicate)
 * @method self numInferenceSteps(int $value) Set inference steps
 * @method self seed(int $value) Set the seed
 * @method self guidanceScale(float $value) Set guidance scale
 * @method self negativePrompt(string $value) Set negative prompt
 * @method self imageUrl(string $value) Set image URL
 * @method self maskUrl(string $value) Set mask URL
 * @method self strength(float $value) Set strength
 * @method self enableSafetyChecker(bool $value) Enable/disable safety checker
 * @method self outputFormat(string $value) Set output format
 * @method self promptImmutable(string $value) Set the prompt (immutable)
 * @method self imageSizeImmutable(string $value) Set the image size (immutable)
 */
class FluentRequest
{
    use Conditionable;

    /**
     * The input data for the request
     *
     * @var array<string, mixed>
     */
    public private(set) array $data = [];

    /**
     * The webhook URL for async notifications
     */
    public private(set) ?string $webhookUrl = null;

    /**
     * The request mode (queue, sync, stream)
     */
    protected RequestMode $mode = RequestMode::Queue;

    /**
     * The model identifier
     */
    protected ?string $modelId;

    /**
     * The driver instance that created this request
     */
    protected DriverInterface $driver;

    public function __construct(DriverInterface $driver, ?string $modelId = null)
    {
        $this->driver = $driver;
        $this->modelId = $modelId;
    }

    /**
     * Handle dynamic method calls for fluent interface
     *
     * @param  array<int, mixed>  $arguments
     */
    public function __call(string $method, array $arguments): self
    {
        // Check if this is an immutable call (ends with 'Immutable')
        if (Str::endsWith($method, 'Immutable')) {
            $actualMethod = Str::beforeLast($method, 'Immutable');

            return $this->__callImmutable($actualMethod, $arguments);
        }

        // Convert camelCase method names to snake_case for API compatibility
        $key = Str::snake($method);

        // Set the value (first argument)
        $this->data[$key] = $arguments[0] ?? null;

        return $this;
    }

    /**
     * Handle dynamic method calls for immutable fluent interface
     *
     * @param  string  $method  The method name (will be converted to snake_case)
     * @param  array<int, mixed>  $arguments  The method arguments (first argument becomes the value)
     * @return self New instance with the data set
     *
     * @example $newRequest = $request->promptImmutable('Hello world')
     * @example $newRequest = $request->imageSizeImmutable('1024x1024')
     */
    public function __callImmutable(string $method, array $arguments): self
    {
        $clone = clone $this;
        $key = Str::snake($method);
        $clone->data[$key] = $arguments[0] ?? null;

        return $clone;
    }

    /**
     * Set the request to use the sync endpoint
     */
    public function sync(): self
    {
        $this->mode = RequestMode::Sync;

        return $this;
    }

    /**
     * Set the request to use the queue endpoint explicitly
     */
    public function queue(): self
    {
        $this->mode = RequestMode::Queue;

        return $this;
    }

    /**
     * Set the webhook URL for asynchronous notifications
     * Automatically switches to queue mode when webhook is specified
     *
     * @throws Throwable
     */
    public function withWebhook(string $url): self
    {
        throw_unless(
            Str::isUrl($url),
            InvalidArgumentException::class,
            'Invalid webhook URL provided'
        );

        throw_unless(
            Str::startsWith(haystack: $url, needles: 'https://'),
            InvalidArgumentException::class,
            'Webhook URL must use HTTPS'
        );

        $this->webhookUrl = $url;

        // Automatically switch to queue mode when webhook is specified
        $this->mode = RequestMode::Queue;

        return $this;
    }

    /**
     * Execute the request
     */
    public function run(): mixed
    {
        return $this->driver->run($this);
    }

    /**
     * Execute the request with streaming response
     */
    public function stream(): mixed
    {
        $this->mode = RequestMode::Stream;

        return $this->driver->stream($this);
    }

    /**
     * Get the model identifier
     */
    public function getModel(): ?string
    {
        return $this->modelId;
    }

    /**
     * Get the input data
     *
     * @return array<string, mixed>
     */
    public function getInput(): array
    {
        return $this->data;
    }

    /**
     * Get the request mode (queue, sync, stream)
     */
    public function getMode(): RequestMode
    {
        return $this->mode;
    }

    /**
     * Get the webhook URL if set
     */
    public function getWebhook(): ?string
    {
        return $this->webhookUrl;
    }

    /**
     * Get all data as an array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Get all data as JSON
     *
     * @throws JsonException
     */
    public function toJson(): string
    {
        return json_encode($this->data, JSON_THROW_ON_ERROR);
    }

    /**
     * Set multiple data values at once
     *
     * @param  array<string, mixed>  $data
     */
    public function with(array $data): self
    {
        $this->data = array_merge($this->data, $data);

        return $this;
    }

    /**
     * Set multiple data values at once (immutable - returns new instance)
     *
     * @param  array<string, mixed>  $data
     */
    public function withImmutable(array $data): self
    {
        $clone = clone $this;
        $clone->data = array_merge($this->data, $data);

        return $clone;
    }
}
