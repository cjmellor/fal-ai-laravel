<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Drivers\Replicate\Support;

use Cjmellor\FalAi\Drivers\Replicate\ReplicateConnector;
use Cjmellor\FalAi\Drivers\Replicate\Requests\Deployments\DeleteDeploymentRequest;
use Cjmellor\FalAi\Drivers\Replicate\Requests\Deployments\GetDeploymentRequest;
use Cjmellor\FalAi\Drivers\Replicate\Requests\Deployments\ListDeploymentsRequest;
use Cjmellor\FalAi\Drivers\Replicate\Responses\DeploymentResponse;
use Cjmellor\FalAi\Drivers\Replicate\Responses\DeploymentsCollection;

/**
 * Manager class for Replicate deployments CRUD operations.
 *
 * Provides fluent access to deployment listing, retrieval, creation, update, and deletion.
 */
final class DeploymentsManager
{
    public function __construct(
        private ReplicateConnector $connector,
    ) {}

    /**
     * List all deployments for the authenticated user.
     */
    public function list(): DeploymentsCollection
    {
        $response = $this->connector->send(new ListDeploymentsRequest);

        return new DeploymentsCollection($response, $response->json());
    }

    /**
     * Get a specific deployment by owner and name.
     */
    public function get(string $owner, string $name): DeploymentResponse
    {
        $response = $this->connector->send(new GetDeploymentRequest($owner, $name));

        return new DeploymentResponse($response, $response->json());
    }

    /**
     * Create a new deployment builder.
     */
    public function create(string $name): DeploymentBuilder
    {
        return new DeploymentBuilder($this->connector, $name, isUpdate: false);
    }

    /**
     * Update an existing deployment.
     */
    public function update(string $owner, string $name): DeploymentBuilder
    {
        return new DeploymentBuilder($this->connector, $name, isUpdate: true, owner: $owner);
    }

    /**
     * Delete a deployment.
     *
     * @return bool True if deletion was successful
     */
    public function delete(string $owner, string $name): bool
    {
        $response = $this->connector->send(new DeleteDeploymentRequest($owner, $name));

        return $response->successful();
    }
}
