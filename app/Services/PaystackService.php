<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PaystackService
{
    private string $secretKey;

    private string $baseUrl;

    public function __construct()
    {
        $this->secretKey = (string) config('services.paystack.secret_key');
        $this->baseUrl = (string) config('services.paystack.base_url');
    }

    /**
     * Initialize a Paystack transaction. Returns the authorization URL and reference.
     *
     * @param  int  $amountInKobo  Amount in kobo (N1,000 = 100000 kobo)
     * @param  string  $email  Customer email
     * @param  array<string, mixed>  $metadata  Extra data to attach
     * @return array{authorization_url: string, access_code: string, reference: string}|null
     */
    public function initializeTransaction(int $amountInKobo, string $email, string $callbackUrl, array $metadata = []): ?array
    {
        $reference = 'SP-'.Str::upper(Str::random(12));

        $response = $this->callWithRetry(fn () => Http::withToken($this->secretKey)
            ->timeout(15)
            ->post("{$this->baseUrl}/transaction/initialize", [
                'amount' => $amountInKobo,
                'email' => $email,
                'reference' => $reference,
                'callback_url' => $callbackUrl,
                'metadata' => $metadata,
            ]));

        if ($response->successful() && $response->json('status') === true) {
            $data = $response->json('data');

            return [
                'authorization_url' => $data['authorization_url'],
                'access_code' => $data['access_code'],
                'reference' => $data['reference'],
            ];
        }

        return null;
    }

    /**
     * Verify a Paystack transaction by reference.
     *
     * @return array{status: string, amount: int, reference: string, metadata: array}|null
     */
    public function verifyTransaction(string $reference): ?array
    {
        $response = $this->callWithRetry(fn () => Http::withToken($this->secretKey)
            ->timeout(15)
            ->get("{$this->baseUrl}/transaction/verify/{$reference}"));

        if ($response->successful() && $response->json('status') === true) {
            $data = $response->json('data');

            return [
                'status' => $data['status'],          // 'success', 'failed', 'abandoned'
                'amount' => (int) $data['amount'],     // in kobo
                'reference' => $data['reference'],
                'metadata' => $data['metadata'] ?? [],
            ];
        }

        return null;
    }

    /**
     * Execute an HTTP callable with up to 2 retries on transient server errors
     * (HTTP 429, 502, 503) or network-level connection failures, using linear
     * back-off (500 ms, 1 000 ms) so we don't hammer Paystack under load.
     *
     * @param  callable(): Response  $fn
     */
    private function callWithRetry(callable $fn): Response
    {
        $transient = [429, 502, 503];
        $maxAttempts = 3;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $response = $fn();

                // Retry on transient HTTP status codes (but not on the final attempt).
                if ($attempt < $maxAttempts && in_array($response->status(), $transient, true)) {
                    usleep($attempt * 500_000); // 500 ms then 1 000 ms

                    continue;
                }

                return $response;
            } catch (ConnectionException $e) {
                if ($attempt >= $maxAttempts) {
                    throw $e;
                }
                usleep($attempt * 500_000);
            }
        }

        // Unreachable — the loop always returns or re-throws before this point.
        throw new \RuntimeException('Paystack HTTP call failed after retries.');
    }

    /**
     * Validate a Paystack webhook signature.
     */
    public function validateWebhookSignature(string $payload, string $signature): bool
    {
        $computed = hash_hmac('sha512', $payload, $this->secretKey);

        return hash_equals($computed, $signature);
    }
}
