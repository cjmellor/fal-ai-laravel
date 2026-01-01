# Upgrade Guide

## Upgrading to 1.1 from 1.0

### Response Property Access

Response classes now use PHP 8.4 property hooks with camelCase naming instead of magic `__get()` methods with snake_case properties. This provides better type safety and IDE autocompletion.

**SubmitResponse**

```php
// Before (v1.0)
$response->request_id;
$response->response_url;
$response->status_url;
$response->cancel_url;
$response->getRequestId();
$response->getResponseUrl();
$response->getStatusUrl();
$response->getCancelUrl();

// After (v1.1)
$response->requestId;
$response->responseUrl;
$response->statusUrl;
$response->cancelUrl;
```

**StatusResponse**

```php
// Before (v1.0)
$response->queue_position;
$response->response_url;
$response->getQueuePosition();
$response->getResponseUrl();
$response->getLogs();
$response->getMetrics();
$response->getTimings();

// After (v1.1)
$response->queuePosition;
$response->responseUrl;
$response->logs;
$response->metrics;
$response->timings;
```

### Automatic Exception Throwing

HTTP connectors now use Saloon's `AlwaysThrowOnErrors` trait. Failed requests (4xx and 5xx responses) will automatically throw exceptions instead of returning a response object that you need to check manually.

If you were checking for failed responses manually, you should now wrap API calls in try-catch blocks:

```php
// Before (v1.0)
$response = FalAi::model('fal-ai/flux/dev')->prompt('A cat')->run();

if ($response->failed()) {
    // Handle error
}

// After (v1.1)
use Saloon\Exceptions\Request\RequestException;

try {
    $response = FalAi::model('fal-ai/flux/dev')->prompt('A cat')->run();
} catch (RequestException $e) {
    // Handle error
    $status = $e->getResponse()->status();
    $body = $e->getResponse()->json();
}
```
