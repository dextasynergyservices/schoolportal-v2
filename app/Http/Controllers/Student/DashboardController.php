<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Game;
use App\Models\GamePlay;
use App\Models\Notice;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Result;
use App\Models\StudentTermReport;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $student = auth()->user();
        $school = app('current.school');
        $profile = $student->studentProfile;
        $classId = $profile?->class_id;

        $currentSession = $school->currentSession();
        $currentTerm = $school->currentTerm();

        // Load class with teacher info
        $class = $profile?->class()->with(['teacher:id,name', 'level:id,name'])->first();

        // ── Core counts (cached 5 min) ───────────────────────────────
        $statsCacheKey = "school:{$school->id}:student:{$student->id}:dashboard:stats";
        $stats = Cache::remember($statsCacheKey, now()->addMinutes(5), function () use ($student, $classId, $currentSession, $currentTerm): array {
            $resultsCount = Result::where('student_id', $student->id)->where('status', 'approved')->count();
            $reportCardsCount = StudentTermReport::where('student_id', $student->id)->where('status', 'published')->count();
            $cbtResultsCount = ExamAttempt::where('student_id', $student->id)->whereIn('status', ['submitted', 'timed_out', 'grading', 'graded'])->count();

            $assignmentsCount = 0;
            if ($classId && $currentSession && $currentTerm) {
                $assignmentsCount = Assignment::where('class_id', $classId)
                    ->where('session_id', $currentSession->id)
                    ->where('term_id', $currentTerm->id)
                    ->where('status', 'approved')
                    ->count();
            }

            $quizzesTaken = QuizAttempt::where('student_id', $student->id)->whereIn('status', ['submitted', 'timed_out'])->count();
            $quizAvgScore = QuizAttempt::where('student_id', $student->id)->whereIn('status', ['submitted', 'timed_out'])->avg('percentage');
            $quizPassRate = 0;
            if ($quizzesTaken > 0) {
                $quizPassed = QuizAttempt::where('student_id', $student->id)->whereIn('status', ['submitted', 'timed_out'])->where('passed', true)->count();
                $quizPassRate = round(($quizPassed / $quizzesTaken) * 100);
            }

            $examsTaken = ExamAttempt::where('student_id', $student->id)->whereIn('status', ['submitted', 'timed_out', 'grading'])->count();
            $examAvgScore = ExamAttempt::where('student_id', $student->id)->whereIn('status', ['submitted', 'timed_out'])->whereNotNull('percentage')->avg('percentage');
            $examPassRate = 0;
            if ($examsTaken > 0) {
                $examPassed = ExamAttempt::where('student_id', $student->id)->whereIn('status', ['submitted', 'timed_out'])->where('passed', true)->count();
                $examPassRate = round(($examPassed / $examsTaken) * 100);
            }

            return compact('resultsCount', 'reportCardsCount', 'cbtResultsCount', 'assignmentsCount', 'quizzesTaken', 'quizAvgScore', 'quizPassRate', 'examsTaken', 'examAvgScore', 'examPassRate');
        });

        $resultsCount = $stats['resultsCount'];
        $reportCardsCount = $stats['reportCardsCount'];
        $cbtResultsCount = $stats['cbtResultsCount'];
        $assignmentsCount = $stats['assignmentsCount'];
        $quizzesTaken = $stats['quizzesTaken'];
        $quizAvgScore = $stats['quizAvgScore'];
        $quizPassRate = $stats['quizPassRate'];
        $examsTaken = $stats['examsTaken'];
        $examAvgScore = $stats['examAvgScore'];
        $examPassRate = $stats['examPassRate'];

        // ── Quizzes & Games counts ───────────────────────────────────
        $availableQuizzes = 0;
        $availableGames = 0;
        $availableExamsCount = 0;
        $availableAssessmentsCount = 0;
        $availableCbtAssignmentsCount = 0;
        if ($classId) {
            $availableQuizzes = Quiz::published()
                ->where('class_id', $classId)
                ->count();

            $availableGames = Game::published()
                ->where('class_id', $classId)
                ->count();

            $availableExamsCount = Exam::available()
                ->forClass($classId)
                ->forCategory('exam')
                ->count();

            $availableAssessmentsCount = Exam::available()
                ->forClass($classId)
                ->forCategory('assessment')
                ->count();

            $availableCbtAssignmentsCount = Exam::available()
                ->forClass($classId)
                ->forCategory('assignment')
                ->count();
        }

        // ── Upcoming CBT exams (nearest deadlines) ──────────────
        $upcomingExams = collect();
        if ($classId) {
            $upcomingExams = Exam::available()
                ->forClass($classId)
                ->with('subject:id,name')
                ->whereNotNull('available_until')
                ->orderBy('available_until')
                ->take(5)
                ->get(['id', 'title', 'category', 'subject_id', 'total_questions', 'time_limit_minutes', 'available_until', 'max_attempts', 'school_id', 'class_id', 'is_published', 'available_from']);
        }

        // ── My Learning: unified quiz + game items ────────────────
        $learningItems = collect();
        $totalPublishedQuizzes = 0;
        $quizzesCompletedCount = 0;
        $totalPublishedGames = 0;
        $gamesCompletedCount = 0;

        if ($classId) {
            // Quizzes with attempt info
            $quizzes = Quiz::published()
                ->where('class_id', $classId)
                ->latest('published_at')
                ->take(10)
                ->get(['id', 'title', 'total_questions', 'time_limit_minutes', 'max_attempts', 'expires_at', 'published_at', 'session_id', 'class_id', 'school_id', 'is_published']);

            foreach ($quizzes as $quiz) {
                $attemptsUsed = QuizAttempt::where('quiz_id', $quiz->id)
                    ->where('student_id', $student->id)
                    ->whereIn('status', ['submitted', 'timed_out'])
                    ->count();

                $learningItems->push((object) [
                    'type' => 'quiz',
                    'id' => $quiz->id,
                    'title' => $quiz->title,
                    'meta' => $quiz->total_questions.' '.__('questions'),
                    'time_limit' => $quiz->time_limit_minutes,
                    'expires_at' => $quiz->expires_at,
                    'published_at' => $quiz->published_at,
                    'route' => route('student.quizzes.index'),
                    'btn_label' => $attemptsUsed > 0 ? __('Continue') : __('Start'),
                    'completed' => $attemptsUsed >= $quiz->max_attempts,
                    'attempts_label' => $attemptsUsed.'/'.$quiz->max_attempts,
                ]);
            }

            // Games with play info
            $games = Game::published()
                ->where('class_id', $classId)
                ->latest('published_at')
                ->take(10)
                ->get(['id', 'title', 'game_type', 'difficulty', 'school_id', 'class_id', 'is_published', 'expires_at', 'published_at']);

            foreach ($games as $game) {
                $played = GamePlay::where('game_id', $game->id)
                    ->where('student_id', $student->id)
                    ->where('completed', true)
                    ->exists();

                $learningItems->push((object) [
                    'type' => 'game',
                    'id' => $game->id,
                    'title' => $game->title,
                    'meta' => $game->gameTypeLabel(),
                    'difficulty' => $game->difficulty,
                    'expires_at' => $game->expires_at,
                    'published_at' => $game->published_at,
                    'route' => route('student.games.play', $game),
                    'btn_label' => $played ? __('Play Again') : __('Play'),
                    'completed' => $played,
                ]);
            }

            // Sort: items with deadlines first (nearest), then newest published
            $learningItems = $learningItems->sort(function ($a, $b) {
                $aHas = $a->expires_at !== null;
                $bHas = $b->expires_at !== null;
                if ($aHas && ! $bHas) {
                    return -1;
                }
                if (! $aHas && $bHas) {
                    return 1;
                }
                if ($aHas && $bHas) {
                    return $a->expires_at->timestamp - $b->expires_at->timestamp;
                }

                return ($b->published_at?->timestamp ?? 0) - ($a->published_at?->timestamp ?? 0);
            })->take(6)->values();

            // Progress ring data
            $totalPublishedQuizzes = Quiz::published()->where('class_id', $classId)->count();
            $quizzesCompletedCount = Quiz::published()
                ->where('class_id', $classId)
                ->whereHas('attempts', fn ($q) => $q->where('student_id', $student->id)
                    ->whereIn('status', ['submitted', 'timed_out']))
                ->count();

            $totalPublishedGames = Game::published()->where('class_id', $classId)->count();
            $gamesCompletedCount = Game::published()
                ->where('class_id', $classId)
                ->whereHas('plays', fn ($q) => $q->where('student_id', $student->id)
                    ->where('completed', true))
                ->count();
        }

        // ── Upcoming assignment deadlines ────────────────────────────
        $upcomingDeadlines = collect();
        if ($classId && $currentSession && $currentTerm) {
            $upcomingDeadlines = Assignment::where('class_id', $classId)
                ->where('session_id', $currentSession->id)
                ->where('term_id', $currentTerm->id)
                ->where('status', 'approved')
                ->whereNotNull('due_date')
                ->where('due_date', '>=', now())
                ->orderBy('due_date')
                ->take(5)
                ->get(['id', 'title', 'week_number', 'due_date', 'file_url']);
        }

        // ── Recent results (latest 5) ────────────────────────────────
        $recentResults = Result::where('student_id', $student->id)
            ->where('status', 'approved')
            ->with(['session:id,name', 'term:id,name'])
            ->latest()
            ->take(5)
            ->get();

        // ── Recent assignments ───────────────────────────────────────
        $recentAssignments = collect();
        if ($classId && $currentSession && $currentTerm) {
            $recentAssignments = Assignment::where('class_id', $classId)
                ->where('session_id', $currentSession->id)
                ->where('term_id', $currentTerm->id)
                ->where('status', 'approved')
                ->latest()
                ->take(5)
                ->get();
        }

        // ── Notices ──────────────────────────────────────────────────
        $noticeQuery = Notice::where('is_published', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            })
            ->where(function ($q) {
                $q->whereNull('target_roles')
                    ->orWhereJsonContains('target_roles', 'student');
            })
            ->where(function ($q) use ($profile) {
                $q->whereNull('target_levels');
                if ($profile?->class?->level_id) {
                    $q->orWhereJsonContains('target_levels', $profile->class->level_id);
                }
            });

        $noticesCount = $noticeQuery->count();

        $recentNotices = (clone $noticeQuery)
            ->latest('published_at')
            ->take(3)
            ->get();

        return view('student.dashboard', compact(
            'student', 'school', 'profile', 'class',
            'currentSession', 'currentTerm',
            'resultsCount', 'reportCardsCount', 'cbtResultsCount', 'assignmentsCount', 'noticesCount',
            'availableQuizzes', 'availableGames',
            'availableExamsCount', 'availableAssessmentsCount', 'availableCbtAssignmentsCount',
            'quizzesTaken', 'quizAvgScore', 'quizPassRate',
            'examsTaken', 'examAvgScore', 'examPassRate',
            'upcomingExams',
            'learningItems', 'totalPublishedQuizzes', 'quizzesCompletedCount',
            'totalPublishedGames', 'gamesCompletedCount',
            'upcomingDeadlines',
            'recentResults', 'recentAssignments', 'recentNotices',
        ));
    }
}
