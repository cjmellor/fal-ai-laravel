<img width="1664" height="384" alt="Fal AI Laravel SDK Banner" src="https://github.com/user-attachments/assets/17a91407-7135-4a21-b9ed-43529ce7fa77" />

# Fal.ai Laravel Package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/cjmellor/fal-ai-laravel?color=rgb%2856%20189%20248%29&label=release&style=for-the-badge)](https://packagist.org/packages/cjmellor/fal-ai-laravel)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/cjmellor/fal-ai-laravel/run-pest.yml?branch=main&label=tests&style=for-the-badge&color=rgb%28134%20239%20128%29)](https://github.com/cjmellor/fal-ai-laravel/actions?query=workflow%3Arun-pest+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/cjmellor/fal-ai-laravel.svg?color=rgb%28249%20115%2022%29&style=for-the-badge)](https://packagist.org/packages/cjmellor/fal-ai-laravel)
![Packagist PHP Version](https://img.shields.io/packagist/dependency-v/cjmellor/fal-ai-laravel/php?color=rgb%28165%20180%20252%29&logo=php&logoColor=rgb%28165%20180%20252%29&style=for-the-badge)
![Laravel Version](https://img.shields.io/badge/laravel-^12-rgb(235%2068%2050)?style=for-the-badge&logo=laravel)

A Laravel package for integrating with the [Fal.ai](https://fal.ai) API, providing a fluent interface for AI model execution with built-in webhook support, streaming, and Platform APIs.

> [!NOTE]
> **Multi-provider support:** This package also includes a [Replicate driver](#replicate-driver) for [Replicate.com](https://replicate.com).

## Features

- Fluent API for building model requests
- Queue and Sync execution modes
- Real-time streaming with Server-Sent Events (SSE)
- Webhook support with ED25519 signature verification
- Platform APIs for pricing, usage, analytics, and cost estimation
- Multi-provider architecture
- Replicate Deployments API for auto-scaling inference

> [!WARNING]
> **Upgrading from v1.x?** Version 2.0 is a complete architectural rewrite with breaking changes. The configuration structure, API methods, and class namespaces have all changed. You **must** follow the [Upgrade Guide](UPGRADE.md) to migrate from v1.x to v2.x.

## Installation

Install via Composer:

```bash
composer require cjmellor/fal-ai-laravel
```

Publish the configuration:

```bash
php artisan vendor:publish --tag=fal-ai-config
```

Add your API key to `.env`:

```env
FAL_API_KEY=your_fal_api_key
```

## Basic Usage

```php
use Cjmellor\FalAi\Facades\FalAi;

$response = FalAi::model('fal-ai/flux/schnell')
    ->prompt('A beautiful sunset over mountains')
    ->imageSize('landscape_4_3')
    ->run();

$requestId = $response->requestId;
```

### Using a Default Model

Set a default model in your config to omit the model ID:

```php
// config/fal-ai.php
'drivers' => [
    'fal' => [
        'default_model' => 'fal-ai/flux/schnell',
    ],
],

// Usage
$response = FalAi::model()
    ->prompt('A cozy cabin in the woods')
    ->run();
```

## Queue vs Sync Modes

### Queue Mode (Default)

Requests are processed asynchronously. Use webhooks or polling to get results.

```php
$response = FalAi::model('fal-ai/flux/dev')
    ->prompt('A detailed portrait')
    ->queue() // Optional - queue is the default
    ->run();

// Returns immediately with request_id
$requestId = $response->requestId;
```

**Best for:** Complex generations, batch processing, production workloads.

### Sync Mode

Requests block until complete and return the result directly.

```php
$response = FalAi::model('fal-ai/flux/schnell')
    ->prompt('A quick sketch')
    ->sync()
    ->run();

// Returns the complete result
$images = $response->json()['images'];
```

**Best for:** Simple generations, interactive applications, development.

> [!WARNING]
> Sync mode may timeout for complex requests.

## Polling Status & Results

For queued requests, poll for status and retrieve results:

```php
// Check status
$status = FalAi::driver('fal')->status($requestId, 'fal-ai/flux/dev');

if ($status->json()['status'] === 'COMPLETED') {
    // Get the result
    $result = FalAi::driver('fal')->result($requestId, 'fal-ai/flux/dev');
    $images = $result->json()['images'];
}

// Cancel a queued request
FalAi::driver('fal')->cancel($requestId, 'fal-ai/flux/dev');
```

### Response Helpers

```php
$response = FalAi::model('fal-ai/flux/schnell')
    ->prompt('A fox in a forest')
    ->run();

$response->requestId;    // Request ID
$response->statusUrl;    // URL to check status
$response->responseUrl;  // URL to get result
$response->cancelUrl;    // URL to cancel
```

## Streaming

Stream responses in real-time using Server-Sent Events:

```php
$stream = FalAi::model('fal-ai/flux/schnell')
    ->prompt('A dancing robot')
    ->stream();

// Process the stream response
$stream->getResponse();
```

> [!NOTE]
> Not all models support streaming. Check model documentation.

## Webhook Support

### Setting a Webhook URL

Adding a webhook automatically uses queue mode:

```php
$response = FalAi::model('fal-ai/flux/schnell')
    ->prompt('A beautiful landscape')
    ->withWebhook('https://yourapp.com/webhooks/fal')
    ->run();
```

> [!IMPORTANT]
> Webhook URLs must use HTTPS and be publicly accessible.

### Built-in Webhook Route

The package provides a pre-configured route at `/webhooks/fal`:

```php
$response = FalAi::model('fal-ai/flux/schnell')
    ->withWebhook(url('/webhooks/fal'))
    ->prompt('Your prompt')
    ->run();
```

### Custom Webhook Endpoint

Create your own endpoint with the verification middleware:

```php
use Cjmellor\FalAi\Middleware\VerifyFalWebhook;

Route::post('/webhooks/fal-custom', function (Request $request) {
    $payload = $request->json()->all();

    if ($payload['status'] === 'OK') {
        $images = $payload['data']['images'];
        // Process images
    }

    return response()->json(['status' => 'processed']);
})->middleware(VerifyFalWebhook::class);
```

### Manual Verification

```php
use Cjmellor\FalAi\Services\WebhookVerifier;
use Cjmellor\FalAi\Exceptions\WebhookVerificationException;

$verifier = new WebhookVerifier();

try {
    $verifier->verify($request);
    // Webhook is valid
} catch (WebhookVerificationException $e) {
    // Verification failed
}
```

### Webhook Payload

**Success:**
```json
{
    "request_id": "req_123",
    "status": "OK",
    "data": {
        "images": [{"url": "https://...", "width": 1024, "height": 768}],
        "seed": 12345
    }
}
```

**Error:**
```json
{
    "request_id": "req_123",
    "status": "ERROR",
    "error": {"type": "ValidationError", "message": "Invalid prompt"}
}
```

## Platform APIs

Access Fal.ai Platform APIs for pricing, usage, and analytics.

### Pricing

```php
$pricing = FalAi::platform()
    ->pricing()
    ->forEndpoints(['fal-ai/flux/dev', 'fal-ai/flux/schnell'])
    ->get();

$unitPrice = $pricing->getUnitPriceFor('fal-ai/flux/dev');
```

### Cost Estimation

```php
// Estimate by API calls
$estimate = FalAi::platform()
    ->estimateCost()
    ->historicalApiPrice()
    ->endpoint('fal-ai/flux/dev', callQuantity: 100)
    ->estimate();

echo $estimate->totalCost; // e.g., 2.50

// Estimate by billing units
$estimate = FalAi::platform()
    ->estimateCost()
    ->unitPrice()
    ->endpoint('fal-ai/flux/dev', unitQuantity: 100)
    ->estimate();
```

### Usage

```php
$usage = FalAi::platform()
    ->usage()
    ->forEndpoint('fal-ai/flux/dev')
    ->between('2025-01-01T00:00:00Z', '2025-01-31T23:59:59Z')
    ->timeframe('day')
    ->withSummary()
    ->get();

$totalCost = $usage->getTotalCost();
$totalQuantity = $usage->getTotalQuantity();
```

### Analytics

```php
$analytics = FalAi::platform()
    ->analytics()
    ->forEndpoint('fal-ai/flux/dev')
    ->between('2025-01-01', '2025-01-31')
    ->withAllMetrics()
    ->get();

$totalRequests = $analytics->getTotalRequests();
$successRate = $analytics->getSuccessRate();
```

### Delete Request Payloads

Remove stored input/output data for a request:

```php
$response = FalAi::platform()
    ->deleteRequestPayloads('req_123456789')
    ->delete();

if (!$response->hasErrors()) {
    echo "Deleted successfully";
}
```

## Fluent API

### Dynamic Methods

Method names are converted from camelCase to snake_case:

```php
FalAi::model('fal-ai/flux/schnell')
    ->prompt('A sunset')           // prompt
    ->imageSize('1024x1024')       // image_size
    ->numInferenceSteps(50)        // num_inference_steps
    ->guidanceScale(7.5)           // guidance_scale
    ->negativePrompt('blurry')     // negative_prompt
    ->numImages(2)                 // num_images
    ->seed(12345)                  // seed
    ->run();
```

### Bulk Data

```php
$response = FalAi::model('fal-ai/flux/schnell')
    ->with([
        'prompt' => 'A landscape',
        'image_size' => '1024x1024',
        'num_images' => 2,
    ])
    ->run();
```

### Immutable Methods

Create new instances without modifying the original:

```php
$base = FalAi::model('fal-ai/flux/schnell')
    ->imageSize('1024x1024')
    ->numImages(1);

$request1 = $base->promptImmutable('A dragon');
$request2 = $base->promptImmutable('A unicorn');

// $base is unchanged
```

### Conditional Methods

```php
$response = FalAi::model('fal-ai/flux/schnell')
    ->prompt('A sunset')
    ->when($highQuality, fn($req) => $req->numInferenceSteps(100))
    ->unless($skipSeed, fn($req) => $req->seed(42))
    ->run();
```

## Configuration

```php
// config/fal-ai.php
return [
    'default' => env('AI_DRIVER', 'fal'),

    'drivers' => [
        'fal' => [
            'api_key' => env('FAL_API_KEY'),
            'base_url' => env('FAL_BASE_URL', 'https://queue.fal.run'),
            'sync_url' => env('FAL_SYNC_URL', 'https://fal.run'),
            'platform_base_url' => env('FAL_PLATFORM_URL', 'https://api.fal.ai'),
            'default_model' => env('FAL_DEFAULT_MODEL'),
            'webhook' => [
                'jwks_cache_ttl' => 86400,
                'timestamp_tolerance' => 300,
                'verification_timeout' => 10,
            ],
        ],
    ],
];
```

## Error Handling

```php
use Saloon\Exceptions\Request\RequestException;
use Cjmellor\FalAi\Exceptions\WebhookVerificationException;

try {
    $response = FalAi::model('fal-ai/flux/schnell')
        ->prompt('A sunset')
        ->run();
} catch (RequestException $e) {
    $status = $e->getResponse()->status();
    $body = $e->getResponse()->json();
} catch (WebhookVerificationException $e) {
    // Webhook verification failed
}
```

---

## Replicate Driver

This package includes a driver for [Replicate.com](https://replicate.com).

### Setup

Add your Replicate API key to `.env`:

```env
REPLICATE_API_KEY=your_replicate_api_key
```

### Usage

```php
use Cjmellor\FalAi\Facades\FalAi as Ai;

$response = Ai::driver('replicate')
    ->model('stability-ai/sdxl')
    ->prompt('A majestic dragon')
    ->numOutputs(2)
    ->run();
```

### Model Format

Replicate models can use two formats:

**Official Models:**
```php
// Format: owner/model
->model('stability-ai/sdxl')
```

**Custom Models (with specific version):**
```php
// Format: owner/model:version
->model('your-username/my-custom-model:da77bc59ee60...')
```

> [!NOTE]
> The `:version` suffix is only required for custom models. Official Replicate models use just `owner/model`.

### Checking Status

Replicate uses polling for status:

```php
$response = Ai::driver('replicate')
    ->model('stability-ai/sdxl')
    ->prompt('A landscape')
    ->run();

// Poll for completion
$status = Ai::driver('replicate')->status($response->id);

// Status helpers
$status->isRunning();    // starting or processing
$status->isSucceeded();  // completed successfully
$status->isFailed();     // failed
$status->isCanceled();   // canceled
$status->isTerminal();   // any final state

// Get result when complete
if ($status->isSucceeded()) {
    $output = $status->output;
}
```

### Key Differences from Fal

| Feature | Fal.ai | Replicate |
|---------|--------|-----------|
| Queue/Sync modes | Yes | No (always async) |
| Streaming | Yes | No (use polling) |
| Platform APIs | Yes | No |
| Deployments API | No | Yes |
| Webhooks | Yes | Yes |

### Replicate Webhooks

```php
$response = Ai::driver('replicate')
    ->model('stability-ai/sdxl')
    ->prompt('A sunset')
    ->withWebhook('https://yourapp.com/webhooks/replicate')
    ->run();
```

Built-in route available at `/webhooks/replicate`.

Configure webhook verification in `.env`:

```env
REPLICATE_WEBHOOK_SECRET=your_webhook_secret
```

### Deployments

Manage Replicate deployments for auto-scaling model inference.

#### Create a Deployment

```php
$deployment = Ai::driver('replicate')
    ->deployments()
    ->create('my-image-generator')
    ->model('stability-ai/sdxl')
    ->version('da77bc59ee60423279fd632efb4795ab731d9e3ca9705ef3341091fb989b7eaf')
    ->hardware('gpu-t4')
    ->instances(1, 5)  // min, max
    ->save();

echo $deployment->name;       // 'my-image-generator'
echo $deployment->hardware(); // 'gpu-t4'
```

**Available Hardware:** `cpu`, `gpu-t4`, `gpu-l40s`, `gpu-l40s-2x`, `gpu-a100-large`, `gpu-a100-large-2x`, `gpu-h100`

#### List Deployments

```php
$collection = Ai::driver('replicate')->deployments()->list();

foreach ($collection->results() as $deployment) {
    echo $deployment->name . ': ' . $deployment->hardware();
}

// Pagination
if ($collection->hasMore()) {
    $nextUrl = $collection->next();
}
```

#### Get, Update, Delete

```php
// Get
$deployment = Ai::driver('replicate')->deployments()->get('owner', 'name');

// Update
$updated = Ai::driver('replicate')
    ->deployments()
    ->update('owner', 'name')
    ->hardware('gpu-a100-large')
    ->instances(2, 10)
    ->save();

// Delete
Ai::driver('replicate')->deployments()->delete('owner', 'name');
```

#### Run Predictions via Deployment

```php
$prediction = Ai::driver('replicate')
    ->deployment('owner/my-deployment')
    ->with(['prompt' => 'A sunset over mountains'])
    ->webhook('https://example.com/webhook')
    ->run();

// Use prediction ID with standard status/result methods
$status = Ai::driver('replicate')->status($prediction->id);
```

---

## Testing

```bash
composer test
```

## Security

> [!IMPORTANT]
> **Webhook Verification:**
> - **Fal.ai:** ED25519 signatures with JWKS
> - **Replicate:** HMAC-SHA256 signatures
>
> Always use HTTPS for webhook URLs and keep API keys secure.

## Contributing

Contributions are welcome! Please submit a Pull Request.

## License

MIT License. See [LICENSE](LICENSE) for details.
