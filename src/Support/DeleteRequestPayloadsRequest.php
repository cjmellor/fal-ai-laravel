<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Support;

use Cjmellor\FalAi\Platform;
use Cjmellor\FalAi\Requests\Platform\DeleteRequestPayloadsRequest as SaloonDeleteRequestPayloadsRequest;
use Cjmellor\FalAi\Responses\DeleteRequestPayloadsResponse;
use Illuminate\Support\Traits\Conditionable;

class DeleteRequestPayloadsRequest
{
    use Conditionable;

    public private(set) string $requestId;

    public private(set) ?string $idempotencyKey = null;

    private Platform $platform;

    public function __construct(Platform $platform, string $requestId)
    {
        $this->platform = $platform;
        $this->requestId = $requestId;
    }

    /**
     * Set an idempotency key for safe retries
     *
     * Responses are cached for 10 minutes per unique key
     */
    public function withIdempotencyKey(string $key): self
    {
        $this->idempotencyKey = $key;

        return $this;
    }

    /**
     * Execute the delete request
     */
    public function delete(): DeleteRequestPayloadsResponse
    {
        $request = new SaloonDeleteRequestPayloadsRequest($this->requestId, $this->idempotencyKey);
        $response = $this->platform->connector->send($request);

        return new DeleteRequestPayloadsResponse($response, $response->json());
    }
}
