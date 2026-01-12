<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Drivers\Replicate\Responses;

use Cjmellor\FalAi\Drivers\Replicate\Enums\PredictionStatus;
use Cjmellor\FalAi\Responses\AbstractResponse;

/**
 * Response wrapper for Replicate prediction operations.
 *
 * Wraps the raw Saloon response with typed accessors for prediction data.
 */
class PredictionResponse extends AbstractResponse
{
    /**
     * Get the prediction ID
     */
    public string $id {
        get => $this->data['id'] ?? '';
    }

    /**
     * Get the prediction status as a string
     *
     * @deprecated Use predictionStatusEnum() method for type-safe access
     */
    public string $predictionStatus {
        get => $this->data['status'] ?? '';
    }

    /**
     * Get the model identifier
     */
    public ?string $model {
        get => $this->data['model'] ?? null;
    }

    /**
     * Get the model version ID
     */
    public ?string $version {
        get => $this->data['version'] ?? null;
    }

    /**
     * Get the input parameters
     *
     * @var array<string, mixed>
     */
    public array $input {
        get => $this->data['input'] ?? [];
    }

    /**
     * Get the output data (null if not completed)
     */
    public mixed $output {
        get => $this->data['output'] ?? null;
    }

    /**
     * Get the error message (null if no error)
     */
    public ?string $error {
        get => $this->data['error'] ?? null;
    }

    /**
     * Get the logs from model execution
     */
    public ?string $logs {
        get => $this->data['logs'] ?? null;
    }

    /**
     * Get the prediction URLs
     *
     * @var array<string, string>
     */
    public array $urls {
        get => $this->data['urls'] ?? [];
    }

    /**
     * Get the creation timestamp
     */
    public ?string $createdAt {
        get => $this->data['created_at'] ?? null;
    }

    /**
     * Get the start timestamp
     */
    public ?string $startedAt {
        get => $this->data['started_at'] ?? null;
    }

    /**
     * Get the completion timestamp
     */
    public ?string $completedAt {
        get => $this->data['completed_at'] ?? null;
    }

    /**
     * Get the metrics data
     *
     * @var array<string, mixed>
     */
    public array $metricsData {
        get => $this->data['metrics'] ?? [];
    }

    /**
     * Get the predict time in seconds
     */
    public ?float $predictTime {
        get => $this->metricsData['predict_time'] ?? null;
    }

    /**
     * Get the total time in seconds
     */
    public ?float $totalTime {
        get => $this->metricsData['total_time'] ?? null;
    }

    /**
     * Get the prediction status as an enum
     */
    public function predictionStatusEnum(): ?PredictionStatus
    {
        return PredictionStatus::tryFromString($this->data['status'] ?? null);
    }

    /**
     * Check if the prediction has completed successfully
     */
    public function isSucceeded(): bool
    {
        return $this->predictionStatusEnum()?->isSuccessful() ?? false;
    }

    /**
     * Check if the prediction has failed
     */
    public function isFailed(): bool
    {
        return $this->predictionStatusEnum()?->isFailed() ?? false;
    }

    /**
     * Check if the prediction is still running
     */
    public function isRunning(): bool
    {
        return $this->predictionStatusEnum()?->isRunning() ?? false;
    }

    /**
     * Check if the prediction was canceled
     */
    public function isCanceled(): bool
    {
        return $this->predictionStatusEnum()?->isCanceled() ?? false;
    }

    /**
     * Check if the prediction is in a terminal state
     */
    public function isTerminal(): bool
    {
        return $this->predictionStatusEnum()?->isTerminal() ?? false;
    }
}
