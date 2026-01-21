<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Drivers\Replicate\Support;

use Cjmellor\FalAi\Drivers\Replicate\ReplicateConnector;
use Cjmellor\FalAi\Drivers\Replicate\Requests\Deployments\CreateDeploymentRequest;
use Cjmellor\FalAi\Drivers\Replicate\Requests\Deployments\UpdateDeploymentRequest;
use Cjmellor\FalAi\Drivers\Replicate\Responses\DeploymentResponse;
use Cjmellor\FalAi\Exceptions\InvalidConfigurationException;

/**
 * Fluent builder for creating and updating Replicate deployments.
 *
 * For create: FalAi::driver('replicate')->deployments()->create('name')
 *     ->model('owner/model')->version('abc...')->hardware('gpu-t4')->instances(1, 5)->save()
 *
 * For update: FalAi::driver('replicate')->deployments()->update('owner', 'name')
 *     ->hardware('gpu-a40-small')->instances(2, 10)->save()
 */
final class DeploymentBuilder
{
    private ?string $model = null;

    private ?string $version = null;

    private ?string $hardware = null;

    private ?int $minInstances = null;

    private ?int $maxInstances = null;

    public function __construct(
        private ReplicateConnector $connector,
        private string $name,
        private bool $isUpdate = false,
        private ?string $owner = null,
    ) {}

    /**
     * Set the model for the deployment (owner/name format).
     */
    public function model(string $model): self
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Set the model version for the deployment (64 char hex).
     */
    public function version(string $version): self
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Set the hardware SKU for the deployment.
     */
    public function hardware(string $hardware): self
    {
        $this->hardware = $hardware;

        return $this;
    }

    /**
     * Set the instance scaling limits.
     */
    public function instances(int $min, int $max): self
    {
        $this->minInstances = $min;
        $this->maxInstances = $max;

        return $this;
    }

    /**
     * Save the deployment (create or update).
     *
     * @throws InvalidConfigurationException When required fields are missing
     */
    public function save(): DeploymentResponse
    {
        if ($this->isUpdate) {
            return $this->performUpdate();
        }

        return $this->performCreate();
    }

    /**
     * Perform a create operation.
     *
     * @throws InvalidConfigurationException When required fields are missing
     */
    private function performCreate(): DeploymentResponse
    {
        $this->validateCreateFields();

        $request = new CreateDeploymentRequest(
            name: $this->name,
            model: $this->model,
            version: $this->version,
            hardware: $this->hardware,
            minInstances: $this->minInstances,
            maxInstances: $this->maxInstances,
        );

        $response = $this->connector->send($request);

        return new DeploymentResponse($response, $response->json());
    }

    /**
     * Perform an update operation.
     */
    private function performUpdate(): DeploymentResponse
    {
        $updates = $this->buildUpdatePayload();

        $request = new UpdateDeploymentRequest(
            owner: $this->owner,
            name: $this->name,
            updates: $updates,
        );

        $response = $this->connector->send($request);

        return new DeploymentResponse($response, $response->json());
    }

    /**
     * Validate that all required fields are set for create.
     *
     * @throws InvalidConfigurationException When required fields are missing
     */
    private function validateCreateFields(): void
    {
        $missing = [];

        if ($this->model === null) {
            $missing[] = 'model';
        }

        if ($this->version === null) {
            $missing[] = 'version';
        }

        if ($this->hardware === null) {
            $missing[] = 'hardware';
        }

        if ($this->minInstances === null || $this->maxInstances === null) {
            $missing[] = 'instances (min and max)';
        }

        throw_if(
            count($missing) > 0,
            new InvalidConfigurationException('Missing required deployment fields: '.implode(', ', $missing))
        );
    }

    /**
     * Build the update payload with only set fields.
     *
     * @return array<string, mixed>
     */
    private function buildUpdatePayload(): array
    {
        $updates = [];

        if ($this->model !== null) {
            $updates['model'] = $this->model;
        }

        if ($this->version !== null) {
            $updates['version'] = $this->version;
        }

        if ($this->hardware !== null) {
            $updates['hardware'] = $this->hardware;
        }

        if ($this->minInstances !== null) {
            $updates['min_instances'] = $this->minInstances;
        }

        if ($this->maxInstances !== null) {
            $updates['max_instances'] = $this->maxInstances;
        }

        return $updates;
    }
}
