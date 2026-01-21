<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Drivers\Replicate\Responses;

use Saloon\Http\Response;

/**
 * Collection wrapper for paginated deployment list responses.
 *
 * Provides iteration and pagination helpers for deployment listings.
 */
class DeploymentsCollection
{
    /** @var array<int, DeploymentResponse> */
    protected array $deployments = [];

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        protected Response $response,
        protected array $data,
    ) {
        $this->hydrate();
    }

    /**
     * Get all deployments in this page.
     *
     * @return array<int, DeploymentResponse>
     */
    public function results(): array
    {
        return $this->deployments;
    }

    /**
     * Get the URL for the next page of results.
     */
    public function next(): ?string
    {
        return $this->data['next'] ?? null;
    }

    /**
     * Get the URL for the previous page of results.
     */
    public function previous(): ?string
    {
        return $this->data['previous'] ?? null;
    }

    /**
     * Check if there are more results available.
     */
    public function hasMore(): bool
    {
        return $this->next() !== null;
    }

    /**
     * Get the count of deployments in this page.
     */
    public function count(): int
    {
        return count($this->deployments);
    }

    /**
     * Get the raw response data.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Get the underlying Saloon response.
     */
    public function getResponse(): Response
    {
        return $this->response;
    }

    /**
     * Hydrate the collection with deployment responses.
     */
    protected function hydrate(): void
    {
        $results = $this->data['results'] ?? [];

        foreach ($results as $deploymentData) {
            $this->deployments[] = new DeploymentResponse($this->response, $deploymentData);
        }
    }
}
