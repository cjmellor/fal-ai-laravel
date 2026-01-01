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

### Core Components

- **`FalAi`** (`src/FalAi.php`) - Main entry point. Orchestrates model requests and Platform API access. Methods: `model()`, `platform()`, `run()`, `status()`, `result()`, `stream()`, `cancel()`.

- **`Platform`** (`src/Platform.php`) - Platform API gateway. Provides fluent builders for pricing, cost estimation, usage data, and analytics. Separate from model execution APIs.

- **`FluentRequest`** (`src/Support/FluentRequest.php`) - Chainable request builder for model requests. Dynamic `__call` converts camelCase to snake_case for API params (e.g., `->imageSize('landscape')` → `image_size: 'landscape'`). Supports both mutable and immutable patterns via `Immutable` suffix. Chainable methods include `->queue()`, `->sync()`, `->withWebhook()`, `->with()`, `->run()`, `->stream()`.

- **`FalConnector`** (`src/Connectors/FalConnector.php`) - Saloon HTTP connector for model APIs. Handles `Key` prefix authentication.

- **`PlatformConnector`** (`src/Connectors/PlatformConnector.php`) - Saloon HTTP connector for Platform APIs (pricing, usage, analytics). Separate authentication from model connector.

### Request/Response Flow (Model APIs)

1. `FalAi::model($id)` returns a `FluentRequest`
2. Chain methods (e.g., `->prompt()`, `->imageSize()`) to build request payload
3. Call `->run()` (default queue mode), `->sync()`, or `->stream()`
4. Returns typed response: `SubmitResponse`, `StatusResponse`, `ResultResponse`, or `StreamResponse`
5. Response objects provide convenient accessors and helper methods

### Request/Response Flow (Platform APIs)

1. `FalAi::platform()` returns a `Platform` instance
2. Call builder method: `->pricing()`, `->estimateCost()`, `->usage()`, or `->analytics()`
3. Chain builder methods (e.g., `->forEndpoint()`, `->between()`)
4. Call `->get()` to execute request
5. Returns typed response: `PricingResponse`, `EstimateCostResponse`, `UsageResponse`, or `AnalyticsResponse`

### Request Classes

All extend Saloon's `Request` class. Located in `src/Requests/`:
- `SubmitRequest` - Submit model request to Fal.ai
- `FetchRequestStatusRequest` - Get status of queued request
- `GetResultRequest` - Fetch result of completed request
- `StreamRequest` - Stream real-time results using SSE
- `CancelRequest` - Cancel queued request

### Response Classes

Extend Saloon's `Response` and provide convenient accessors. Located in `src/Responses/`:
- `SubmitResponse` - Queued request response with `getRequestId()`, `getStatusUrl()`, `getCancelUrl()`, etc.
- `StatusResponse` - Status with `isInProgress()`, `isCompleted()`, `getLogs()`, etc.
- `ResultResponse` - Final result with `firstImageUrl`, `json()`, etc.
- `StreamResponse` - Server-Sent Event stream response
- `PricingResponse`, `UsageResponse`, `AnalyticsResponse`, `EstimateCostResponse` - Platform API responses with typed accessors

### Webhook System

- **`VerifyFalWebhook`** middleware (`src/Middleware/VerifyFalWebhook.php`) - ED25519 signature verification using JWKS
- **`WebhookVerifier`** service (`src/Services/WebhookVerifier.php`) - Verifies webhook signatures and timestamps to prevent replay attacks
- Pre-configured route at `/webhooks/fal` (loaded via `routes/webhooks.php`)
- JWKS caching for performance (configurable TTL in `config/fal-ai.php`)

### API Endpoints

The package switches between endpoints based on mode (configurable):
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
- `webhook` - Webhook verification settings (JWKS cache TTL, timestamp tolerance, verification timeout)

## Namespace

All code uses `Cjmellor\FalAi` namespace. Auto-registered:
- Service provider: `Cjmellor\FalAi\FalAiServiceProvider`
- Facade: `Cjmellor\FalAi\Facades\FalAi`

## Testing

Uses Pest with Orchestra Testbench. Tests extend `Cjmellor\FalAi\Tests\TestCase` which auto-configures the service provider.

Test structure:
- `tests/Unit/` - Unit tests for individual classes
- `tests/Feature/` - Integration tests for workflows
- `tests/Integration/` - Full end-to-end integration tests
- `tests/Arch.php` - Architecture tests using pest-plugin-arch

Test utilities:
- `tests/TestCase.php` - Base test class with Testbench setup
- `tests/Fixtures/` - Mock responses and test data

## Saloon HTTP Client

The package uses Saloon 3.x for all HTTP communication. Key patterns:
- Connectors extend `Connector` with `resolveBaseUrl()` and `defaultAuth()`
- Requests extend `Request` with `resolveEndpoint()` and `defaultMethod()`
- Responses accessed via `$response->json()`, `$response->status()`, etc.
- Exceptions: `FatalRequestException`, `RequestException` from Saloon

Documentation: https://docs.saloon.dev