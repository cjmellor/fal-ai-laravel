<?php

declare(strict_types=1);

use Cjmellor\FalAi\Drivers\Replicate\ReplicateConnector;
use Saloon\Http\Auth\TokenAuthenticator;

covers(ReplicateConnector::class);

beforeEach(function (): void {
    config([
        'fal-ai.drivers.replicate.api_key' => 'test-replicate-api-key',
        'fal-ai.drivers.replicate.base_url' => 'https://api.replicate.com',
    ]);
});

describe('ReplicateConnector', function (): void {

    it('throws InvalidArgumentException when API key is not configured', function (): void {
        config(['fal-ai.drivers.replicate.api_key' => '']);

        $connector = new ReplicateConnector();

        expect(fn () => $connector->getAuthenticator())
            ->toThrow(InvalidArgumentException::class, 'Replicate API key is not configured');
    });

    it('resolves base url from config', function (): void {
        $connector = new ReplicateConnector();

        expect($connector->resolveBaseUrl())->toBe('https://api.replicate.com');
    });

    it('can override base URL via constructor', function (): void {
        $connector = new ReplicateConnector('https://custom.replicate.com');

        expect($connector->resolveBaseUrl())->toBe('https://custom.replicate.com');
    });

    it('falls back to config when no override is provided', function (): void {
        config(['fal-ai.drivers.replicate.base_url' => 'https://custom.replicate.test']);

        $connector = new ReplicateConnector();

        expect($connector->resolveBaseUrl())->toBe('https://custom.replicate.test');
    });

    it('returns correct default headers without Prefer header', function (): void {
        $connector = new ReplicateConnector();

        $headers = $connector->headers()->all();

        expect($headers)->toHaveKey('Content-Type')
            ->and($headers['Content-Type'])->toBe('application/json')
            ->and($headers)->not->toHaveKey('Prefer');
    });

    it('uses Bearer token authentication', function (): void {
        $connector = new ReplicateConnector();

        $authenticator = $connector->getAuthenticator();

        expect($authenticator)->toBeInstanceOf(TokenAuthenticator::class);
    });

});
