# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel package for integrating with the Fal.ai API. It provides a fluent interface for AI model interactions with webhook support, streaming, and queue/sync modes.

## Commands

```bash
# Run all tests
composer test

# Run a single test file
./vendor/bin/pest tests/Unit/FalAiTest.php

# Run a single test by name
./vendor/bin/pest --filter="test name here"

# Lint code (Laravel Pint)
composer lint

# Check lint without fixing
composer lint:test

# Run Rector refactoring
composer refactor

# Dry run Rector
composer refactor:dry

# Prepare testbench environment
composer prepare

# Serve workbench app for manual testing
composer serve
```

## Architecture

### Core Components

- **`FalAi`** (`src/FalAi.php`) - Main entry point. Creates fluent request builders and handles API operations (run, status, result, stream, cancel).

- **`FluentRequest`** (`src/Support/FluentRequest.php`) - Chainable request builder. Dynamic `__call` method converts camelCase methods to snake_case API parameters (e.g., `->imageSize('landscape')` becomes `image_size: 'landscape'`). Supports both mutable and immutable patterns.

- **`FalConnector`** (`src/Connectors/FalConnector.php`) - Saloon HTTP connector with `Key` prefix token authentication.

### Request/Response Flow

1. `FalAi::model($id)` returns a `FluentRequest`
2. Chain methods to build request data
3. Call `->run()` (queue), `->sync()` then `->run()`, or `->stream()`
4. Returns typed response objects: `SubmitResponse`, `StatusResponse`, `ResultResponse`, `StreamResponse`

### Webhook System

- **`VerifyFalWebhook`** middleware - ED25519 signature verification using JWKS
- **`WebhookVerifier`** service - Handles signature and timestamp validation
- Pre-configured route at `/webhooks/fal` (loaded via `routes/webhooks.php`)

### API Endpoints

The package switches between endpoints based on mode:
- Queue mode (default): `https://queue.fal.run`
- Sync mode: `https://fal.run`
- Streaming: `https://fal.run` with `/stream` suffix

## Namespace

All code uses `Cjmellor\FalAi` namespace. The package auto-registers:
- Service provider: `Cjmellor\FalAi\FalAiServiceProvider`
- Facade: `Cjmellor\FalAi\Facades\FalAi`

## Testing

Uses Pest with Orchestra Testbench. Tests extend `Cjmellor\FalAi\Tests\TestCase` which configures the service provider.

Test structure:
- `tests/Unit/` - Unit tests for individual classes
- `tests/Feature/` - Integration tests for workflows
- `tests/Integration/` - Full integration tests
- `tests/Arch.php` - Architecture tests