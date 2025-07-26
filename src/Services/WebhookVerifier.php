<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Services;

use Cjmellor\FalAi\Exceptions\WebhookVerificationException;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SodiumException;

class WebhookVerifier
{
    private const JWKS_ENDPOINT = 'https://rest.alpha.fal.ai/.well-known/jwks.json';

    private const JWKS_CACHE_KEY = 'fal_ai_jwks';

    private const REQUIRED_HEADERS = [
        'X-Fal-Webhook-Request-Id',
        'X-Fal-Webhook-User-Id',
        'X-Fal-Webhook-Timestamp',
        'X-Fal-Webhook-Signature',
    ];

    /**
     * Verify a webhook request signature
     *
     * @param  Request  $request  The incoming webhook request
     * @return bool True if verification succeeds
     *
     * @throws WebhookVerificationException If verification fails
     */
    public function verify(Request $request): bool
    {
        // Extract required headers
        $headers = $this->extractHeaders($request);

        // Validate timestamp
        $this->validateTimestamp($headers['timestamp']);

        // Get request body
        $body = $request->getContent();

        // Construct message for verification
        $message = $this->constructMessage($headers, $body);

        // Get public keys
        $publicKeys = $this->getPublicKeys();

        // Verify signature
        return $this->verifySignature($message, $headers['signature'], $publicKeys);
    }

    /**
     * Clear the JWKS cache (useful for testing)
     */
    public function clearCache(): void
    {
        Cache::forget(self::JWKS_CACHE_KEY);
    }

    /**
     * Extract required headers from the request
     *
     * @throws WebhookVerificationException
     */
    private function extractHeaders(Request $request): array
    {
        $headers = [];

        foreach (self::REQUIRED_HEADERS as $header) {
            $value = $request->header($header);
            throw_if(
                condition: ! $value,
                exception: new WebhookVerificationException("Missing required header: {$header}")
            );

            $key = mb_strtolower(str_replace(['X-Fal-Webhook-', '-'], ['', '_'], $header));
            $headers[$key] = $value;
        }

        return $headers;
    }

    /**
     * Validate the timestamp to prevent replay attacks
     *
     * @throws WebhookVerificationException
     */
    private function validateTimestamp(string $timestamp): void
    {
        $webhookTime = (int) $timestamp;
        $currentTime = time();
        $timeDiff = abs($currentTime - $webhookTime);
        $tolerance = config('fal-ai.webhook.timestamp_tolerance', 300);

        throw_if(
            condition: $timeDiff > $tolerance,
            exception: new WebhookVerificationException(
                "Timestamp too old or too far in the future. Difference: {$timeDiff} seconds"
            )
        );
    }

    /**
     * Construct the message for signature verification
     */
    private function constructMessage(array $headers, string $body): string
    {
        $bodyHash = hash('sha256', $body);

        return implode("\n", [
            $headers['request_id'],
            $headers['user_id'],
            $headers['timestamp'],
            $bodyHash,
        ]);
    }

    /**
     * Get public keys from JWKS endpoint with caching
     *
     * @throws WebhookVerificationException
     */
    private function getPublicKeys(): array
    {
        $cacheTtl = config('fal-ai.webhook.jwks_cache_ttl', 86400);

        return Cache::remember(self::JWKS_CACHE_KEY, $cacheTtl, function () {
            try {
                $timeout = config('fal-ai.webhook.verification_timeout', 10);
                $response = Http::timeout($timeout)->get(self::JWKS_ENDPOINT);

                throw_if(
                    condition: ! $response->successful(),
                    exception: new WebhookVerificationException('Failed to fetch JWKS: '.$response->status())
                );

                $jwks = $response->json();

                throw_if(
                    condition: ! isset($jwks['keys']) || ! is_array($jwks['keys']),
                    exception: new WebhookVerificationException('Invalid JWKS format')
                );

                $publicKeys = [];
                foreach ($jwks['keys'] as $key) {
                    if (isset($key['x']) && isset($key['kty']) && $key['kty'] === 'OKP') {
                        $publicKeys[] = $this->base64UrlDecode($key['x']);
                    }
                }

                throw_if(
                    condition: empty($publicKeys),
                    exception: new WebhookVerificationException('No valid public keys found in JWKS')
                );

                return $publicKeys;

            } catch (Exception $e) {
                throw new WebhookVerificationException('Failed to fetch public keys: '.$e->getMessage());
            }
        });
    }

    /**
     * Verify the signature using ED25519
     *
     * @throws WebhookVerificationException
     */
    private function verifySignature(string $message, string $signature, array $publicKeys): bool
    {
        try {
            $signatureBytes = hex2bin($signature);
            throw_if(
                condition: $signatureBytes === false,
                exception: new WebhookVerificationException('Invalid signature format')
            );

            foreach ($publicKeys as $publicKey) {
                try {
                    if (sodium_crypto_sign_verify_detached($signatureBytes, $message, $publicKey)) {
                        return true;
                    }
                } catch (SodiumException $e) {
                    // Continue to next key
                    continue;
                }
            }

            throw new WebhookVerificationException('Signature verification failed');
        } catch (SodiumException $e) {
            throw new WebhookVerificationException('Cryptographic error: '.$e->getMessage());
        }
    }

    /**
     * Base64 URL decode
     */
    private function base64UrlDecode(string $data): string
    {
        $remainder = mb_strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($data, '-_', '+/'));
    }
}
