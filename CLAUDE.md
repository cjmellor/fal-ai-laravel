# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

A Laravel package for integrating with the Fal.ai API. Provides a fluent interface for AI model interactions with webhook support, streaming, queue/sync modes, and Platform APIs for pricing, usage analytics, and cost estimation.

## Commands

```bash
# Run all tests
composer test

# Run a single test file
./vendor/bin/pest tests/Unit/FalAiTest.php

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

### Two API Systems

The package provides two distinct API systems:

1. **Model APIs** - Execute AI models (image generation, etc.) via `FalAi::model()`
2. **Platform APIs** - Query pricing, usage, analytics via `FalAi::platform()`

Each has its own connector, endpoint, and fluent builder pattern.

### Core Components

- **`FalAi`** (`src/FalAi.php`) - Main entry point. Methods: `model()`, `platform()`, `run()`, `status()`, `result()`, `stream()`, `cancel()`.

- **`Platform`** (`src/Platform.php`) - Platform API gateway. Returns fluent builders: `->pricing()`, `->estimateCost()`, `->usage()`, `->analytics()`.

- **`FluentRequest`** (`src/Support/FluentRequest.php`) - Chainable request builder for model requests. Dynamic `__call` converts camelCase to snake_case for API params (e.g., `->imageSize('landscape')` → `image_size: 'landscape'`). Supports immutable patterns via `Immutable` suffix.

- **`FalConnector`** (`src/Connectors/FalConnector.php`) - Saloon HTTP connector for model APIs. Uses `Key` prefix authentication.

- **`PlatformConnector`** (`src/Connectors/PlatformConnector.php`) - Saloon HTTP connector for Platform APIs.

### Platform API Fluent Builders

Located in `src/Support/`:
- `PricingRequest` - Build pricing queries with `->forEndpoint()`, `->forEndpoints()`
- `EstimateCostRequest` - Build cost estimates with `->historicalApiPrice()`, `->unitPrice()`, `->endpoint()`
- `UsageRequest` - Build usage queries with `->between()`, `->timeframe()`, `->withSummary()`
- `AnalyticsRequest` - Build analytics queries with `->withRequestCount()`, `->withAllMetrics()`

### Request Classes

All extend Saloon's `Request` class. Located in `src/Requests/`:

**Model API requests:**
- `SubmitRequest`, `FetchRequestStatusRequest`, `GetResultRequest`, `StreamRequest`, `CancelRequest`

**Platform API requests** (`src/Requests/Platform/`):
- `GetPricingRequest`, `EstimateCostRequest`, `GetUsageRequest`, `GetAnalyticsRequest`

### Response Classes

**Saloon Response wrappers** (`src/Responses/`) - Extend Saloon's `Response` with typed accessors:
- `SubmitResponse`, `StatusResponse`, `ResultResponse`, `StreamResponse`
- `PricingResponse`, `UsageResponse`, `AnalyticsResponse`, `EstimateCostResponse`

**Data Transfer Objects** (`src/Data/`) - Plain PHP classes for structured data:
- `SubmitResponse`, `StatusResponse` - Typed DTOs with `fromArray()` factory methods

### Webhook System

- **`VerifyFalWebhook`** middleware (`src/Middleware/VerifyFalWebhook.php`) - ED25519 signature verification using JWKS
- **`WebhookVerifier`** service (`src/Services/WebhookVerifier.php`) - Verifies signatures and timestamps
- Pre-configured route at `/webhooks/fal` (loaded via `routes/webhooks.php`)

### API Endpoints

- Queue mode (default): `https://queue.fal.run`
- Sync mode: `https://fal.run`
- Streaming: `https://fal.run` with `/stream` suffix
- Platform APIs: `https://api.fal.ai`

## Configuration

Configuration file: `config/fal-ai.php`

Key options:
- `api_key` - Fal.ai API key (from `FAL_API_KEY` env var)
- `base_url` - Queue API endpoint (default: `https://queue.fal.run`)
- `platform_base_url` - Platform API endpoint (default: `https://api.fal.ai`)
- `default_model` - Default model ID (optional)
- `webhook` - JWKS cache TTL, timestamp tolerance, verification timeout

## Namespace

All code uses `Cjmellor\FalAi` namespace. Auto-registered:
- Service provider: `Cjmellor\FalAi\FalAiServiceProvider`
- Facade: `Cjmellor\FalAi\Facades\FalAi`

## Testing

Uses Pest with Orchestra Testbench. Tests extend `Cjmellor\FalAi\Tests\TestCase`.

Test structure:
- `tests/Unit/` - Unit tests for individual classes
- `tests/Feature/` - Integration tests for workflows
- `tests/Integration/` - Full end-to-end integration tests
- `tests/Arch.php` - Architecture tests using pest-plugin-arch

## Saloon HTTP Client

The package uses Saloon 3.x for all HTTP communication. Key patterns:
- Connectors extend `Connector` with `resolveBaseUrl()` and `defaultAuth()`
- Requests extend `Request` with `resolveEndpoint()` and `defaultMethod()`
- Responses accessed via `$response->json()`, `$response->status()`, etc.
- Exceptions: `FatalRequestException`, `RequestException` from Saloon

Documentation: https://docs.saloon.dev