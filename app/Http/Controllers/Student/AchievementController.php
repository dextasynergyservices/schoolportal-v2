<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\AchievementService;
use Illuminate\View\View;

class AchievementController extends Controller
{
    public function __invoke(AchievementService $service): View
    {
        $student = auth()->user();

        $progress = $service->getStudentProgress($student);
        $nextGoals = $service->getNextGoals($student, 6);

        // Format unlocked_at for Blade display
        $unlocked = array_map(function (array $a): array {
            $a['unlocked_at_formatted'] = $a['unlocked_at']->format('j M Y');

            return $a;
        }, $progress['unlocked']);

        $profile = $student->studentProfile;
        $loginStreak = $profile?->login_streak ?? 0;
        $bestLoginStreak = $profile?->best_login_streak ?? 0;
        $quizPassStreak = $profile?->quiz_pass_streak ?? 0;

        return view('student.achievements', [
            'unlockedCount' => $progress['unlocked_count'],
            'totalCount' => $progress['total'],
            'unlocked' => $unlocked,
            'locked' => $progress['locked'],
            'nextGoals' => $nextGoals,
            'loginStreak' => $loginStreak,
            'bestLoginStreak' => $bestLoginStreak,
            'quizPassStreak' => $quizPassStreak,
        ]);
    }
}
