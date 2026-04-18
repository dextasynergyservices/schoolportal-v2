<?php

declare(strict_types=1);

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\GamePlay;
use App\Models\Notice;
use App\Models\QuizAttempt;
use App\Models\Result;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $parent = auth()->user();
        $school = app('current.school');

        $currentSession = $school->currentSession();
        $currentTerm = $school->currentTerm();

        // Load linked children with their profiles, classes, and teachers
        $childIds = $parent->children()->pluck('student_id');

        $children = User::whereIn('id', $childIds)
            ->where('school_id', $parent->school_id)
            ->with([
                'studentProfile.class.teacher:id,name',
                'studentProfile.class.level:id,name',
            ])
            ->get();

        // ── Bulk per-child stats (avoid N+1) ─────────────────────────
        $childrenStats = [];
        $totalResults = 0;
        $totalQuizzesTaken = 0;
        $totalGamesPlayed = 0;
        $allQuizPercentages = [];

        if ($childIds->isNotEmpty()) {
            // Results counts per child
            $resultsCounts = Result::where('status', 'approved')
                ->whereIn('student_id', $childIds)
                ->selectRaw('student_id, count(*) as total')
                ->groupBy('student_id')
                ->pluck('total', 'student_id');

            // Latest result per child
            $latestResults = Result::where('status', 'approved')
                ->whereIn('student_id', $childIds)
                ->with(['session:id,name', 'term:id,name'])
                ->orderByDesc('created_at')
                ->get()
                ->groupBy('student_id')
                ->map(fn ($group) => $group->first());

            // Quiz stats per child
            $quizStats = QuizAttempt::whereIn('student_id', $childIds)
                ->whereIn('status', ['submitted', 'timed_out'])
                ->selectRaw('student_id, count(*) as taken, avg(percentage) as avg_pct, sum(case when passed = 1 then 1 else 0 end) as passed_count')
                ->groupBy('student_id')
                ->get()
                ->keyBy('student_id');

            // Game stats per child
            $gameStats = GamePlay::whereIn('student_id', $childIds)
                ->where('completed', true)
                ->selectRaw('student_id, count(*) as played, avg(percentage) as avg_pct')
                ->groupBy('student_id')
                ->get()
                ->keyBy('student_id');

            foreach ($children as $child) {
                $cId = $child->id;
                $rCount = (int) ($resultsCounts[$cId] ?? 0);
                $qs = $quizStats[$cId] ?? null;
                $gs = $gameStats[$cId] ?? null;

                $quizzesTaken = $qs ? (int) $qs->taken : 0;
                $quizAvg = $qs ? round((float) $qs->avg_pct, 1) : null;
                $quizPassRate = ($quizzesTaken > 0 && $qs) ? round(((int) $qs->passed_count / $quizzesTaken) * 100) : 0;

                $gamesPlayed = $gs ? (int) $gs->played : 0;
                $gameAvg = $gs ? round((float) $gs->avg_pct, 1) : null;

                $childrenStats[$cId] = [
                    'results_count' => $rCount,
                    'latest_result' => $latestResults[$cId] ?? null,
                    'quizzes_taken' => $quizzesTaken,
                    'quiz_avg' => $quizAvg,
                    'quiz_pass_rate' => $quizPassRate,
                    'games_played' => $gamesPlayed,
                    'game_avg' => $gameAvg,
                ];

                $totalResults += $rCount;
                $totalQuizzesTaken += $quizzesTaken;
                $totalGamesPlayed += $gamesPlayed;
                if ($quizAvg !== null) {
                    $allQuizPercentages[] = (float) $quizAvg;
                }
            }
        }

        $overallQuizAvg = count($allQuizPercentages) > 0
            ? round(array_sum($allQuizPercentages) / count($allQuizPercentages))
            : null;

        // ── Notices (filtered by parent role + children's levels) ────
        $childLevelIds = $children->map(fn ($c) => $c->studentProfile?->class?->level_id)->filter()->unique()->values();

        $recentNotices = Notice::where('is_published', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            })
            ->where(function ($q) {
                $q->whereNull('target_roles')
                    ->orWhereJsonContains('target_roles', 'parent');
            })
            ->where(function ($q) use ($childLevelIds) {
                $q->whereNull('target_levels');
                foreach ($childLevelIds as $levelId) {
                    $q->orWhereJsonContains('target_levels', $levelId);
                }
            })
            ->latest('published_at')
            ->take(5)
            ->get();

        return view('parent.dashboard', compact(
            'parent', 'school', 'children',
            'currentSession', 'currentTerm',
            'childrenStats', 'totalResults', 'totalQuizzesTaken', 'totalGamesPlayed', 'overallQuizAvg',
            'recentNotices',
        ));
    }
}
