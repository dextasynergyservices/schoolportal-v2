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

        // ── CBT items: ongoing (open now) + upcoming (not yet open) ──
        $cbtItems = collect();
        if ($classId) {
            $cols = ['id', 'title', 'category', 'subject_id', 'total_questions', 'time_limit_minutes', 'available_from', 'available_until', 'max_attempts', 'school_id', 'class_id', 'is_published'];

            // Ongoing: published, window is open right now
            $ongoing = Exam::published()
                ->forClass($classId)
                ->where(function ($q) {
                    $q->whereNull('available_from')->orWhere('available_from', '<=', now());
                })
                ->where(function ($q) {
                    $q->whereNull('available_until')->orWhere('available_until', '>=', now());
                })
                ->with('subject:id,name')
                ->orderBy('available_until')
                ->take(4)
                ->get($cols);

            // Upcoming: published, window hasn't opened yet
            $upcoming = Exam::upcoming()
                ->forClass($classId)
                ->with('subject:id,name')
                ->orderBy('available_from')
                ->take(3)
                ->get($cols);

            // Bulk-load this student's completed attempt counts for these exams
            $allIds = $ongoing->pluck('id')->merge($upcoming->pluck('id'))->unique();
            $takenCounts = ExamAttempt::where('student_id', $student->id)
                ->whereIn('exam_id', $allIds)
                ->whereIn('status', ['submitted', 'timed_out', 'grading', 'graded'])
                ->selectRaw('exam_id, COUNT(*) as cnt')
                ->groupBy('exam_id')
                ->pluck('cnt', 'exam_id');

            foreach ($ongoing as $exam) {
                $done = (int) ($takenCounts[$exam->id] ?? 0);
                $exam->_status = 'ongoing';
                $exam->_attempts_done = $done;
                $exam->_taken = $done >= $exam->max_attempts;
            }
            foreach ($upcoming as $exam) {
                $exam->_status = 'upcoming';
                $exam->_attempts_done = 0;
                $exam->_taken = false;
            }

            $cbtItems = $ongoing->merge($upcoming);
        }

        // Keep $upcomingExams as alias for backwards-compat with calendar helper
        $upcomingExams = $cbtItems;

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

        // ── Progress Timeline ─────────────────────────────────────
        $timelineData = $this->buildTimelineData($student->id, $school->id, $currentSession, $currentTerm);

        // ── Study Calendar events (next 60 days) ─────────────────────
        $calendarEvents = $this->buildCalendarEvents($student->id, $classId, $currentSession, $currentTerm);

        return view('student.dashboard', compact(
            'student', 'school', 'profile', 'class',
            'currentSession', 'currentTerm',
            'resultsCount', 'reportCardsCount', 'cbtResultsCount', 'assignmentsCount', 'noticesCount',
            'availableQuizzes', 'availableGames',
            'availableExamsCount', 'availableAssessmentsCount', 'availableCbtAssignmentsCount',
            'quizzesTaken', 'quizAvgScore', 'quizPassRate',
            'examsTaken', 'examAvgScore', 'examPassRate',
            'upcomingExams',
            'cbtItems',
            'learningItems', 'totalPublishedQuizzes', 'quizzesCompletedCount',
            'totalPublishedGames', 'gamesCompletedCount',
            'upcomingDeadlines',
            'recentResults', 'recentAssignments', 'recentNotices',
            'timelineData', 'calendarEvents',
        ));
    }

    /**
     * Build the academic progress timeline data from published term reports.
     * Returns sessions ordered oldest → newest, each with terms and average score.
     */
    private function buildTimelineData(int $studentId, int $schoolId, mixed $currentSession, mixed $currentTerm): array
    {
        $reports = StudentTermReport::where('student_id', $studentId)
            ->where('school_id', $schoolId)
            ->where('status', 'published')
            ->whereNotNull('average_weighted_score')
            ->with(['session:id,name', 'term:id,term_number,name'])
            ->orderBy('session_id')
            ->orderBy('term_id')
            ->get(['id', 'student_id', 'school_id', 'session_id', 'term_id', 'average_weighted_score']);

        if ($reports->isEmpty()) {
            return [];
        }

        $grouped = [];
        foreach ($reports as $report) {
            $sessionName = $report->session?->name ?? '?';
            $termNumber = $report->term?->term_number ?? 1;
            $isCurrentTerm = $currentSession && $currentTerm
                && $report->session_id === $currentSession->id
                && $report->term_id === $currentTerm->id;

            if (! isset($grouped[$sessionName])) {
                $grouped[$sessionName] = ['session' => $sessionName, 'terms' => []];
            }
            $grouped[$sessionName]['terms'][] = [
                'label' => 'T'.$termNumber,
                'label_full' => $report->term?->name ?? ('Term '.$termNumber),
                'score' => (int) round((float) $report->average_weighted_score),
                'current' => $isCurrentTerm,
            ];
        }

        return array_values($grouped);
    }

    /**
     * Build calendar event array for upcoming exams, quizzes, and assignment deadlines.
     * Only events within the next 60 days from today.
     */
    private function buildCalendarEvents(int $studentId, ?int $classId, mixed $currentSession, mixed $currentTerm): array
    {
        $events = [];
        $horizon = now()->addDays(60);
        $today = now()->startOfDay();

        if ($classId) {
            // CBT exams / assessments / cbt-assignments with available_until
            $exams = Exam::published()
                ->forClass($classId)
                ->where(function ($q) use ($today) {
                    $q->whereNull('available_from')->orWhere('available_from', '<=', $today->copy()->endOfDay());
                })
                ->whereNotNull('available_until')
                ->where('available_until', '>=', $today)
                ->where('available_until', '<=', $horizon)
                ->get(['id', 'title', 'category', 'available_until', 'max_attempts', 'school_id', 'class_id', 'is_published', 'available_from']);

            // Check which exams the student has already taken
            $calExamIds = $exams->pluck('id');
            $calTakenCounts = ExamAttempt::where('student_id', $studentId)
                ->whereIn('exam_id', $calExamIds)
                ->whereIn('status', ['submitted', 'timed_out', 'grading', 'graded'])
                ->selectRaw('exam_id, COUNT(*) as cnt')
                ->groupBy('exam_id')
                ->pluck('cnt', 'exam_id');

            foreach ($exams as $exam) {
                $done = (int) ($calTakenCounts[$exam->id] ?? 0);
                $taken = $done >= $exam->max_attempts;
                $events[] = [
                    'date' => $exam->available_until->format('Y-m-d'),
                    'type' => in_array($exam->category, ['exam', 'assessment'], true) ? $exam->category : 'assignment',
                    'title' => $exam->title,
                    'route' => route('student.exams.show', $exam),
                    'urgent' => $exam->available_until->isToday(),
                    'taken' => $taken,
                ];
            }

            // Quizzes with expires_at
            $quizzes = Quiz::published()
                ->where('class_id', $classId)
                ->whereNotNull('expires_at')
                ->where('expires_at', '>=', $today)
                ->where('expires_at', '<=', $horizon)
                ->get(['id', 'title', 'expires_at', 'school_id', 'class_id', 'is_published']);

            foreach ($quizzes as $quiz) {
                $events[] = [
                    'date' => $quiz->expires_at->format('Y-m-d'),
                    'type' => 'quiz',
                    'title' => $quiz->title,
                    'route' => route('student.quizzes.index'),
                    'urgent' => $quiz->expires_at->isToday(),
                    'taken' => false,
                ];
            }

            // Assignment due dates
            if ($currentSession && $currentTerm) {
                $assignments = Assignment::where('class_id', $classId)
                    ->where('session_id', $currentSession->id)
                    ->where('term_id', $currentTerm->id)
                    ->where('status', 'approved')
                    ->whereNotNull('due_date')
                    ->where('due_date', '>=', $today)
                    ->where('due_date', '<=', $horizon)
                    ->get(['id', 'title', 'week_number', 'due_date']);

                foreach ($assignments as $assignment) {
                    $events[] = [
                        'date' => $assignment->due_date->format('Y-m-d'),
                        'type' => 'assignment',
                        'title' => $assignment->title ?? __('Week :week Assignment', ['week' => $assignment->week_number]),
                        'route' => route('student.assignments.index'),
                        'urgent' => $assignment->due_date->isToday(),
                        'taken' => false,
                    ];
                }
            }
        }

        usort($events, static fn ($a, $b) => strcmp($a['date'], $b['date']));

        return $events;
    }
}
