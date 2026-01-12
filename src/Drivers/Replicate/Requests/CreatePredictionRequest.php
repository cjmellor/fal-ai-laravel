<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Drivers\Replicate\Requests;

use Cjmellor\FalAi\Exceptions\InvalidModelException;
use Illuminate\Support\Str;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;
use Throwable;

/**
 * Create a new prediction on Replicate.
 *
 * @see https://replicate.com/docs/reference/http#create-a-prediction
 */
class CreatePredictionRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    /**
     * @param  string|null  $version  The model version ID (64 char hex) or "owner/model:version" format
     * @param  array  $input  The model input parameters
     * @param  string|null  $webhookUrl  Optional webhook URL for async notifications
     * @param  array  $webhookEventsFilter  Events to filter for webhook (start, output, logs, completed)
     */
    public function __construct(
        protected readonly ?string $version = null,
        protected readonly array $input = [],
        protected readonly ?string $webhookUrl = null,
        protected readonly array $webhookEventsFilter = [],
    ) {}

    public function resolveEndpoint(): string
    {
        return '/v1/predictions';
    }

    /**
     * @throws Throwable
     */
    public function defaultBody(): array
    {
        $version = $this->version ?? config()->string(key: 'fal-ai.drivers.replicate.default_model');

        throw_if(
            condition: blank($version),
            exception: new InvalidModelException('Model version cannot be empty')
        );

        $body = [
            'version' => $this->extractVersionId($version),
            'input' => $this->input,
        ];

        if (filled($this->webhookUrl)) {
            $body['webhook'] = $this->webhookUrl;
        }

        if (filled($this->webhookEventsFilter)) {
            $body['webhook_events_filter'] = $this->webhookEventsFilter;
        }

        return $body;
    }

    /**
     * Extract the version ID from various input formats.
     *
     * Supports:
     * - 64 char hex version ID directly
     * - "owner/model:version" format (extracts version after colon)
     */
    protected function extractVersionId(string $version): string
    {
        // If it contains a colon, extract the version after it
        if (Str::contains($version, ':')) {
            return Str::after($version, ':');
        }

        return $version;
    }
}
