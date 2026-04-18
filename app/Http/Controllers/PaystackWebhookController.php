<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AiCreditPurchase;
use App\Services\AiCreditService;
use App\Services\PaystackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        $purchase = AiCreditPurchase::where('reference', $reference)
            ->where('status', 'pending')
            ->first();

        if (! $purchase) {
            return; // Already processed or not found
        }

        // Verify amount matches
        $expectedKobo = (int) ($purchase->amount_naira * 100);
        $paidKobo = (int) ($data['amount'] ?? 0);

        if ($paidKobo !== $expectedKobo) {
            $purchase->update(['status' => 'failed']);

            return;
        }

        $purchase->update(['status' => 'completed']);
        $this->creditService->completePurchase($purchase);
    }
}
