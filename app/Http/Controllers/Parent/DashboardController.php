<?php

declare(strict_types=1);

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\ExamAttempt;
use App\Models\GamePlay;
use App\Models\Notice;
use App\Models\QuizAttempt;
use App\Models\Result;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
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

        // ── Bulk per-child stats (cached 5 min, keyed per parent) ────
        $statsCacheKey = "school:{$school->id}:parent:{$parent->id}:dashboard:stats";
        $cachedStats = Cache::remember($statsCacheKey, now()->addMinutes(5), function () use ($childIds): array {
            $resultsCounts = Result::where('status', 'approved')
                ->whereIn('student_id', $childIds)
                ->selectRaw('student_id, count(*) as total')
                ->groupBy('student_id')
                ->pluck('total', 'student_id')
                ->toArray();

            $latestResults = Result::where('status', 'approved')
                ->whereIn('student_id', $childIds)
                ->with(['session:id,name', 'term:id,name'])
                ->orderByDesc('created_at')
                ->get()
                ->groupBy('student_id')
                ->map(fn ($group) => $group->first()->toArray())
                ->toArray();

            $quizStats = QuizAttempt::whereIn('student_id', $childIds)
                ->whereIn('status', ['submitted', 'timed_out'])
                ->selectRaw('student_id, count(*) as taken, avg(percentage) as avg_pct, sum(case when passed = 1 then 1 else 0 end) as passed_count')
                ->groupBy('student_id')
                ->get()
                ->keyBy('student_id')
                ->map(fn ($row) => (array) $row->getAttributes())
                ->toArray();

            $gameStats = GamePlay::whereIn('student_id', $childIds)
                ->where('completed', true)
                ->selectRaw('student_id, count(*) as played, avg(percentage) as avg_pct')
                ->groupBy('student_id')
                ->get()
                ->keyBy('student_id')
                ->map(fn ($row) => (array) $row->getAttributes())
                ->toArray();

            $cbtStats = ExamAttempt::whereIn('exam_attempts.student_id', $childIds)
                ->whereIn('exam_attempts.status', ['submitted', 'timed_out', 'grading', 'graded'])
                ->join('exams', 'exam_attempts.exam_id', '=', 'exams.id')
                ->selectRaw('exam_attempts.student_id, exams.category, count(*) as taken, avg(exam_attempts.percentage) as avg_pct, sum(case when exam_attempts.passed = 1 then 1 else 0 end) as passed_count')
                ->groupBy('exam_attempts.student_id', 'exams.category')
                ->get()
                ->map(fn ($row) => (array) $row->getAttributes())
                ->toArray();

            return compact('resultsCounts', 'latestResults', 'quizStats', 'gameStats', 'cbtStats');
        });

        $resultsCounts = $cachedStats['resultsCounts'];   // plain array: [student_id => count]
        $latestResults = $cachedStats['latestResults'];    // plain array: [student_id => result[]]
        $quizStats = $cachedStats['quizStats'];        // plain array: [student_id => attrs[]]
        $gameStats = $cachedStats['gameStats'];        // plain array: [student_id => attrs[]]
        $cbtStatsAll = collect($cachedStats['cbtStats']); // Collection of plain arrays for filtering

        // ── Bulk per-child stats (avoid N+1) ─────────────────────────
        $childrenStats = [];
        $totalResults = 0;
        $totalQuizzesTaken = 0;
        $totalGamesPlayed = 0;
        $totalCbtExamsTaken = 0;
        $allQuizPercentages = [];

        foreach ($children as $child) {
            $cId = $child->id;
            $rCount = (int) ($resultsCounts[$cId] ?? 0);
            $qs = $quizStats[$cId] ?? null;
            $gs = $gameStats[$cId] ?? null;

            $quizzesTaken = $qs ? (int) ($qs['taken'] ?? 0) : 0;
            $quizAvg = $qs ? round((float) ($qs['avg_pct'] ?? 0), 1) : null;
            $quizPassRate = ($quizzesTaken > 0 && $qs) ? round(((int) ($qs['passed_count'] ?? 0) / $quizzesTaken) * 100) : 0;

            $gamesPlayed = $gs ? (int) ($gs['played'] ?? 0) : 0;
            $gameAvg = $gs ? round((float) ($gs['avg_pct'] ?? 0), 1) : null;

            // CBT breakdown by category
            $childCbt = $cbtStatsAll->where('student_id', $cId);
            $cbtExams = $childCbt->firstWhere('category', 'exam');
            $cbtAssessments = $childCbt->firstWhere('category', 'assessment');
            $cbtAssignments = $childCbt->firstWhere('category', 'assignment');
            $cbtTotalTaken = $childCbt->sum('taken');
            $cbtAllAvgs = $childCbt->pluck('avg_pct')->filter();
            $cbtOverallAvg = $cbtAllAvgs->isNotEmpty() ? round($cbtAllAvgs->avg(), 1) : null;

            $childrenStats[$cId] = [
                'results_count' => $rCount,
                'latest_result' => $latestResults[$cId] ?? null,
                'quizzes_taken' => $quizzesTaken,
                'quiz_avg' => $quizAvg,
                'quiz_pass_rate' => $quizPassRate,
                'games_played' => $gamesPlayed,
                'game_avg' => $gameAvg,
                'cbt_exams' => $cbtExams ? (int) ($cbtExams['taken'] ?? 0) : 0,
                'cbt_assessments' => $cbtAssessments ? (int) ($cbtAssessments['taken'] ?? 0) : 0,
                'cbt_assignments' => $cbtAssignments ? (int) ($cbtAssignments['taken'] ?? 0) : 0,
                'cbt_taken' => (int) $cbtTotalTaken,
                'cbt_avg' => $cbtOverallAvg,
            ];

            $totalResults += $rCount;
            $totalQuizzesTaken += $quizzesTaken;
            $totalGamesPlayed += $gamesPlayed;
            $totalCbtExamsTaken += (int) $cbtTotalTaken;
            if ($quizAvg !== null) {
                $allQuizPercentages[] = (float) $quizAvg;
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
            'totalCbtExamsTaken',
            'recentNotices',
        ));
    }
}
