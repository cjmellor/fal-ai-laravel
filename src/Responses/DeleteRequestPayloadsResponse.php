<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Responses;

class DeleteRequestPayloadsResponse extends AbstractResponse
{
    /**
     * Get all CDN delete results
     *
     * @var array<array{link: string, exception: string|null}>
     */
    public array $cdnDeleteResults {
        get => $this->data['cdn_delete_results'] ?? [];
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
}
