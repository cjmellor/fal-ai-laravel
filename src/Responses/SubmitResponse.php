<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Responses;

class SubmitResponse extends AbstractResponse
{
    /**
     * Get the request ID
     */
    public string $requestId {
        get => $this->data['request_id'] ?? '';
    }

    /**
     * Get the response URL
     */
    public string $responseUrl {
        get => $this->data['response_url'] ?? '';
    }

    /**
     * Get the status URL
     */
    public string $statusUrl {
        get => $this->data['status_url'] ?? '';
    }

    /**
     * Get the cancel URL
     */
    public string $cancelUrl {
        get => $this->data['cancel_url'] ?? '';
    }
}
