<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Drivers\Replicate\Requests\Deployments;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

/**
 * Create a prediction using a specific deployment.
 *
 * @see https://replicate.com/docs/reference/http#create-a-prediction-using-a-deployment
 */
class CreateDeploymentPredictionRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    /**
     * @param  string  $owner  The owner of the deployment
     * @param  string  $name  The name of the deployment
     * @param  array<string, mixed>  $input  The model input parameters
     * @param  string|null  $webhookUrl  Optional webhook URL for async notifications
     * @param  array<int, string>  $webhookEventsFilter  Events to filter for webhook
     */
    public function __construct(
        protected readonly string $owner,
        protected readonly string $name,
        protected readonly array $input = [],
        protected readonly ?string $webhookUrl = null,
        protected readonly array $webhookEventsFilter = [],
    ) {}

    public function resolveEndpoint(): string
    {
        return "/v1/deployments/{$this->owner}/{$this->name}/predictions";
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultBody(): array
    {
        $body = [
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
}
