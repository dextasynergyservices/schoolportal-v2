<?php

declare(strict_types=1);

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\AiCreditAllocation;
use App\Models\Assignment;
use App\Models\Game;
use App\Models\Notice;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Result;
use App\Models\TeacherAction;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $teacher = auth()->user();
        $school = app('current.school');
        $classIds = $teacher->assignedClasses()->pluck('id');

        $currentSession = $school->currentSession();
        $currentTerm = $school->currentTerm();

        // ── Assigned classes with student counts ─────────────────────
        $assignedClasses = $teacher->assignedClasses()
            ->where('is_active', true)
            ->withCount('students')
            ->with('level:id,name')
            ->orderBy('name')
            ->get();

        $totalStudents = $assignedClasses->sum('students_count');

        // ── Submission stats ─────────────────────────────────────────
        $totalResults = Result::where('uploaded_by', $teacher->id)->count();
        $totalAssignments = Assignment::where('uploaded_by', $teacher->id)->count();
        $totalNotices = Notice::where('created_by', $teacher->id)->count();

        $pendingCount = TeacherAction::where('teacher_id', $teacher->id)
            ->where('status', 'pending')
            ->count();

        // ── Rejected submissions alert ───────────────────────────────
        $rejectedSubmissions = TeacherAction::where('teacher_id', $teacher->id)
            ->where('status', 'rejected')
            ->latest('created_at')
            ->take(3)
            ->get(['id', 'action_type', 'entity_type', 'rejection_reason', 'created_at']);

        // ── Recent submissions with status ───────────────────────────
        $recentSubmissions = TeacherAction::where('teacher_id', $teacher->id)
            ->with('reviewer:id,name')
            ->latest('created_at')
            ->take(10)
            ->get();

        // ── Results upload progress per class (current term) ─────────
        $resultsProgress = collect();
        if ($currentSession && $currentTerm && $classIds->isNotEmpty()) {
            $resultsByClass = Result::whereIn('class_id', $classIds)
                ->where('session_id', $currentSession->id)
                ->where('term_id', $currentTerm->id)
                ->where('status', 'approved')
                ->selectRaw('class_id, count(*) as uploaded')
                ->groupBy('class_id')
                ->pluck('uploaded', 'class_id');

            $resultsProgress = $assignedClasses->map(fn ($cls) => [
                'name' => $cls->name,
                'level' => $cls->level?->name,
                'uploaded' => $resultsByClass[$cls->id] ?? 0,
                'total' => $cls->students_count,
            ]);
        }

        // ── Assignments coverage per class (current term) ────────────
        $weeksPerTerm = $school->setting('academic.weeks_per_term', 12);
        $assignmentsCoverage = collect();
        if ($currentSession && $currentTerm && $classIds->isNotEmpty()) {
            $assignmentsByClass = Assignment::whereIn('class_id', $classIds)
                ->where('session_id', $currentSession->id)
                ->where('term_id', $currentTerm->id)
                ->where('status', 'approved')
                ->selectRaw('class_id, count(*) as uploaded_weeks')
                ->groupBy('class_id')
                ->pluck('uploaded_weeks', 'class_id');

            $assignmentsCoverage = $assignedClasses->map(fn ($cls) => [
                'name' => $cls->name,
                'uploaded' => $assignmentsByClass[$cls->id] ?? 0,
                'total' => $weeksPerTerm,
            ]);
        }

        // ── Quiz & Game stats ────────────────────────────────────────
        $publishedQuizzes = Quiz::whereIn('class_id', $classIds)
            ->where('created_by', $teacher->id)
            ->where('is_published', true)
            ->count();

        $publishedGames = Game::whereIn('class_id', $classIds)
            ->where('created_by', $teacher->id)
            ->where('is_published', true)
            ->count();

        $quizAttempts = QuizAttempt::whereHas('quiz', fn ($q) => $q->whereIn('class_id', $classIds)->where('created_by', $teacher->id))
            ->whereIn('status', ['submitted', 'timed_out'])
            ->count();

        $avgQuizScore = QuizAttempt::whereHas('quiz', fn ($q) => $q->whereIn('class_id', $classIds)->where('created_by', $teacher->id))
            ->whereIn('status', ['submitted', 'timed_out'])
            ->avg('percentage');

        // ── AI credits remaining (level allocation or school pool) ───
        $aiCreditsRemaining = $school->aiCreditsBalance();
        $aiCreditsLabel = __('School pool');
        if ($teacher->level_id) {
            $allocation = AiCreditAllocation::where('level_id', $teacher->level_id)->first();
            if ($allocation) {
                $aiCreditsRemaining = $allocation->remainingCredits();
                $aiCreditsLabel = $allocation->level?->name ?? __('Your level');
            }
        }

        // ── Upcoming due dates (assignments with future due_date) ────
        $upcomingDeadlines = collect();
        if ($currentSession && $currentTerm && $classIds->isNotEmpty()) {
            $upcomingDeadlines = Assignment::whereIn('class_id', $classIds)
                ->where('session_id', $currentSession->id)
                ->where('term_id', $currentTerm->id)
                ->where('status', 'approved')
                ->whereNotNull('due_date')
                ->where('due_date', '>=', now())
                ->with('class:id,name')
                ->orderBy('due_date')
                ->take(5)
                ->get(['id', 'title', 'week_number', 'class_id', 'due_date']);
        }

        return view('teacher.dashboard', compact(
            'teacher', 'currentSession', 'currentTerm',
            'assignedClasses', 'totalStudents',
            'totalResults', 'totalAssignments', 'totalNotices', 'pendingCount',
            'rejectedSubmissions', 'recentSubmissions',
            'resultsProgress', 'assignmentsCoverage', 'weeksPerTerm',
            'publishedQuizzes', 'publishedGames', 'quizAttempts', 'avgQuizScore',
            'aiCreditsRemaining', 'aiCreditsLabel',
            'upcomingDeadlines',
        ));
    }
}
