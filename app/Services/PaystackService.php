<?php

declare(strict_types=1);

namespace App\Services;

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

        $response = Http::withToken($this->secretKey)
            ->timeout(15)
            ->post("{$this->baseUrl}/transaction/initialize", [
                'amount' => $amountInKobo,
                'email' => $email,
                'reference' => $reference,
                'callback_url' => $callbackUrl,
                'metadata' => $metadata,
            ]);

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
        $response = Http::withToken($this->secretKey)
            ->timeout(15)
            ->get("{$this->baseUrl}/transaction/verify/{$reference}");

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
     * Validate a Paystack webhook signature.
     */
    public function validateWebhookSignature(string $payload, string $signature): bool
    {
        $computed = hash_hmac('sha512', $payload, $this->secretKey);

        return hash_equals($computed, $signature);
    }
}
