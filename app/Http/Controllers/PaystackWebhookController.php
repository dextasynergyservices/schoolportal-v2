<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AiCreditPurchase;
use App\Services\AiCreditService;
use App\Services\PaystackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaystackWebhookController extends Controller
{
    public function __construct(
        private readonly PaystackService $paystackService,
        private readonly AiCreditService $creditService,
    ) {}

    /**
     * Handle Paystack webhook events.
     *
     * Paystack sends a POST with JSON body and an X-Paystack-Signature header.
     */
    public function __invoke(Request $request): JsonResponse
    {
        // Validate webhook signature
        $signature = $request->header('X-Paystack-Signature', '');
        $payload = $request->getContent();

        if (! $this->paystackService->validateWebhookSignature($payload, $signature)) {
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        $event = $request->input('event');
        $data = $request->input('data');

        if ($event === 'charge.success') {
            $this->handleChargeSuccess($data);
        }

        return response()->json(['status' => 'ok']);
    }

    private function handleChargeSuccess(array $data): void
    {
        $reference = $data['reference'] ?? null;

        if (! $reference) {
            return;
        }

        // Use a DB transaction with a row-level lock to prevent two simultaneous
        // webhook deliveries for the same reference from both completing the purchase.
        $purchase = DB::transaction(function () use ($reference, $data): ?AiCreditPurchase {
            $purchase = AiCreditPurchase::where('reference', $reference)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->first();

            if (! $purchase) {
                return null; // Already processed or not found — idempotent, do nothing.
            }

            // Verify amount matches before committing anything.
            $expectedKobo = (int) ($purchase->amount_naira * 100);
            $paidKobo = (int) ($data['amount'] ?? 0);

            if ($paidKobo !== $expectedKobo) {
                $purchase->update(['status' => 'failed']);

                return null;
            }

            // Mark as processing so concurrent webhooks cannot enter this block.
            // completePurchase() will set it to 'completed' atomically.
            $purchase->update(['status' => 'processing']);

            return $purchase;
        });

        if (! $purchase) {
            return;
        }

        try {
            $this->creditService->completePurchase($purchase);
        } catch (\Throwable $e) {
            // completePurchase failed — roll back to pending so the webhook can retry.
            $purchase->update(['status' => 'pending']);

            Log::error('Webhook: completePurchase failed, reverted to pending', [
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
