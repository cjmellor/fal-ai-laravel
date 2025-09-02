<img width="1664" height="384" alt="Fal AI Laravel SDK Banner" src="https://github.com/user-attachments/assets/17a91407-7135-4a21-b9ed-43529ce7fa77" />

# Fal.ai Laravel Package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/cjmellor/fal-ai-laravel?color=rgb%2856%20189%20248%29&label=release&style=for-the-badge)](https://packagist.org/packages/cjmellor/fal-ai-laravel)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/cjmellor/fal-ai-laravel/run-:which-test.yml?branch=main&label=tests&style=for-the-badge&color=rgb%28134%20239%20128%29)](https://github.com/cjmellor/fal-ai-laravel/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/cjmellor/fal-ai-laravel.svg?color=rgb%28249%20115%2022%29&style=for-the-badge)](https://packagist.org/packages/cjmellor/fal-ai-laravel)
![Packagist PHP Version](https://img.shields.io/packagist/dependency-v/cjmellor/fal-ai-laravel/php?color=rgb%28165%20180%20252%29&logo=php&logoColor=rgb%28165%20180%20252%29&style=for-the-badge)
![Laravel Version](https://img.shields.io/badge/laravel-^12-rgb(235%2068%2050)?style=for-the-badge&logo=laravel)

A Laravel package for integrating with the Fal.ai API, providing a fluent interface for AI model interactions with built-in webhook support.

## ✨ Features

- 🚀 **Fluent API** - Chainable methods for easy request building
- 🔗 **Webhook Support** - Secure webhook handling with ED25519 signature verification
- ⚡ **Queue & Sync Modes** - Support for both immediate and queued requests
- 📡 **Real-time Streaming** - Server-Sent Events (SSE) support for progressive AI model responses
- 🛡️ **Security** - Built-in webhook verification middleware
- 🧪 **Well Tested** - Comprehensive test suite
- 📝 **Laravel Integration** - Native Laravel middleware and service provider
- 🛣️ **Built-in Routes** - Pre-configured webhook endpoints ready to use

## 📦 Installation

Install the package via Composer:

```bash
composer require fal-ai/laravel
```

Publish the configuration file:

```bash
php artisan vendor:publish --provider="FalAi\FalAiServiceProvider"
```

Add your Fal.ai API key to your `.env` file:

```env
FAL_API_KEY=your_fal_api_key_here
```

## 🚀 Basic Usage

### 🎯 Simple Request

```php
use FalAi\FalAi;

$falAi = new FalAi();

$response = $falAi->model('fal-ai/flux/schnell')
    ->prompt('A beautiful sunset over mountains')
    ->imageSize('landscape_4_3')
    ->run();
```

### ⚡ Queue vs Sync Modes

> [!TIP]
> **Queue mode** is the default and recommended for most use cases. It's perfect for complex generations that take time to process.

#### 📋 Queue Mode (Default)

```php
$response = $falAi->model('fal-ai/flux/dev')
    ->prompt('A futuristic cityscape')
    ->queue() // Explicit queue mode (optional, it's the default)
    ->run();

// Returns: ['request_id' => 'req_123...', 'status' => 'IN_QUEUE']
```

**Use queue mode when:**
- Generating high-quality images with many inference steps
- Processing multiple images in batch
- You don't need immediate results
- Working with complex prompts or large image sizes

#### ⚡ Sync Mode

```php
$response = $falAi->model('fal-ai/flux/schnell')
    ->prompt('A beautiful landscape')
    ->sync() // Switch to sync mode
    ->run();

// Returns the complete result immediately
```

**Use sync mode when:**
- You need immediate results
- Generating simple images with few inference steps
- Building interactive applications
- Testing and development

> [!WARNING]
> Sync mode may timeout for complex requests. Use queue mode for production applications.

## 🔗 Webhook Support

### 📤 Making Requests with Webhooks

When you add a webhook URL to your request, it automatically switches to queue mode:

```php
$response = $falAi->model('fal-ai/flux/schnell')
    ->withWebhook('https://myapp.com/webhooks/fal')
    ->prompt('A beautiful sunset over mountains')
    ->imageSize('landscape_4_3')
    ->run();

// Returns: ['request_id' => 'req_123...', 'status' => 'IN_QUEUE']
```

### 📋 Webhook URL Requirements

- Must be a valid HTTPS URL
- Must be publicly accessible
- Should respond with 2xx status codes

### 🛠️ Setting Up Webhook Endpoints

You have two options for handling webhooks: use the built-in route or create your own custom endpoint.

#### 🎯 Option 1: Built-in Webhook Route (Easiest)

The package includes a pre-configured webhook route at `/webhooks/fal` that handles basic webhook processing:

```php
// This route is automatically registered by the package
// POST /webhooks/fal

// Use it in your requests:
$response = $falAi->model('fal-ai/flux/schnell')
    ->withWebhook(url('/webhooks/fal')) // Uses the built-in route
    ->prompt('Your prompt here')
    ->run();
```

> [!TIP]
> The built-in route automatically verifies webhooks and returns appropriate responses. Perfect for getting started quickly!

#### 🏭 Option 2: Custom Webhook Endpoint (Recommended for Production)

```php
use FalAi\Middleware\VerifyFalWebhook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/fal', function (Request $request) {
    $payload = $request->json()->all();
    
    if ($payload['status'] === 'OK') {
        $images = $payload['data']['images'];
        // Process successful results
        foreach ($images as $image) {
            // Save image URL: $image['url']
        }
    } elseif ($payload['status'] === 'ERROR') {
        $error = $payload['error'];
        // Handle error
    }
    
    return response()->json(['status' => 'processed']);
})->middleware(VerifyFalWebhook::class);
```

For production applications, create a custom webhook endpoint with your own processing logic:

```php
use FalAi\Middleware\VerifyFalWebhook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/fal-custom', function (Request $request) {
    $payload = $request->json()->all();
    
    if ($payload['status'] === 'OK') {
        $images = $payload['data']['images'];
        // Process successful results
        foreach ($images as $image) {
            // Save image URL: $image['url']
            // Custom processing logic here
        }
    } elseif ($payload['status'] === 'ERROR') {
        $error = $payload['error'];
        // Handle error with custom logic
    }
    
    return response()->json(['status' => 'processed']);
})->middleware(VerifyFalWebhook::class);
```

#### 🔧 Option 3: Manual Verification (Advanced)

For complete control over the verification process:

```php
use FalAi\Services\WebhookVerifier;
use FalAi\Exceptions\WebhookVerificationException;

Route::post('/webhooks/fal-manual', function (Request $request) {
    $verifier = new WebhookVerifier();
    
    try {
        $verifier->verify($request);
        
        // Webhook is valid, process payload
        $payload = $request->json()->all();
        
        return response()->json(['status' => 'verified']);
        
    } catch (WebhookVerificationException $e) {
        return response()->json([
            'error' => 'Unauthorized',
            'message' => 'Webhook verification failed'
        ], 401);
    }
});
```

### 📄 Webhook Payload Examples

#### ✅ Successful Completion

```json
{
    "request_id": "req_123456789",
    "status": "OK",
    "data": {
        "images": [
            {
                "url": "https://fal.media/files/generated-image.jpg",
                "width": 1024,
                "height": 768,
                "content_type": "image/jpeg"
            }
        ],
        "seed": 12345,
        "has_nsfw_concepts": [false],
        "prompt": "A beautiful sunset over mountains"
    }
}
```

#### ❌ Error

```json
{
    "request_id": "req_123456789",
    "status": "ERROR",
    "error": {
        "type": "ValidationError",
        "message": "Invalid prompt provided"
    }
}
```

## ⚙️ Configuration

> [!NOTE]
> You can customise the package behaviour by publishing and modifying the configuration file.

The configuration file `config/fal-ai.php` contains the following options:

```php
return [
    'api_key' => env('FAL_API_KEY'),
    'base_url' => 'https://queue.fal.run',
    'default_model' => '',
    
    'webhook' => [
        // JWKS cache TTL in seconds (max 24 hours)
        'jwks_cache_ttl' => env('FAL_WEBHOOK_JWKS_CACHE_TTL', 86400),
        
        // Timestamp tolerance in seconds (prevents replay attacks)
        'timestamp_tolerance' => env('FAL_WEBHOOK_TIMESTAMP_TOLERANCE', 300),
        
        // HTTP timeout for JWKS fetching
        'verification_timeout' => env('FAL_WEBHOOK_VERIFICATION_TIMEOUT', 10),
    ],
];
```

### Environment Variables

```env
# Required
FAL_API_KEY=your_fal_api_key_here

# Optional webhook configuration
FAL_WEBHOOK_JWKS_CACHE_TTL=86400
FAL_WEBHOOK_TIMESTAMP_TOLERANCE=300
FAL_WEBHOOK_VERIFICATION_TIMEOUT=10
```

## 🤖 Available Models

The package supports all Fal.ai models. Some popular ones include:

**FLUX Models (Recommended):**
- `fal-ai/flux/schnell` - Fast image generation (1-4 steps)
- `fal-ai/flux/dev` - High-quality image generation (12B parameters)
- `fal-ai/flux/pro` - Professional grade (API only)
- `fal-ai/flux-lora` - FLUX with LoRA support

**Other Popular Models:**
- `fal-ai/stable-diffusion-v35-large` - Latest Stable Diffusion 3.5
- `fal-ai/recraft/v3` - Vector art and typography
- `fal-ai/aura-flow` - High-quality text-to-image
- `fal-ai/ideogram/v2` - Excellent typography handling

## 🔗 Fluent API Methods

### 🛠️ Common Methods

```php
$request = $falAi->model('fal-ai/flux/schnell')
    ->prompt('Your prompt here')           // Set the text prompt
    ->imageSize('landscape_4_3')           // Set image dimensions
    ->numImages(2)                         // Number of images to generate
    ->seed(12345)                          // Set random seed
    ->withWebhook('https://...')           // Add webhook URL
    ->queue()                              // Use queue mode
    ->sync();                              // Use sync mode
```

## ⚠️ Error Handling

> [!IMPORTANT]
> Always implement proper error handling in production applications to gracefully handle API failures and webhook verification issues.

```php
use FalAi\Exceptions\WebhookVerificationException;
use InvalidArgumentException;

try {
    $response = $falAi->model('fal-ai/flux/schnell')
        ->withWebhook('https://myapp.com/webhook')
        ->prompt('Test prompt')
        ->run();
        
    if (!$response->successful()) {
        throw new Exception('API request failed: ' . $response->body());
    }
    
} catch (InvalidArgumentException $e) {
    // Invalid webhook URL or other validation errors
    echo "Validation error: " . $e->getMessage();
} catch (WebhookVerificationException $e) {
    // Webhook verification failed (in webhook endpoints)
    echo "Webhook error: " . $e->getMessage();
} catch (Exception $e) {
    // Other errors (network, API, etc.)
    echo "Error: " . $e->getMessage();
}
```

## 📡 Streaming

The Fal.ai Laravel package supports real-time streaming responses using Server-Sent Events (SSE). This is particularly useful for AI models that generate content progressively, such as text generation or image creation with intermediate steps.

### 🎯 Basic Streaming Usage

To use streaming, call the `stream()` method instead of `run()` or `queue()`:

```php
use Cjmellor\FalAi\Facades\FalAi;

$streamResponse = FalAi::model('fal-ai/flux/schnell')
    ->prompt('A beautiful sunset over mountains')
    ->imageSize('landscape_4_3')
    ->stream();

    $streamResponse->getResponse();
}
```

### 📊 Streaming vs Regular Requests

| Feature | Regular Request | Streaming Request |
|---------|----------------|-------------------|
| Response Time | Wait for completion | Real-time updates |
| User Experience | Loading spinner | Progress indicators |
| Resource Usage | Lower | Slightly higher |
| Complexity | Simple | Moderate |
| Best For | Simple workflows | Interactive applications |

## 🧪 Testing

Run the test suite:

```bash
composer test
```

### 📝 Important Notes

- Streaming requests always use the `https://fal.run` endpoint regardless of configuration
- Not all Fal.ai models support streaming - check the model documentation
- Streaming responses cannot be cached like regular responses
- Consider implementing proper error handling for network interruptions
- Use streaming for models that benefit from progressive updates (text generation, multi-step image creation)

## 🔒 Security

> [!CAUTION]
> Webhook security is critical for protecting your application from malicious requests. Always use the provided verification mechanisms.

### 🔐 Webhook Security

This package implements Fal.ai's webhook verification using:

- **ED25519 signature verification** using Fal.ai's public keys
- **Timestamp validation** to prevent replay attacks
- **JWKS caching** for performance
- **Automatic header extraction** and validation

### 💡 Best Practices

> [!TIP]
> Follow these security practices to ensure your webhook endpoints are secure:

1. **Always use HTTPS** for webhook URLs
2. **Use the provided middleware** for automatic verification
3. **Validate webhook payloads** in your application logic
4. **Implement proper error handling** and logging
5. **Monitor webhook endpoints** for suspicious activity
6. **Use rate limiting** on webhook routes
7. **Keep your API keys secure** and rotate them regularly

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📄 License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## 💬 Support

For support, please open an issue on GitHub or contact the maintainers.
