<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AiCreditAllocation;
use App\Models\AiCreditPurchase;
use App\Models\AiCreditUsageLog;
use App\Models\School;
use App\Models\User;
use App\Notifications\CreditPurchaseConfirmation;
use Illuminate\Support\Facades\DB;

class AiCreditService
{
    /**
     * Get total available credits for the school (free + purchased).
     */
    public function getSchoolBalance(School $school): int
    {
        return $school->ai_free_credits + $school->ai_purchased_credits;
    }

    /**
     * Get available credits for a specific level (from allocation), or school-wide if no allocation.
     */
    public function getAvailableCredits(School $school, ?int $levelId = null): int
    {
        if ($levelId) {
            $allocation = AiCreditAllocation::where('school_id', $school->id)
                ->where('level_id', $levelId)
                ->first();

            if ($allocation) {
                return $allocation->remainingCredits();
            }
        }

        return $this->getSchoolBalance($school);
    }

    /**
     * Check if the school/level has enough credits for 1 AI generation.
     */
    public function hasCredits(School $school, ?int $levelId = null): bool
    {
        return $this->getAvailableCredits($school, $levelId) >= 1;
    }

    /**
     * Deduct 1 credit for an AI generation. Free credits consumed first, then purchased.
     * Returns true on success, false if insufficient credits.
     *
     * Uses a DB transaction with a row-level lock (lockForUpdate) so concurrent
     * requests cannot both pass the credits check and both decrement — which would
     * let credits go negative.
     */
    public function deductCredit(School $school, User $user, string $usageType, ?int $entityId = null, ?int $levelId = null): bool
    {
        $deducted = DB::transaction(function () use ($school, $user, $usageType, $entityId, $levelId): bool {
            // Re-read the school row with an exclusive lock so no other transaction
            // can read or write it until this transaction commits.
            $lockedSchool = School::lockForUpdate()->find($school->id);

            if (! $lockedSchool) {
                return false;
            }

            // If level allocation is configured, lock that row too and check it.
            if ($levelId) {
                $allocation = AiCreditAllocation::where('school_id', $lockedSchool->id)
                    ->where('level_id', $levelId)
                    ->lockForUpdate()
                    ->first();

                if ($allocation) {
                    if ($allocation->remainingCredits() < 1) {
                        return false;
                    }
                    $allocation->increment('used_credits');
                }
            }

            // Check school-wide balance.
            $balance = $lockedSchool->ai_free_credits + $lockedSchool->ai_purchased_credits;
            if ($balance < 1) {
                return false;
            }

            // Deduct: free credits first, then purchased.
            if ($lockedSchool->ai_free_credits > 0) {
                $lockedSchool->decrement('ai_free_credits');
            } else {
                $lockedSchool->decrement('ai_purchased_credits');
            }

            // Log usage inside the transaction so it's rolled back if anything fails.
            AiCreditUsageLog::create([
                'school_id' => $lockedSchool->id,
                'user_id' => $user->id,
                'level_id' => $levelId,
                'usage_type' => $usageType,
                'entity_id' => $entityId,
                'credits_used' => 1,
            ]);

            return true;
        });

        if (! $deducted) {
            return false;
        }

        // Refresh the school model so the caller sees the updated balance.
        $school->refresh();

        // Warn school admins when credits drop to or below the warning threshold.
        $remaining = $this->getSchoolBalance($school);
        if ($remaining <= 3) {
            app(NotificationService::class)->notifyLowCredits($school->id, $remaining);
        }

        return true;
    }

    /**
     * Purchase credits (completes a purchase record and adds credits to school).
     *
     * Both the status update and the credit increment happen inside a single
     * transaction so a mid-flight failure cannot leave the purchase "completed"
     * without the credits being added.
     */
    public function completePurchase(AiCreditPurchase $purchase): void
    {
        DB::transaction(function () use ($purchase): void {
            $purchase->update(['status' => 'completed']);

            $school = School::lockForUpdate()->find($purchase->school_id);
            $school->increment('ai_purchased_credits', $purchase->credits);
            $school->increment('ai_credits_total_purchased', $purchase->credits);
        });

        // Notifications run outside the transaction — a notification failure should
        // never roll back a completed purchase.
        $school = School::find($purchase->school_id);
        $formattedAmount = '₦'.number_format((float) $purchase->amount_naira, 0);

        app(NotificationService::class)->notifyCreditPurchased(
            $school,
            $purchase->credits,
            $formattedAmount,
        );

        $purchaser = User::withoutGlobalScopes()->find($purchase->purchased_by);
        if ($purchaser?->email) {
            $newBalance = $school->ai_free_credits + $school->ai_purchased_credits;
            $purchaser->notify(new CreditPurchaseConfirmation(
                schoolName: $school->name,
                credits: $purchase->credits,
                amount: $formattedAmount,
                newBalance: $newBalance,
            ));
        }
    }

    /**
     * Get usage stats for the current month.
     */
    public function getMonthlyUsage(School $school): array
    {
        $startOfMonth = now()->startOfMonth();

        $usage = AiCreditUsageLog::where('school_id', $school->id)
            ->where('created_at', '>=', $startOfMonth)
            ->selectRaw('usage_type, COUNT(*) as count, SUM(credits_used) as total_credits')
            ->groupBy('usage_type')
            ->pluck('total_credits', 'usage_type')
            ->toArray();

        return [
            'quizzes' => (int) ($usage['quiz'] ?? 0),
            'games' => (int) ($usage['game'] ?? 0),
            'total' => array_sum($usage),
        ];
    }
}
