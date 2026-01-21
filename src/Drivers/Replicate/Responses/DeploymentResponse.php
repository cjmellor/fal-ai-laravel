<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Drivers\Replicate\Responses;

use Cjmellor\FalAi\Responses\AbstractResponse;

/**
 * Response wrapper for Replicate deployment operations.
 *
 * Wraps the raw Saloon response with typed accessors for deployment data.
 */
class DeploymentResponse extends AbstractResponse
{
    /**
     * Get the deployment owner
     */
    public ?string $owner {
        get => $this->data['owner'] ?? null;
    }

    /**
     * Get the deployment name
     */
    public ?string $name {
        get => $this->data['name'] ?? null;
    }

    /**
     * Get the current release information
     *
     * @var array<string, mixed>|null
     */
    public ?array $currentRelease {
        get => $this->data['current_release'] ?? null;
    }

    /**
     * Get the hardware SKU from current release
     */
    public function hardware(): ?string
    {
        return $this->currentRelease['hardware'] ?? null;
    }

    /**
     * Get the model identifier from current release
     */
    public function model(): ?string
    {
        return $this->currentRelease['model'] ?? null;
    }

    /**
     * Get the model version from current release
     */
    public function version(): ?string
    {
        return $this->currentRelease['version'] ?? null;
    }

    /**
     * Get the minimum instances from current release
     */
    public function minInstances(): ?int
    {
        $value = $this->currentRelease['min_instances'] ?? null;

        return $value !== null ? (int) $value : null;
    }

    /**
     * Get the maximum instances from current release
     */
    public function maxInstances(): ?int
    {
        $value = $this->currentRelease['max_instances'] ?? null;

        return $value !== null ? (int) $value : null;
    }

    /**
     * Get the release number from current release
     */
    public function releaseNumber(): ?int
    {
        $value = $this->currentRelease['number'] ?? null;

        return $value !== null ? (int) $value : null;
    }

    /**
     * Get the created_at timestamp from current release
     */
    public function createdAt(): ?string
    {
        return $this->currentRelease['created_at'] ?? null;
    }

    /**
     * Get the created_by information from current release
     *
     * @return array<string, mixed>|null
     */
    public function createdBy(): ?array
    {
        return $this->currentRelease['created_by'] ?? null;
    }
}
