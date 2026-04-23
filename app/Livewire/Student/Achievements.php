<?php

declare(strict_types=1);

namespace App\Livewire\Student;

use App\Services\AchievementService;
use Illuminate\View\View;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class Achievements extends Component
{
    public int $unlockedCount = 0;

    public int $totalCount = 0;

    /** @var list<array> */
    public array $recentAchievements = [];

    /** @var list<array> */
    public array $nextGoals = [];

    public int $loginStreak = 0;

    public int $bestLoginStreak = 0;

    public int $quizPassStreak = 0;

    public function mount(): void
    {
        $student = auth()->user();
        $service = app(AchievementService::class);

        $progress = $service->getStudentProgress($student);
        $this->unlockedCount = $progress['unlocked_count'];
        $this->totalCount = $progress['total'];

        // Show latest 5 unlocked
        $this->recentAchievements = array_slice($progress['unlocked'], 0, 5);

        // Serialize unlocked_at for Livewire
        $this->recentAchievements = array_map(function ($a) {
            $a['unlocked_at'] = $a['unlocked_at']->toIso8601String();

            return $a;
        }, $this->recentAchievements);

        $this->nextGoals = $service->getNextGoals($student, 3);

        $profile = $student->studentProfile;
        $this->loginStreak = $profile?->login_streak ?? 0;
        $this->bestLoginStreak = $profile?->best_login_streak ?? 0;
        $this->quizPassStreak = $profile?->quiz_pass_streak ?? 0;
    }

    public function placeholder(): string
    {
        return <<<'HTML'
        <div class="dash-panel dash-animate" style="padding: 0;">
            <div class="dash-panel-header">
                <div class="h-4 w-32 bg-zinc-200 dark:bg-zinc-700 rounded animate-pulse"></div>
            </div>
            <div class="p-4 space-y-3">
                <div class="h-16 bg-zinc-100 dark:bg-zinc-800 rounded-lg animate-pulse"></div>
                <div class="flex gap-2">
                    <div class="h-10 w-10 bg-zinc-200 dark:bg-zinc-700 rounded-full animate-pulse"></div>
                    <div class="h-10 w-10 bg-zinc-200 dark:bg-zinc-700 rounded-full animate-pulse"></div>
                    <div class="h-10 w-10 bg-zinc-200 dark:bg-zinc-700 rounded-full animate-pulse"></div>
                </div>
            </div>
        </div>
        HTML;
    }

    public function render(): View
    {
        return view('livewire.student.achievements');
    }
}
