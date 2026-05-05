<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Jobs\GradeExamAttempt;
use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Services\ExamGradingService;
use App\Services\ScoreAggregationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ExamController extends Controller
{
    public function __construct(
        private readonly ExamGradingService $gradingService,
        private readonly ScoreAggregationService $scoreService,
    ) {}

    /**
     * List available exams for the student, filtered by category.
     */
    public function index(Request $request): View
    {
        $student = auth()->user();
        $classId = $student->studentProfile?->class_id;
        $category = $this->resolveCategory();

        $exams = Exam::with(['subject:id,name,short_name', 'class:id,name', 'attempts' => fn ($q) => $q->where('student_id', $student->id)])
            ->available()
            ->forClass($classId);

        // When category is null (unified view), show all; otherwise filter
        if ($category !== null) {
            $exams->forCategory($category);
        }

        $exams = $exams->orderByDesc('published_at')
            ->paginate(12);

        // Upcoming exams (published but not yet open)
        $upcomingQuery = Exam::with(['subject:id,name,short_name', 'class:id,name'])
            ->upcoming()
            ->forClass($classId);

        if ($category !== null) {
            $upcomingQuery->forCategory($category);
        }

        $upcoming = $upcomingQuery->orderBy('available_from')
            ->get();

        // Closed exams (deadline has passed) — show results or "Missed" badge
        $closedQuery = Exam::with(['subject:id,name,short_name', 'attempts' => fn ($q) => $q->where('student_id', $student->id)])
            ->closed()
            ->forClass($classId);

        if ($category !== null) {
            $closedQuery->forCategory($category);
        }

        $closed = $closedQuery->orderByDesc('available_until')
            ->take(20)
            ->get();

        return view('student.exams.index', [
            'exams' => $exams,
            'upcoming' => $upcoming,
            'closed' => $closed,
            'category' => $category,
            'label' => $this->categoryLabel($category),
            'routePrefix' => $this->routePrefix(),
            'studentId' => $student->id,
        ]);
    }

    /**
     * Pre-exam screen: show instructions, rules, info.
     */
    public function show(Exam $exam): View|RedirectResponse
    {
        $student = auth()->user();
        $classId = $student->studentProfile?->class_id;

        if ($exam->class_id !== $classId || ! $exam->is_published) {
            abort(403);
        }

        $exam->load(['subject:id,name', 'class:id,name', 'questions']);

        $completedAttempts = $exam->completedAttemptsFor($student->id);
        $latestAttempt = $exam->latestAttemptFor($student->id);
        $canAttempt = $exam->canStudentAttempt($student->id);

        // If there's an in-progress attempt, offer to resume
        $inProgressAttempt = $exam->attemptsFor($student->id)
            ->where('status', 'in_progress')
            ->first();

        $questionTypeCounts = $exam->questions->groupBy('type')->map->count();
        $totalPoints = $exam->questions->sum('points');
        $hasTheoryQuestions = $exam->questions->contains(fn ($q) => in_array($q->type, ['theory', 'short_answer']));

        $previousAttempts = $exam->attemptsFor($student->id)
            ->whereIn('status', ['submitted', 'timed_out', 'grading'])
            ->orderBy('attempt_number')
            ->get();

        return view('student.exams.show', [
            'exam' => $exam,
            'category' => $this->resolveCategory(),
            'label' => $this->categoryLabel($this->resolveCategory()),
            'routePrefix' => $this->routePrefix(),
            'completedAttempts' => $completedAttempts,
            'latestAttempt' => $latestAttempt,
            'canAttempt' => $canAttempt,
            'inProgressAttempt' => $inProgressAttempt,
            'questionTypeCounts' => $questionTypeCounts,
            'totalPoints' => $totalPoints,
            'hasTheoryQuestions' => $hasTheoryQuestions,
            'previousAttempts' => $previousAttempts,
        ]);
    }

    /**
     * Start a new attempt or resume an existing one.
     */
    public function start(Exam $exam): RedirectResponse
    {
        $student = auth()->user();
        $classId = $student->studentProfile?->class_id;

        if ($exam->class_id !== $classId || ! $exam->canStudentAttempt($student->id)) {
            abort(403, 'You cannot take this exam.');
        }

        // Check for in-progress attempt first
        $existingAttempt = $exam->attemptsFor($student->id)
            ->where('status', 'in_progress')
            ->first();

        if ($existingAttempt) {
            // Check timeout
            if ($existingAttempt->hasTimedOut()) {
                $this->autoSubmit($existingAttempt);

                return redirect()->route($this->routePrefix().'.results', $existingAttempt);
            }

            return redirect()->route($this->routePrefix().'.take', $existingAttempt);
        }

        $attemptNumber = $exam->completedAttemptsFor($student->id) + 1;

        $attempt = DB::transaction(function () use ($exam, $student, $attemptNumber) {
            $attempt = ExamAttempt::create([
                'exam_id' => $exam->id,
                'student_id' => $student->id,
                'school_id' => $student->school_id,
                'attempt_number' => $attemptNumber,
                'started_at' => now(),
                'status' => 'in_progress',
                'ip_address' => request()->ip(),
            ]);

            // Pre-create answer slots for all questions
            $questions = $exam->questions()->get();
            foreach ($questions as $question) {
                ExamAnswer::create([
                    'attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                    'school_id' => $student->school_id,
                ]);
            }

            return $attempt;
        });

        return redirect()->route($this->routePrefix().'.take', $attempt);
    }

    /**
     * The exam-taking interface.
     */
    public function take(ExamAttempt $attempt): View|RedirectResponse
    {
        $student = auth()->user();

        if ($attempt->student_id !== $student->id) {
            abort(403);
        }

        if (! $attempt->isInProgress()) {
            return redirect()->route($this->routePrefix().'.results', $attempt);
        }

        // IP session lock — prevent resuming from a different IP (e.g. shared device hand-off)
        if ($attempt->ip_address && $attempt->ip_address !== request()->ip()) {
            return redirect()->route($this->routePrefix().'.index')
                ->with('error', __('This exam session is locked to another device. Please contact your teacher.'));
        }

        // Check timeout
        if ($attempt->hasTimedOut()) {
            $this->autoSubmit($attempt);

            return redirect()->route($this->routePrefix().'.results', $attempt);
        }

        $exam = $attempt->exam;
        $exam->load('questions');

        // Shuffle questions with a stable seed per attempt (consistent on refresh)
        if ($exam->shuffle_questions) {
            $seed = crc32((string) $attempt->id);
            $questions = $exam->questions->shuffle($seed);
        } else {
            $questions = $exam->questions;
        }

        // Get saved answers keyed by question_id
        $answers = ExamAnswer::where('attempt_id', $attempt->id)
            ->get()
            ->keyBy('question_id');

        $remainingSeconds = $attempt->remainingSeconds();

        // Transform saved answers to {question_id: selected_answer}
        $answersForJs = $answers->mapWithKeys(fn ($a) => [$a->question_id => $a->selected_answer])->all();

        return view('student.exams.take', [
            'exam' => $exam,
            'attempt' => $attempt,
            'questions' => $questions->values(),
            'answers' => $answersForJs,
            'remainingSeconds' => $remainingSeconds,
            'category' => $this->resolveCategory(),
            'label' => $this->categoryLabel($this->resolveCategory()),
            'routePrefix' => $this->routePrefix(),
        ]);
    }

    /**
     * Save a single answer (AJAX-friendly).
     */
    public function saveAnswer(Request $request, ExamAttempt $attempt): RedirectResponse|JsonResponse
    {
        $student = auth()->user();

        if ($attempt->student_id !== $student->id || ! $attempt->isInProgress()) {
            abort(403);
        }

        if ($attempt->hasTimedOut()) {
            $this->autoSubmit($attempt);

            if ($request->wantsJson()) {
                return response()->json(['status' => 'timed_out', 'redirect' => route($this->routePrefix().'.results', $attempt)]);
            }

            return redirect()->route($this->routePrefix().'.results', $attempt);
        }

        $validated = $request->validate([
            'question_id' => ['required', 'integer', 'exists:exam_questions,id'],
            'selected_answer' => ['nullable', 'string', 'max:10000'],
        ]);

        $question = $attempt->exam->questions()->find($validated['question_id']);
        $isTheory = $question && $question->isTheory();

        ExamAnswer::where('attempt_id', $attempt->id)
            ->where('question_id', $validated['question_id'])
            ->update([
                'selected_answer' => $isTheory ? null : $validated['selected_answer'],
                'theory_answer' => $isTheory ? $validated['selected_answer'] : null,
                'answered_at' => now(),
            ]);

        if ($request->wantsJson()) {
            return response()->json(['status' => 'saved']);
        }

        return redirect()->back();
    }

    /**
     * Record a tab switch event.
     */
    public function tabSwitch(Request $request, ExamAttempt $attempt): JsonResponse
    {
        $student = auth()->user();

        if ($attempt->student_id !== $student->id || ! $attempt->isInProgress()) {
            return response()->json(['status' => 'invalid'], 403);
        }

        $attempt->increment('tab_switches');

        $maxSwitches = $attempt->exam->max_tab_switches;
        $exceeded = $maxSwitches > 0 && $attempt->tab_switches >= $maxSwitches;

        if ($exceeded) {
            $this->autoSubmit($attempt);

            return response()->json([
                'status' => 'auto_submitted',
                'redirect' => route($this->routePrefix().'.results', $attempt),
                'message' => 'Your exam was auto-submitted due to exceeding the maximum number of tab switches.',
            ]);
        }

        return response()->json([
            'status' => 'recorded',
            'tab_switches' => $attempt->tab_switches,
            'max_tab_switches' => $maxSwitches,
            'remaining' => max(0, $maxSwitches - $attempt->tab_switches),
        ]);
    }

    /**
     * Submit the exam.
     */
    public function submit(Request $request, ExamAttempt $attempt): RedirectResponse
    {
        $student = auth()->user();

        if ($attempt->student_id !== $student->id || ! $attempt->isInProgress()) {
            abort(403);
        }

        // Save any final answers bundled with submit
        $answers = $request->input('answers', []);
        if (is_array($answers)) {
            $validQuestions = $attempt->exam->questions()->pluck('type', 'id');
            foreach ($answers as $questionId => $answer) {
                if (! is_numeric($questionId) || ! $validQuestions->has((int) $questionId)) {
                    continue;
                }

                $isTheory = in_array($validQuestions->get((int) $questionId), ['theory', 'short_answer'], true);
                ExamAnswer::where('attempt_id', $attempt->id)
                    ->where('question_id', (int) $questionId)
                    ->update([
                        'selected_answer' => $isTheory ? null : (is_string($answer) ? $answer : null),
                        'theory_answer' => $isTheory ? (is_string($answer) ? $answer : null) : null,
                        'answered_at' => now(),
                    ]);
            }
        }

        DB::transaction(function () use ($attempt) {
            $elapsed = (int) $attempt->started_at->diffInSeconds(now());
            $attempt->update([
                'submitted_at' => now(),
                'time_spent_seconds' => $elapsed,
                'status' => 'submitted',
            ]);
        });

        GradeExamAttempt::dispatch($attempt);

        return redirect()->route($this->routePrefix().'.results', $attempt);
    }

    /**
     * Show results after submission.
     */
    public function results(ExamAttempt $attempt): View|RedirectResponse
    {
        $student = auth()->user();

        if ($attempt->student_id !== $student->id) {
            abort(403);
        }

        if ($attempt->isInProgress()) {
            return redirect()->route($this->routePrefix().'.take', $attempt);
        }

        $exam = $attempt->exam;
        $exam->load(['questions', 'subject:id,name']);

        $answers = ExamAnswer::where('attempt_id', $attempt->id)
            ->get()
            ->keyBy('question_id');

        $grade = $attempt->percentage !== null
            ? $this->scoreService->getGrade($student->school_id, (float) $attempt->percentage)
            : null;

        return view('student.exams.results', [
            'exam' => $exam,
            'attempt' => $attempt,
            'answers' => $answers,
            'grade' => $grade,
            'category' => $this->resolveCategory(),
            'label' => $this->categoryLabel($this->resolveCategory()),
            'routePrefix' => $this->routePrefix(),
        ]);
    }

    // ── Private helpers ──

    private function autoSubmit(ExamAttempt $attempt): void
    {
        DB::transaction(function () use ($attempt) {
            $elapsed = $attempt->exam->time_limit_minutes
                ? $attempt->exam->time_limit_minutes * 60
                : (int) $attempt->started_at->diffInSeconds(now());

            $attempt->update([
                'submitted_at' => now(),
                'time_spent_seconds' => $elapsed,
                'status' => 'timed_out',
            ]);
        });

        GradeExamAttempt::dispatch($attempt);
    }

    private function resolveCategory(): ?string
    {
        // 1. Check query param first (unified index page uses ?category=)
        $queryCategory = request()->query('category');
        if (in_array($queryCategory, ['exam', 'assessment', 'assignment'], true)) {
            return $queryCategory;
        }

        // 2. Check route-bound exam model (for show/results/etc.)
        $exam = request()->route('exam');
        if ($exam instanceof Exam) {
            return $exam->category;
        }

        // 3. For the unified index (student.exams.index without ?category), return null = all
        $routeName = request()->route()?->getName() ?? '';
        if ($routeName === 'student.exams.index' && ! $queryCategory) {
            return null;
        }

        return 'exam';
    }

    private function categoryLabel(?string $category): string
    {
        return match ($category) {
            'assessment' => __('Assessment'),
            'assignment' => __('Assignment'),
            null => __('CBT'),
            default => __('Exam'),
        };
    }

    private function routePrefix(): string
    {
        return 'student.exams';
    }
}
