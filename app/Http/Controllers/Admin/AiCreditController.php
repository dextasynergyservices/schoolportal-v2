<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiCreditAllocation;
use App\Models\AiCreditPurchase;
use App\Models\AiCreditUsageLog;
use App\Models\SchoolLevel;
use App\Services\AiCreditService;
use App\Services\PaystackService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiCreditController extends Controller
{
    public function __construct(
        private readonly AiCreditService $creditService,
        private readonly PaystackService $paystackService,
    ) {}

    public function index(): View
    {
        $school = app('current.school');
        $balance = $this->creditService->getSchoolBalance($school);
        $monthlyUsage = $this->creditService->getMonthlyUsage($school);

        $levels = SchoolLevel::where('is_active', true)->orderBy('sort_order')->get();
        $allocations = AiCreditAllocation::where('school_id', $school->id)
            ->with('level:id,name')
            ->get()
            ->keyBy('level_id');

        $purchases = AiCreditPurchase::where('school_id', $school->id)
            ->where('status', 'completed')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $recentUsage = AiCreditUsageLog::where('school_id', $school->id)
            ->with(['user:id,name', 'level:id,name'])
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return view('admin.credits.index', compact(
            'school',
            'balance',
            'monthlyUsage',
            'levels',
            'allocations',
            'purchases',
            'recentUsage',
        ));
    }

    public function purchase(): View
    {
        $school = app('current.school');
        $balance = $this->creditService->getSchoolBalance($school);

        return view('admin.credits.purchase', compact('school', 'balance'));
    }

    public function processPurchase(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'credits' => ['required', 'integer', 'min:5', 'max:500'],
        ]);

        $credits = (int) $validated['credits'];

        // Must be multiple of 5
        if ($credits % 5 !== 0) {
            return redirect()->back()->with('error', __('Credits must be a multiple of 5.'));
        }

        $amountNaira = ($credits / 5) * 1000;
        $school = app('current.school');
        $user = auth()->user();

        // Create pending purchase
        $purchase = AiCreditPurchase::create([
            'school_id' => $school->id,
            'purchased_by' => $user->id,
            'credits' => $credits,
            'amount_naira' => $amountNaira,
            'payment_method' => 'paystack',
            'status' => 'pending',
        ]);

        // Initialize Paystack transaction (amount in kobo)
        $amountKobo = (int) ($amountNaira * 100);
        $callbackUrl = route('admin.credits.purchase.callback');

        $transaction = $this->paystackService->initializeTransaction(
            amountInKobo: $amountKobo,
            email: $user->email ?? "{$user->username}@{$school->slug}.schoolportal.local",
            callbackUrl: $callbackUrl,
            metadata: [
                'purchase_id' => $purchase->id,
                'school_id' => $school->id,
                'credits' => $credits,
            ],
        );

        if (! $transaction) {
            $purchase->update(['status' => 'failed']);

            return redirect()->route('admin.credits.purchase')
                ->with('error', __('Payment could not be initialized. Please try again.'));
        }

        // Store reference on purchase
        $purchase->update(['reference' => $transaction['reference']]);

        // Redirect to Paystack checkout
        return redirect()->away($transaction['authorization_url']);
    }

    /**
     * Handle callback from Paystack after payment.
     */
    public function purchaseCallback(Request $request): RedirectResponse
    {
        $reference = $request->query('reference');

        if (! $reference) {
            return redirect()->route('admin.credits.index')
                ->with('error', __('Invalid payment reference.'));
        }

        // Find the purchase by reference
        $school = app('current.school');
        $purchase = AiCreditPurchase::where('school_id', $school->id)
            ->where('reference', $reference)
            ->where('status', 'pending')
            ->first();

        if (! $purchase) {
            return redirect()->route('admin.credits.index')
                ->with('error', __('Purchase not found or already processed.'));
        }

        // Verify with Paystack
        $verification = $this->paystackService->verifyTransaction($reference);

        if (! $verification || $verification['status'] !== 'success') {
            $purchase->update(['status' => 'failed']);

            return redirect()->route('admin.credits.index')
                ->with('error', __('Payment verification failed. If you were charged, please contact support.'));
        }

        // Verify amount matches (prevent tampering)
        $expectedKobo = (int) ($purchase->amount_naira * 100);
        if ($verification['amount'] !== $expectedKobo) {
            $purchase->update(['status' => 'failed']);

            return redirect()->route('admin.credits.index')
                ->with('error', __('Payment amount mismatch. Please contact support.'));
        }

        // Complete purchase
        $purchase->update(['status' => 'completed']);
        $this->creditService->completePurchase($purchase);

        return redirect()->route('admin.credits.index')
            ->with('success', __(':credits credits purchased for ₦:amount.', [
                'credits' => $purchase->credits,
                'amount' => number_format((float) $purchase->amount_naira),
            ]));
    }

    public function allocate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'allocations' => ['required', 'array'],
            'allocations.*.level_id' => ['required', 'exists:school_levels,id'],
            'allocations.*.credits' => ['required', 'integer', 'min:0'],
        ]);

        $school = app('current.school');

        foreach ($validated['allocations'] as $alloc) {
            AiCreditAllocation::updateOrCreate(
                ['school_id' => $school->id, 'level_id' => $alloc['level_id']],
                ['allocated_credits' => $alloc['credits'], 'allocated_by' => auth()->id()],
            );
        }

        return redirect()->route('admin.credits.index')
            ->with('success', __('Credit allocations updated.'));
    }
}
