# Upgrade Guide

## Upgrading from v1.x to v2.x

Version 2.0 introduces a multi-provider architecture with support for both Fal.ai and Replicate. This is a **major breaking change** that requires updates to your configuration and code.

### Impact Legend

- 🔴 **High Impact** - Breaking changes requiring code updates
- 🟠 **Medium Impact** - Behavioral changes that may affect your app
- 🟢 **Low Impact** - New features, safe additions

---

### 🔴 High Impact Changes

#### Configuration Structure Changed

All configuration is now nested under `drivers.*` to support multiple providers.

**Before (v1.x):**

```php
// config/fal-ai.php
return [
    'api_key' => env('FAL_API_KEY'),
    'base_url' => env('FAL_BASE_URL', 'https://queue.fal.run'),
    'platform_base_url' => env('FAL_PLATFORM_URL', 'https://api.fal.ai'),
    'default_model' => env('FAL_DEFAULT_MODEL'),
];
```

**After (v2.x):**

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
            'webhook' => [/* ... */],
        ],
        'replicate' => [
            'api_key' => env('REPLICATE_API_KEY'),
            'base_url' => env('REPLICATE_BASE_URL', 'https://api.replicate.com'),
            'default_model' => env('REPLICATE_DEFAULT_MODEL'),
            'webhook' => [/* ... */],
        ],
    ],
];
```

**Action required:**

```bash
php artisan vendor:publish --tag=fal-ai-config --force
```

#### `FalAi` Class Removed

The `Cjmellor\FalAi\FalAi` class no longer exists. Use the facade or resolve `AIManager`.

**Before (v1.x):**

```php
use Cjmellor\FalAi\FalAi;

$fal = new FalAi();
$fal->run('model', $data);
```

**After (v2.x):**

```php
use Cjmellor\FalAi\Facades\FalAi;

FalAi::model('model')->with($data)->run();

// Or resolve from container
$manager = app('fal-ai');
$manager->driver('fal')->model('model')->run();
```

#### Direct Method Calls Removed

Direct `run()`, `status()`, `result()` calls on the facade are removed. Use the fluent interface.

**Before (v1.x):**

```php
$response = FalAi::run('fal-ai/flux/schnell', ['prompt' => 'A sunset']);
$status = FalAi::status($requestId, 'fal-ai/flux/schnell');
$result = FalAi::result($requestId, 'fal-ai/flux/schnell');
```

**After (v2.x):**

```php
// Run a model
$response = FalAi::model('fal-ai/flux/schnell')
    ->prompt('A sunset')
    ->run();

// Check status
$status = FalAi::driver('fal')->status($requestId, 'fal-ai/flux/schnell');

// Get result
$result = FalAi::driver('fal')->result($requestId, 'fal-ai/flux/schnell');
```

---

### 🟠 Medium Impact Changes

#### Platform API Access

Platform APIs remain available but now require the Fal driver explicitly if not the default.

**Before (v1.x):**

```php
$pricing = FalAi::platform()->pricing()->forEndpoint('fal-ai/flux/dev')->get();
```

**After (v2.x):**

```php
// Works if 'fal' is the default driver
$pricing = FalAi::platform()->pricing()->forEndpoint('fal-ai/flux/dev')->get();

// Or explicitly select the driver
$pricing = FalAi::driver('fal')->platform()->pricing()->forEndpoint('fal-ai/flux/dev')->get();
```

> **Note:** Calling `platform()` on the Replicate driver throws `PlatformNotSupportedException`.

#### Namespace Changes

Some internal classes have moved:

| Old Location | New Location |
|--------------|--------------|
| `Cjmellor\FalAi\FalAi` | Removed |
| `Cjmellor\FalAi\Connectors\FalConnector` | `Cjmellor\FalAi\Connectors\FalConnector` (unchanged) |
| `Cjmellor\FalAi\Connectors\PlatformConnector` | `Cjmellor\FalAi\Connectors\PlatformConnector` (unchanged) |

---

### 🟢 Low Impact Changes

#### New Environment Variables

New optional environment variables for driver selection and Replicate:

```env
# Select default driver (optional, defaults to 'fal')
AI_DRIVER=fal

# Replicate configuration (optional)
REPLICATE_API_KEY=your-replicate-api-key
REPLICATE_DEFAULT_MODEL=stability-ai/sdxl
```

#### New Webhook Route

A new webhook route is registered for Replicate:

- `/webhooks/fal` - Fal.ai webhooks (unchanged)
- `/webhooks/replicate` - Replicate webhooks (new)

#### New Types and Interfaces

New enums and interfaces added for type safety:

- `RequestMode` enum (`Queue`, `Sync`, `Stream`)
- `PredictionStatus` enum (for Replicate)
- `DriverInterface` contract
- `DriverResponseInterface` contract

---

### Migration Checklist

1. **Republish configuration file**
   ```bash
   php artisan vendor:publish --tag=fal-ai-config --force
   ```

2. **Update direct method calls** to use fluent interface
   ```php
   // Old
   FalAi::run($model, $data);

   // New
   FalAi::model($model)->with($data)->run();
   ```

3. **Update any direct `FalAi` class usage** to use facade or `AIManager`

4. **Review Platform API calls** if not using Fal as default driver

5. **Clear cached config**
   ```bash
   php artisan config:clear
   ```

---

### Using Multiple Providers

v2.x allows using both Fal.ai and Replicate in the same application:

```php
// Fal.ai
$falResponse = FalAi::driver('fal')
    ->model('fal-ai/flux/schnell')
    ->prompt('A beautiful sunset')
    ->run();

// Replicate
$replicateResponse = FalAi::driver('replicate')
    ->model('stability-ai/sdxl')
    ->prompt('A majestic dragon')
    ->run();
```

---

### Getting Help

If you encounter issues:

1. Verify your config file is properly migrated
2. Check `.env` has the correct API keys
3. Review facade usage includes `driver()` where needed
4. Open an issue on GitHub

---

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
