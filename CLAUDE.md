# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

A Laravel package for integrating with AI APIs using a multi-provider architecture. Supports **Fal.ai** and **Replicate** with a unified driver-based interface. Provides a fluent API for model interactions with webhook support, streaming, queue/sync modes, and Platform APIs (Fal.ai only) for pricing, usage analytics, and cost estimation.

## Commands

```bash
# Run all tests
composer test

# Run tests with coverage report
composer test:coverage

# Run a single test file
./vendor/bin/pest tests/Unit/Drivers/FalDriverTest.php

# Run a single test by name
./vendor/bin/pest --filter="test name here"

# Run architecture tests
./vendor/bin/pest tests/Arch.php

# Lint code (Laravel Pint)
composer lint

# Check lint without fixing
composer lint:test

# Run Rector refactoring
composer refactor

# Dry run Rector
composer refactor:dry

# Type checking with PHPStan
composer analyse

# Prepare testbench environment
composer prepare

# Build workbench app for manual testing
composer build

# Serve workbench app locally
composer serve
```

## Architecture

### Multi-Driver Pattern

The package uses Laravel's Manager pattern for multi-provider support:

```
FalAi Facade → AIManager → Driver (Fal/Replicate)
```

- **`AIManager`** (`src/Manager/AIManager.php`) - Extends Laravel's `Manager` class, provides `driver()` method for selecting providers
- **`DriverInterface`** (`src/Contracts/DriverInterface.php`) - Core contract all drivers implement
- **`SupportsPlatform`** (`src/Contracts/SupportsPlatform.php`) - Optional interface for Platform API support (Fal only)

### Two API Systems (Fal.ai only)

1. **Model APIs** - Execute AI models (image generation, etc.) via `FalAi::model()` or `FalAi::driver('fal')->model()`
2. **Platform APIs** - Query pricing, usage, analytics via `FalAi::platform()` (Fal.ai driver only)

### Source Structure

```
src/
├── Manager/AIManager.php           # Laravel Manager - entry point via facade
├── Platform.php                    # Platform API gateway (Fal.ai only)
├── Connectors/
│   ├── FalConnector.php            # Saloon connector with Key auth
│   └── PlatformConnector.php       # Platform API connector
├── Drivers/
│   ├── Fal/FalDriver.php           # Fal driver (DriverInterface + SupportsPlatform)
│   ├── Replicate/
│   │   ├── ReplicateDriver.php     # Replicate driver (DriverInterface)
│   │   ├── ReplicateConnector.php  # Saloon connector with Bearer auth
│   │   ├── Enums/                  # PredictionStatus, Hardware
│   │   ├── Requests/               # CreatePrediction, GetPrediction, CancelPrediction
│   │   │   └── Deployments/        # CRUD requests for deployments
│   │   ├── Responses/              # PredictionResponse, DeploymentResponse, DeploymentsCollection
│   │   ├── Support/                # DeploymentsManager, DeploymentBuilder, DeploymentPredictionRequest
│   │   └── Webhooks/               # VerifyReplicateWebhook, ReplicateWebhookVerifier
│   └── Concerns/ResolvesModelId.php
├── Contracts/                      # DriverInterface, SupportsPlatform, DriverResponseInterface
├── Enums/RequestMode.php           # Queue, Sync, Stream modes
├── Requests/                       # Fal model API requests (SubmitRequest, etc.)
│   └── Platform/                   # Fal Platform API requests
├── Responses/                      # Fal response wrappers extending AbstractResponse
├── Support/                        # FluentRequest + Platform API builders
├── Middleware/VerifyFalWebhook.php
├── Services/WebhookVerifier.php
└── Exceptions/
```

### Core Components

- **`AIManager`** - Laravel Manager implementation. Entry point via facade.
  - `driver('fal')` / `driver('replicate')` - Select provider
  - Default driver from `config('fal-ai.default')`

- **`FalDriver`** - Fal.ai implementation. Methods: `model()`, `run()`, `status()`, `result()`, `stream()`, `cancel()`, `platform()`.

- **`ReplicateDriver`** - Replicate implementation. Methods: `model()`, `run()`, `status()`, `result()`, `cancel()`, `deployments()`, `deployment()`. No `platform()` (throws `PlatformNotSupportedException`).

- **`FluentRequest`** (`src/Support/FluentRequest.php`) - Driver-agnostic chainable request builder. Dynamic `__call` converts camelCase to snake_case for API params. Uses `RequestMode` enum for execution modes.

- **`Platform`** (`src/Platform.php`) - Platform API gateway. Returns fluent builders: `->pricing()`, `->estimateCost()`, `->usage()`, `->analytics()`, `->deleteRequestPayloads()`.

