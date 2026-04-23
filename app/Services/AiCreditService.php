<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AiCreditAllocation;
use App\Models\AiCreditPurchase;
use App\Models\AiCreditUsageLog;
use App\Models\School;
use App\Models\User;
use App\Notifications\CreditPurchaseConfirmation;

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
     */
    public function deductCredit(School $school, User $user, string $usageType, ?int $entityId = null, ?int $levelId = null): bool
    {
        if (! $this->hasCredits($school, $levelId)) {
            return false;
        }

        // If level allocation exists, deduct from there
        if ($levelId) {
            $allocation = AiCreditAllocation::where('school_id', $school->id)
                ->where('level_id', $levelId)
                ->first();

            if ($allocation && $allocation->remainingCredits() >= 1) {
                $allocation->increment('used_credits');
            }
        }

        // Deduct from school pool: free credits first, then purchased
        if ($school->ai_free_credits > 0) {
            $school->decrement('ai_free_credits');
        } else {
            $school->decrement('ai_purchased_credits');
        }

        // Log usage
        AiCreditUsageLog::create([
            'school_id' => $school->id,
            'user_id' => $user->id,
            'level_id' => $levelId,
            'usage_type' => $usageType,
            'entity_id' => $entityId,
            'credits_used' => 1,
        ]);

        return true;
    }

    /**
     * Purchase credits (completes a purchase record and adds credits to school).
     */
    public function completePurchase(AiCreditPurchase $purchase): void
    {
        $purchase->update(['status' => 'completed']);

        $school = School::find($purchase->school_id);
        $school->increment('ai_purchased_credits', $purchase->credits);
        $school->increment('ai_credits_total_purchased', $purchase->credits);

        $formattedAmount = '₦'.number_format((float) $purchase->amount_naira, 0);

        // Notify super admins of the purchase (database + email)
        app(NotificationService::class)->notifyCreditPurchased(
            $school,
            $purchase->credits,
            $formattedAmount,
        );

        // Send confirmation email to the purchaser (school admin)
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
