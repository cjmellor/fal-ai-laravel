# Changelog

## v2.0.1 - 2026-01-15

### What's Changed

- Add `Config::preventStrayRequests()` to TestCase to prevent any unmocked HTTP requests from going through to real APIs during testing

**Full Changelog**: https://github.com/cjmellor/fal-ai-laravel/compare/v2.0.0...v2.0.1

## v2.0.0 - 2026-01-11

### Added

- Multi-provider architecture with driver-based design
- Replicate driver for Replicate.com API integration
- `AIManager` class using Laravel's Manager pattern
- `DriverInterface` contract for all drivers
- `SupportsPlatform` interface for Platform API support
- `DriverResponseInterface` for typed response handling
- `RequestMode` enum for type-safe execution mode selection
- `PredictionStatus` enum for Replicate prediction status handling
- `PredictionResponse` class with typed accessors and status helpers
- Replicate webhook verification middleware (`VerifyReplicateWebhook`)
- Built-in `/webhooks/replicate` route for Replicate webhooks

### Changed

- Configuration restructured to support multiple drivers under `drivers.*`
- Facade now proxies to `AIManager` instead of `FalAi` class
- Platform APIs accessed via `platform()` method on Fal driver
- Request modes use `RequestMode` enum internally

### Removed

- `FalAi` class (replaced by `FalDriver` and `AIManager`)
- `FluentRequestInterface` contract
- Direct `run()`, `status()`, `result()` methods on facade (use fluent interface)

### Breaking Changes

- Configuration structure changed - republish config file required
- Must use fluent interface (`FalAi::model()->run()`) instead of direct method calls
- `FalAi` class no longer exists - use facade or `AIManager`

**Full Changelog**: https://github.com/cjmellor/fal-ai-laravel/compare/v1.2.0...v2.0.0

## v1.2.0 - 2026-01-03

### What's Changed

* Refactor to PHP 8.4 property hooks and asymmetric visibility by @cjmellor in https://github.com/cjmellor/fal-ai-laravel/pull/12
* Add Delete Request Payloads Platform API endpoint by @cjmellor in https://github.com/cjmellor/fal-ai-laravel/pull/11

**Full Changelog**: https://github.com/cjmellor/fal-ai-laravel/compare/v1.1.0...v1.2.0

## v1.0.1 - 2025-09-09

### What's Changed

* fix(webhooks): add named parameter to webhook route by @cjmellor in https://github.com/cjmellor/fal-ai-laravel/pull/7

### New Contributors

* @cjmellor made their first contribution in https://github.com/cjmellor/fal-ai-laravel/pull/7

**Full Changelog**: https://github.com/cjmellor/fal-ai-laravel/compare/v1.0.0...v1.0.1

## v1.0.0 - 2025-09-02

### What's Changed

* build(deps): bump aglipanci/laravel-pint-action from 2.3.1 to 2.6 by @dependabot[bot] in https://github.com/cjmellor/fal-ai-laravel/pull/5
* build(deps): bump actions/checkout from 4 to 5 by @dependabot[bot] in https://github.com/cjmellor/fal-ai-laravel/pull/6

**Full Changelog**: https://github.com/cjmellor/fal-ai-laravel/compare/v0.0.1...v1.0.0
