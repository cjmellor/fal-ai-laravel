<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Drivers\Replicate\Support;

use Cjmellor\FalAi\Drivers\Replicate\ReplicateConnector;
use Cjmellor\FalAi\Drivers\Replicate\Requests\Deployments\CreateDeploymentPredictionRequest;
use Cjmellor\FalAi\Drivers\Replicate\Responses\PredictionResponse;

/**
 * Fluent builder for creating predictions via a deployment.
 *
 * Usage: FalAi::driver('replicate')->deployment('owner/name')
 *     ->with(['prompt' => 'A sunset'])->webhook('https://...')->run()
 */
final class DeploymentPredictionRequest
{
    /** @var array<string, mixed> */
    private array $input = [];

    private ?string $webhookUrl = null;

    /** @var array<int, string> */
    private array $webhookEvents = ['completed'];

    public function __construct(
        private ReplicateConnector $connector,
        private string $owner,
        private string $name,
    ) {}

    /**
     * Set the input parameters for the prediction.
     *
     * @param  array<string, mixed>  $input
     */
    public function with(array $input): self
    {
        $this->input = $input;

        return $this;
    }

    /**
     * Set a webhook URL for async notifications.
     *
     * @param  array<int, string>  $events  Events to subscribe to (start, output, logs, completed)
     */
    public function webhook(string $url, array $events = ['completed']): self
    {
        $this->webhookUrl = $url;
        $this->webhookEvents = $events;

        return $this;
    }

    /**
     * Execute the prediction.
     */
    public function run(): PredictionResponse
    {
        $request = new CreateDeploymentPredictionRequest(
            owner: $this->owner,
            name: $this->name,
            input: $this->input,
            webhookUrl: $this->webhookUrl,
            webhookEventsFilter: $this->webhookUrl ? $this->webhookEvents : [],
        );

        $response = $this->connector->send($request);

        return new PredictionResponse($response, $response->json());
    }
}
