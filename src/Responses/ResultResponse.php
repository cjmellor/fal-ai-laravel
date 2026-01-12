<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Responses;

class ResultResponse extends AbstractResponse
{
    /**
     * Get all images from the response
     *
     * @var array<int, array<string, mixed>>
     */
    public array $images {
        get => $this->data['images'] ?? [];
    }

    /**
     * Get the first image array
     *
     * @var array<string, mixed>|null
     */
    public ?array $firstImage {
        get => $this->images[0] ?? null;
    }

    /**
     * Get the first image URL
     */
    public ?string $firstImageUrl {
        get => $this->images[0]['url'] ?? null;
    }

    /**
     * Get the inference time from timings
     */
    public ?float $inferenceTime {
        get => $this->data['timings']['inference'] ?? null;
    }

    /**
     * Get the seed used for generation
     */
    public ?int $seed {
        get => $this->data['seed'] ?? null;
    }

    /**
     * Get the prompt used for generation
     */
    public ?string $prompt {
        get => $this->data['prompt'] ?? null;
    }

    /**
     * Check if NSFW concepts were detected
     *
     * @var array<int, bool>|null
     */
    public ?array $hasNsfwConcepts {
        get => $this->data['has_nsfw_concepts'] ?? null;
    }

    /**
     * Get timing information
     *
     * @var array<string, float>|null
     */
    public ?array $timings {
        get => $this->data['timings'] ?? null;
    }

    /**
     * Get metrics information
     *
     * @var array<string, mixed>|null
     */
    public ?array $metrics {
        get => $this->data['metrics'] ?? null;
    }

    /**
     * Get the width of the first image
     */
    public ?int $width {
        get => $this->images[0]['width'] ?? null;
    }

    /**
     * Get the height of the first image
     */
    public ?int $height {
        get => $this->images[0]['height'] ?? null;
    }

    /**
     * Get the content type of the first image
     */
    public ?string $contentType {
        get => $this->images[0]['content_type'] ?? null;
    }

    /**
     * Get the file name of the first image
     */
    public ?string $fileName {
        get => $this->images[0]['file_name'] ?? null;
    }

    /**
     * Get the file size of the first image
     */
    public ?int $fileSize {
        get => $this->images[0]['file_size'] ?? null;
    }
}
