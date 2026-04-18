<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Game;
use App\Models\Notice;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Result;
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

        // ── Core counts ──────────────────────────────────────────────
        $resultsCount = Result::where('student_id', $student->id)
            ->where('status', 'approved')
            ->count();

        $assignmentsCount = 0;
        if ($classId && $currentSession && $currentTerm) {
            $assignmentsCount = Assignment::where('class_id', $classId)
                ->where('session_id', $currentSession->id)
                ->where('term_id', $currentTerm->id)
                ->where('status', 'approved')
                ->count();
        }

        // ── Quizzes & Games counts ───────────────────────────────────
        $availableQuizzes = 0;
        $availableGames = 0;
        if ($classId) {
            $availableQuizzes = Quiz::published()
                ->where('class_id', $classId)
                ->count();

            $availableGames = Game::published()
                ->where('class_id', $classId)
                ->count();
        }

        // ── Quiz performance summary ─────────────────────────────────
        $quizzesTaken = QuizAttempt::where('student_id', $student->id)
            ->whereIn('status', ['submitted', 'timed_out'])
            ->count();

        $quizAvgScore = QuizAttempt::where('student_id', $student->id)
            ->whereIn('status', ['submitted', 'timed_out'])
            ->avg('percentage');

        $quizPassRate = 0;
        if ($quizzesTaken > 0) {
            $quizPassed = QuizAttempt::where('student_id', $student->id)
                ->whereIn('status', ['submitted', 'timed_out'])
                ->where('passed', true)
                ->count();
            $quizPassRate = round(($quizPassed / $quizzesTaken) * 100);
        }

        // ── Available quizzes preview (next 3) ───────────────────────
        $upcomingQuizzes = collect();
        if ($classId) {
            $upcomingQuizzes = Quiz::published()
                ->where('class_id', $classId)
                ->with('session:id,name')
                ->latest('published_at')
                ->take(3)
                ->get(['id', 'title', 'total_questions', 'time_limit_minutes', 'max_attempts', 'expires_at', 'session_id', 'class_id', 'school_id', 'is_published']);
        }

        // ── Available games preview (next 3) ─────────────────────────
        $upcomingGames = collect();
        if ($classId) {
            $upcomingGames = Game::published()
                ->where('class_id', $classId)
                ->latest('published_at')
                ->take(3)
                ->get(['id', 'title', 'game_type', 'difficulty', 'school_id', 'class_id', 'is_published', 'expires_at']);
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
            'resultsCount', 'assignmentsCount', 'noticesCount',
            'availableQuizzes', 'availableGames',
            'quizzesTaken', 'quizAvgScore', 'quizPassRate',
            'upcomingQuizzes', 'upcomingGames', 'upcomingDeadlines',
            'recentResults', 'recentAssignments', 'recentNotices',
        ));
    }
}
