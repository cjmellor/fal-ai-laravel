<?php

declare(strict_types=1);

namespace Cjmellor\FalAi\Drivers\Replicate\Webhooks;

use Cjmellor\FalAi\Exceptions\WebhookVerificationException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReplicateWebhookVerifier
{
    private const REQUIRED_HEADERS = [
        'webhook-id',
        'webhook-timestamp',
        'webhook-signature',
    ];

    /**
     * Verify a webhook request signature.
     *
     * @param  Request  $request  The incoming webhook request
     * @return bool True if verification succeeds
     *
     * @throws WebhookVerificationException If verification fails
     */
    public function verify(Request $request): bool
    {
        // Check if verification is enabled
        if (! config('fal-ai.drivers.replicate.webhook.verify_signatures', true)) {
            return true;
        }

        // Extract required headers
        $headers = $this->extractHeaders($request);

        // Validate timestamp
        $this->validateTimestamp((int) $headers['timestamp']);

        // Get request body
        $body = $request->getContent();

        // Construct signed content
        $signedContent = $this->constructSignedContent(
            $headers['id'],
            $headers['timestamp'],
            $body
        );

        // Get signing secret
        $secret = $this->getSigningSecret();

        // Verify signature
        return $this->verifySignature($signedContent, $headers['signature'], $secret);
    }

    /**
     * Extract required headers from the request.
     *
     * @return array<string, string>
     *
     * @throws WebhookVerificationException
     */
    public function extractHeaders(Request $request): array
    {
        $headers = [];

        foreach (self::REQUIRED_HEADERS as $header) {
            $value = $request->header($header);
            throw_if(
                condition: ! $value,
                exception: new WebhookVerificationException("Missing required header: {$header}")
            );

            // Convert header name to key (webhook-id -> id, webhook-timestamp -> timestamp)
            $key = str_replace('webhook-', '', $header);
            $headers[$key] = $value;
        }

        return $headers;
    }

    /**
     * Validate the timestamp to prevent replay attacks.
     *
     * @throws WebhookVerificationException
     */
    public function validateTimestamp(int $timestamp): void
    {
        $currentTime = time();
        $timeDiff = abs($currentTime - $timestamp);
        $tolerance = config('fal-ai.drivers.replicate.webhook.timestamp_tolerance', 300);

        throw_if(
            condition: $timeDiff > $tolerance,
            exception: new WebhookVerificationException(
                "Timestamp too old or too far in the future. Difference: {$timeDiff} seconds"
            )
        );
    }

    /**
     * Construct the signed content for verification.
     *
     * Format: "{webhook-id}.{webhook-timestamp}.{body}"
     */
    public function constructSignedContent(string $id, string $timestamp, string $body): string
    {
        return "{$id}.{$timestamp}.{$body}";
    }

    /**
     * Verify the signature using HMAC-SHA256.
     *
     * @throws WebhookVerificationException
     */
    public function verifySignature(string $signedContent, string $signatureHeader, string $secret): bool
    {
        // Parse the signature header (format: "v1,{base64_signature}" or multiple signatures)
        $signatures = $this->parseSignatures($signatureHeader);

        throw_if(
            condition: $signatures === [],
            exception: new WebhookVerificationException('No valid signatures found in header')
        );

        // Decode the secret from base64
        $secretBytes = base64_decode($secret, true);
        throw_if(
            condition: $secretBytes === false,
            exception: new WebhookVerificationException('Invalid signing secret format')
        );

        // Calculate expected signature
        $expectedSignature = base64_encode(
            hash_hmac('sha256', $signedContent, $secretBytes, true)
        );

        // Check if any signature matches
        foreach ($signatures as $signature) {
            if (hash_equals($expectedSignature, $signature)) {
                return true;
            }
        }

        throw new WebhookVerificationException('Signature verification failed');
    }

    /**
     * Get the signing secret from config.
     *
     * @throws WebhookVerificationException
     */
    private function getSigningSecret(): string
    {
        $secret = config('fal-ai.drivers.replicate.webhook.signing_secret');

        throw_if(
            condition: blank($secret),
            exception: new WebhookVerificationException(
                'Replicate webhook signing secret not configured. Set REPLICATE_WEBHOOK_SECRET in your .env file.'
            )
        );

        // Remove the 'whsec_' prefix if present
        if (Str::startsWith($secret, 'whsec_')) {
            $secret = Str::after($secret, 'whsec_');
        }

        return $secret;
    }

    /**
     * Parse the signature header into individual signatures.
     *
     * The header format is: "v1,{base64_signature} v2,{base64_signature} ..."
     * We only support v1 signatures.
     *
     * @return array<string> Array of base64-encoded signatures
     */
    private function parseSignatures(string $signatureHeader): array
    {
        $signatures = [];
        $parts = Str::of($signatureHeader)->explode(' ');

        foreach ($parts as $part) {
            $part = Str::trim($part);
            if ($part === '') {
                continue;
            }

            // Check for v1 prefix
            if (Str::startsWith($part, 'v1,')) {
                $signatures[] = Str::after($part, 'v1,');
            }
        }

        return $signatures;
    }
}
