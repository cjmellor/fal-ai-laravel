<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Responses;

use Saloon\Http\Response;

class DeleteRequestPayloadsResponse
{
    /**
     * Get all CDN delete results
     *
     * @return array<array{link: string, exception: string|null}>
     */
    public array $cdnDeleteResults {
        get => $this->data['cdn_delete_results'] ?? [];
    }

    private array $data;

    public function __construct(
        private readonly Response $response,
        array $data
    ) {
        $this->data = $data;
    }

    /**
     * Check if any deletions failed
     */
    public function hasErrors(): bool
    {
        foreach ($this->cdnDeleteResults as $result) {
            if ($result['exception'] !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get successfully deleted files
     *
     * @return array<array{link: string, exception: null}>
     */
    public function getSuccessfulDeletions(): array
    {
        return array_values(array_filter(
            $this->cdnDeleteResults,
            fn (array $result): bool => $result['exception'] === null
        ));
    }

    /**
     * Get files that failed to delete
     *
     * @return array<array{link: string, exception: string}>
     */
    public function getFailedDeletions(): array
    {
        return array_values(array_filter(
            $this->cdnDeleteResults,
            fn (array $result): bool => $result['exception'] !== null
        ));
    }

    /**
     * Get the raw JSON response
     */
    public function json(): array
    {
        return $this->response->json();
    }

    /**
     * Get the HTTP status code
     */
    public function status(): int
    {
        return $this->response->status();
    }

    /**
     * Check if the request was successful
     */
    public function successful(): bool
    {
        return $this->response->successful();
    }

    /**
     * Check if the request failed
     */
    public function failed(): bool
    {
        return $this->response->failed();
    }

    /**
     * Get the underlying Saloon response
     */
    public function getResponse(): Response
    {
        return $this->response;
    }
}
