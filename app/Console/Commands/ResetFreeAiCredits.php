<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\School;
use Illuminate\Console\Command;

class ResetFreeAiCredits extends Command
{
    protected $signature = 'credits:reset-free';

    protected $description = 'Reset free AI credits to 15 for all schools (runs monthly on the 1st)';

    public function handle(): int
    {
        $count = School::query()->update([
            'ai_free_credits' => 15,
            'ai_free_credits_reset_at' => now()->addMonth()->startOfMonth(),
        ]);

        $this->info("Reset free AI credits for {$count} schools.");

        return self::SUCCESS;
    }
}
