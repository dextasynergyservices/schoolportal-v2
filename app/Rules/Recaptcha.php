<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Recaptcha implements ValidationRule
{
    public function __construct(
        private readonly ?string $action = null,
        private readonly ?float $threshold = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $secretKey = config('services.recaptcha.secret_key');

        // Skip validation if keys are not configured (development)
        if (empty($secretKey)) {
            return;
        }

        if (empty($value)) {
            $fail(__('Please complete the reCAPTCHA verification.'));

            return;
        }

        try {
            $response = Http::asForm()
                ->timeout(10)
                ->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret' => $secretKey,
                    'response' => $value,
                    'remoteip' => request()->ip(),
                ]);

            if (! $response->successful()) {
                Log::warning('reCAPTCHA API request failed', [
                    'status' => $response->status(),
                ]);

                // Don't block users if the API is down
                return;
            }

            $data = $response->json();

            if (! ($data['success'] ?? false)) {
                Log::error('reCAPTCHA verification failed', [
                    'error_codes' => $data['error-codes'] ?? [],
                    'hostname' => $data['hostname'] ?? null,
                    'request_host' => request()->getHost(),
                    'response_data' => $data,
                ]);
                $fail(__('reCAPTCHA verification failed. Please try again.'));

                return;
            }

            // For v3: check action matches (only when an expected action is specified)
            if ($this->action !== null && isset($data['action']) && $data['action'] !== $this->action) {
                $fail(__('reCAPTCHA verification failed. Please try again.'));

                return;
            }

            // For v3: check score meets threshold
            $minScore = $this->threshold ?? (float) config('services.recaptcha.threshold', 0.5);
            if (isset($data['score']) && $data['score'] < $minScore) {
                Log::warning('reCAPTCHA score too low', [
                    'score' => $data['score'],
                    'threshold' => $minScore,
                    'action' => $data['action'] ?? null,
                    'ip' => request()->ip(),
                ]);
                $fail(__('reCAPTCHA verification failed. Please try again.'));

                return;
            }
        } catch (\Exception $e) {
            Log::warning('reCAPTCHA verification error', [
                'error' => $e->getMessage(),
            ]);
            // Don't block users if verification fails due to network issues
        }
    }
}