### Replicate Deployments API (Replicate only)

Access via `FalAi::driver('replicate')->deployments()` or `->deployment('owner/name')`:

- **`DeploymentsManager`** - CRUD operations: `list()`, `get()`, `create()`, `update()`, `delete()`
- **`DeploymentBuilder`** - Fluent builder for create/update: `->model()->version()->hardware()->instances()->save()`
- **`DeploymentPredictionRequest`** - Run predictions via deployment: `->with(['prompt' => '...'])->run()`
- **`Hardware`** enum - Available SKUs: `cpu`, `gpu-t4`, `gpu-a100-large`, etc.

### Webhook System

- **Fal.ai**: `VerifyFalWebhook` middleware with ED25519 signature verification using JWKS
- **Replicate**: `VerifyReplicateWebhook` middleware with HMAC-SHA256 signature verification
- Pre-configured routes at `/webhooks/fal` and `/webhooks/replicate` (loaded via `routes/webhooks.php`)

### API Endpoints

**Fal.ai:**
- Queue mode (default): `https://queue.fal.run`
- Sync mode: `https://fal.run`
- Streaming: `https://fal.run` with `/stream` suffix
- Platform APIs: `https://api.fal.ai`

**Replicate:**
- All endpoints: `https://api.replicate.com`
- Predictions: `/v1/predictions`
- Deployments: `/v1/deployments`

## Configuration

Configuration file: `config/fal-ai.php`

Key options:
- `default` - Default driver (`fal` or `replicate`)
- `drivers.fal.api_key` - Fal.ai API key (from `FAL_API_KEY` env var)
- `drivers.replicate.api_key` - Replicate API key (from `REPLICATE_API_KEY` env var)

## Namespace

All code uses `Cjmellor\FalAi` namespace. Auto-registered:
- Service provider: `Cjmellor\FalAi\FalAiServiceProvider`
- Facade: `Cjmellor\FalAi\Facades\FalAi`

## Testing

Uses Pest with Orchestra Testbench. Tests extend `Cjmellor\FalAi\Tests\TestCase`.

Test structure:
- `tests/Unit/Drivers/` - Driver-specific unit tests
- `tests/Unit/` - Unit tests for individual classes
- `tests/Feature/` - Integration tests including MultiDriverTest
- `tests/Integration/` - Full end-to-end integration tests
- `tests/Arch.php` - Architecture tests using pest-plugin-arch
- `tests/Fixtures/Saloon/` - Mock response fixtures (Fal/, Replicate/)

## Saloon HTTP Client

The package uses Saloon 3.x for all HTTP communication. Key patterns:
- Connectors extend `Connector` with `resolveBaseUrl()` and `defaultAuth()`
- FalConnector uses `TokenAuthenticator` with `Key` prefix
- ReplicateConnector uses `TokenAuthenticator` with `Bearer` prefix
- Requests extend `Request` with `resolveEndpoint()` and `defaultMethod()`
- Responses accessed via `$response->json()`, `$response->status()`, etc.

Documentation: https://docs.saloon.dev

## Adding a New Driver

1. Create driver directory: `src/Drivers/{ProviderName}/`
2. Create connector extending Saloon's `Connector`
3. Create driver class implementing `DriverInterface`
4. Add driver config to `config/fal-ai.php` under `drivers`
5. Register driver factory in `AIManager::create{ProviderName}Driver()`
6. Add architecture tests to `tests/Arch.php`
7. Create test fixtures in `tests/Fixtures/Saloon/{ProviderName}/`

## Coding Conventions

- Use Laravel's `throw_if`/`throw_unless` helpers instead of manual if-throw blocks:

```php
// Prefer:
throw_if($condition, SomeException::class, 'message');
throw_unless($condition, SomeException::class, 'message');

// Instead of:
if ($condition) {
    throw new SomeException('message');
}
```

- Use Pest Higher Order Expectations for cleaner test assertions:

```php
// Prefer:
expect($response)->id->toBe('abc123');
expect($collection)->count()->toBe(2);
expect($response)->successful()->toBeTrue();

// Instead of:
expect($response->id)->toBe('abc123');
expect($collection->count())->toBe(2);
expect($response->successful())->toBeTrue();
```

- Use Pest's `sequence()` for asserting on array/collection items instead of index access:

```php
// Prefer:
expect($collection->results())->sequence(
    fn ($item) => $item->name->toBe('deployment-one'),
    fn ($item) => $item->name->toBe('deployment-two'),
);

// Instead of:
expect($collection->results()[0])->name->toBe('deployment-one');
expect($collection->results()[1])->name->toBe('deployment-two');
```
