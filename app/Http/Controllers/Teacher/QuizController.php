<?php

declare(strict_types=1);

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\SchoolClass;
use App\Models\TeacherAction;
use App\Services\AiCreditService;
use App\Services\QuizGeneratorService;
use App\Traits\NotifiesAdminsOnSubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuizController extends Controller
{
    use NotifiesAdminsOnSubmission;

    public function __construct(
        private readonly QuizGeneratorService $quizGenerator,
        private readonly AiCreditService $creditService,
    ) {}

    public function index(Request $request): View
    {
        $teacher = auth()->user();
        $classIds = $teacher->assignedClasses()->pluck('id');

        $query = Quiz::with(['class:id,name', 'session:id,name', 'term:id,name'])
            ->where('created_by', $teacher->id);

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->input('class_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $quizzes = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        $classes = SchoolClass::whereIn('id', $classIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('teacher.quizzes.index', compact('quizzes', 'classes'));
    }

    public function create(): View
    {
        $teacher = auth()->user();
        $school = app('current.school');
        $classIds = $teacher->assignedClasses()->pluck('id');

        $classes = SchoolClass::whereIn('id', $classIds)
            ->with('level:id,name')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $currentSession = $school->currentSession();
        $currentTerm = $school->currentTerm();

        $levelId = $teacher->level_id;
        $availableCredits = $this->creditService->getAvailableCredits($school, $levelId);

        return view('teacher.quizzes.create', compact(
            'classes',
            'currentSession',
            'currentTerm',
            'availableCredits',
        ));
    }

    /**
     * AI-generate quiz questions from a prompt or document.
     */
    public function generate(Request $request): RedirectResponse|View
    {
        $teacher = auth()->user();
        $school = app('current.school');
        $classIds = $teacher->assignedClasses()->pluck('id')->toArray();

        $validated = $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'source_type' => ['required', 'in:prompt,document'],
            'prompt' => ['required_if:source_type,prompt', 'nullable', 'string', 'max:2000'],
            'document_url' => ['required_if:source_type,document', 'nullable', 'url'],
            'document_public_id' => ['nullable', 'string'],
            'question_count' => ['required', 'integer', 'in:5,10,15,20'],
            'question_types' => ['required', 'array', 'min:1'],
            'question_types.*' => ['in:multiple_choice,true_false,fill_blank'],
            'difficulty' => ['required', 'in:easy,medium,hard'],
        ]);

        if (! in_array((int) $validated['class_id'], $classIds, true)) {
            abort(403, 'You can only create quizzes for your assigned classes.');
        }

        $levelId = $teacher->level_id;
        if (! $this->creditService->hasCredits($school, $levelId)) {
            return redirect()->route('teacher.quizzes.create')
                ->with('error', __('No AI credits remaining. You can create quizzes manually or ask your admin to purchase more credits.'));
        }

        $class = SchoolClass::with('level:id,name')->findOrFail($validated['class_id']);
        $classLevel = $class->level->name.' - '.$class->name;

        if ($validated['source_type'] === 'document') {
            $content = $this->quizGenerator->extractTextFromDocument($validated['document_url']);
            if (empty($content)) {
                return redirect()->route('teacher.quizzes.create')
                    ->with('error', $this->quizGenerator->lastError ?? __('Could not extract text from the document. Please try a different file.'));
            }
        } else {
            $content = $validated['prompt'];
        }

        $questions = $this->quizGenerator->generateFromContent(
            content: $content,
            classLevel: $classLevel,
            questionCount: (int) $validated['question_count'],
            questionTypes: $validated['question_types'],
            difficulty: $validated['difficulty'],
        );

        if (empty($questions)) {
            return redirect()->route('teacher.quizzes.create')
                ->with('error', $this->quizGenerator->lastError ?? __('AI could not generate questions. Please try again or create manually.'));
        }

        // Deduct 1 credit
        $this->creditService->deductCredit($school, $teacher, 'quiz', levelId: $levelId);

        $currentSession = $school->currentSession();
        $currentTerm = $school->currentTerm();

        $classes = SchoolClass::whereIn('id', $classIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('teacher.quizzes.review', [
            'questions' => $questions,
            'classes' => $classes,
            'selectedClassId' => (int) $validated['class_id'],
            'currentSession' => $currentSession,
            'currentTerm' => $currentTerm,
            'sourceType' => $validated['source_type'],
            'sourcePrompt' => $validated['prompt'] ?? null,
            'sourceDocumentUrl' => $validated['document_url'] ?? null,
            'sourceDocumentPublicId' => $validated['document_public_id'] ?? null,
            'difficulty' => $validated['difficulty'],
        ]);
    }

    /**
     * Store quiz with questions (after AI review or manual creation).
     */
    public function store(Request $request): RedirectResponse
    {
        $teacher = auth()->user();
        $classIds = $teacher->assignedClasses()->pluck('id')->toArray();
        $school = app('current.school');

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'class_id' => ['required', 'exists:classes,id'],
            'source_type' => ['required', 'in:prompt,document,manual'],
            'source_prompt' => ['nullable', 'string'],
            'source_document_url' => ['nullable', 'url'],
            'source_document_public_id' => ['nullable', 'string'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1', 'max:180'],
            'passing_score' => ['required', 'integer', 'min:1', 'max:100'],
            'max_attempts' => ['required', 'integer', 'min:1', 'max:10'],
            'shuffle_questions' => ['boolean'],
            'shuffle_options' => ['boolean'],
            'show_correct_answers' => ['boolean'],
            'difficulty' => ['nullable', 'in:easy,medium,hard'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.type' => ['required', 'in:multiple_choice,true_false,fill_blank'],
            'questions.*.question_text' => ['required', 'string', 'max:2000'],
            'questions.*.options' => ['required', 'array'],
            'questions.*.correct_answer' => ['required', 'string', 'max:500'],
            'questions.*.explanation' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! in_array((int) $validated['class_id'], $classIds, true)) {
            abort(403, 'You can only create quizzes for your assigned classes.');
        }

        $currentSession = $school->currentSession();
        $currentTerm = $school->currentTerm();

        if (! $currentSession || ! $currentTerm) {
            return redirect()->route('teacher.quizzes.index')
                ->with('error', __('No active academic session or term. Please contact your admin.'));
        }

        DB::transaction(function () use ($validated, $teacher, $currentSession, $currentTerm) {
            $quiz = Quiz::create([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'class_id' => $validated['class_id'],
                'session_id' => $currentSession->id,
                'term_id' => $currentTerm->id,
                'source_type' => $validated['source_type'] === 'manual' ? 'prompt' : $validated['source_type'],
                'source_prompt' => $validated['source_prompt'] ?? null,
                'source_document_url' => $validated['source_document_url'] ?? null,
                'source_document_public_id' => $validated['source_document_public_id'] ?? null,
                'time_limit_minutes' => $validated['time_limit_minutes'] ?? null,
                'passing_score' => $validated['passing_score'],
                'max_attempts' => $validated['max_attempts'],
                'shuffle_questions' => $validated['shuffle_questions'] ?? false,
                'shuffle_options' => $validated['shuffle_options'] ?? false,
                'show_correct_answers' => $validated['show_correct_answers'] ?? true,
                'created_by' => $teacher->id,
                'status' => 'pending',
                'total_questions' => count($validated['questions']),
            ]);

            foreach ($validated['questions'] as $index => $questionData) {
                QuizQuestion::create([
                    'quiz_id' => $quiz->id,
                    'school_id' => $teacher->school_id,
                    'type' => $questionData['type'],
                    'question_text' => $questionData['question_text'],
                    'options' => $questionData['options'],
                    'correct_answer' => $questionData['correct_answer'],
                    'explanation' => $questionData['explanation'] ?? null,
                    'sort_order' => $index,
                ]);
            }

            $action = TeacherAction::create([
                'school_id' => $teacher->school_id,
                'teacher_id' => $teacher->id,
                'action_type' => 'create_quiz',
                'entity_type' => 'quiz',
                'entity_id' => $quiz->id,
                'status' => 'pending',
            ]);

            $this->notifyAdminsOfPendingSubmission($action, $teacher);
        });

        return redirect()->route('teacher.quizzes.index')
            ->with('success', __('Quiz created and submitted for approval.'));
    }

    public function edit(Quiz $quiz): View
    {
        $teacher = auth()->user();

        if ($quiz->created_by !== $teacher->id) {
            abort(403);
        }

        if (! in_array($quiz->status, ['draft', 'pending', 'rejected'])) {
            abort(403, 'Approved quizzes cannot be edited.');
        }

        $quiz->load('questions');
        $classIds = $teacher->assignedClasses()->pluck('id');

        $classes = SchoolClass::whereIn('id', $classIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('teacher.quizzes.edit', compact('quiz', 'classes'));
    }

    public function update(Request $request, Quiz $quiz): RedirectResponse
    {
        $teacher = auth()->user();

        if ($quiz->created_by !== $teacher->id) {
            abort(403);
        }

        if (! in_array($quiz->status, ['draft', 'pending', 'rejected'])) {
            abort(403, 'Approved quizzes cannot be edited.');
        }

        $classIds = $teacher->assignedClasses()->pluck('id')->toArray();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'class_id' => ['required', 'exists:classes,id'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1', 'max:180'],
            'passing_score' => ['required', 'integer', 'min:1', 'max:100'],
            'max_attempts' => ['required', 'integer', 'min:1', 'max:10'],
            'shuffle_questions' => ['boolean'],
            'shuffle_options' => ['boolean'],
            'show_correct_answers' => ['boolean'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.type' => ['required', 'in:multiple_choice,true_false,fill_blank'],
            'questions.*.question_text' => ['required', 'string', 'max:2000'],
            'questions.*.options' => ['required', 'array'],
            'questions.*.correct_answer' => ['required', 'string', 'max:500'],
            'questions.*.explanation' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! in_array((int) $validated['class_id'], $classIds, true)) {
            abort(403, 'You can only create quizzes for your assigned classes.');
        }

        DB::transaction(function () use ($validated, $quiz) {
            $quiz->update([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'class_id' => $validated['class_id'],
                'time_limit_minutes' => $validated['time_limit_minutes'] ?? null,
                'passing_score' => $validated['passing_score'],
                'max_attempts' => $validated['max_attempts'],
                'shuffle_questions' => $validated['shuffle_questions'] ?? false,
                'shuffle_options' => $validated['shuffle_options'] ?? false,
                'show_correct_answers' => $validated['show_correct_answers'] ?? true,
                'total_questions' => count($validated['questions']),
                'status' => 'pending',
            ]);

            // Replace all questions
            $quiz->questions()->delete();

            foreach ($validated['questions'] as $index => $questionData) {
                QuizQuestion::create([
                    'quiz_id' => $quiz->id,
                    'school_id' => $quiz->school_id,
                    'type' => $questionData['type'],
                    'question_text' => $questionData['question_text'],
                    'options' => $questionData['options'],
                    'correct_answer' => $questionData['correct_answer'],
                    'explanation' => $questionData['explanation'] ?? null,
                    'sort_order' => $index,
                ]);
            }
        });

        return redirect()->route('teacher.quizzes.index')
            ->with('success', __('Quiz updated and resubmitted for approval.'));
    }

    public function show(Quiz $quiz): View
    {
        $teacher = auth()->user();

        if ($quiz->created_by !== $teacher->id) {
            abort(403);
        }

        $quiz->load(['questions', 'class:id,name', 'session:id,name', 'term:id,name']);

        return view('teacher.quizzes.show', compact('quiz'));
    }

    public function results(Quiz $quiz): View
    {
        $teacher = auth()->user();

        if ($quiz->created_by !== $teacher->id) {
            abort(403);
        }

        $quiz->load(['class:id,name', 'questions']);

        $attempts = $quiz->attempts()
            ->with('student:id,name,username')
            ->whereIn('status', ['submitted', 'timed_out'])
            ->orderByDesc('percentage')
            ->get();

        $stats = [
            'total_students' => $attempts->pluck('student_id')->unique()->count(),
            'average_score' => $attempts->avg('percentage') ? round($attempts->avg('percentage'), 1) : 0,
            'highest_score' => $attempts->max('percentage') ?? 0,
            'lowest_score' => $attempts->min('percentage') ?? 0,
            'passed' => $attempts->where('passed', true)->count(),
            'failed' => $attempts->where('passed', false)->count(),
        ];

        return view('teacher.quizzes.results', compact('quiz', 'attempts', 'stats'));
    }

    public function exportCsv(Quiz $quiz): StreamedResponse
    {
        $teacher = auth()->user();

        if ($quiz->created_by !== $teacher->id) {
            abort(403);
        }

        $attempts = $quiz->attempts()
            ->with('student:id,name,username')
            ->whereIn('status', ['submitted', 'timed_out'])
            ->orderByDesc('percentage')
            ->get();

        $filename = 'quiz-'.Str::slug($quiz->title).'-results-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($attempts) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'Student Name', 'Username', 'Attempt #', 'Score', 'Total Points',
                'Percentage', 'Status', 'Time Spent', 'Submitted At',
            ]);

            foreach ($attempts as $attempt) {
                $time = $attempt->time_spent_seconds;
                $timeFormatted = $time !== null
                    ? sprintf('%02d:%02d', intdiv($time, 60), $time % 60)
                    : '';

                fputcsv($handle, [
                    $attempt->student?->name ?? '',
                    $attempt->student?->username ?? '',
                    $attempt->attempt_number,
                    $attempt->score ?? 0,
                    $attempt->total_points ?? 0,
                    $attempt->percentage !== null ? number_format((float) $attempt->percentage, 2) : '',
                    $attempt->passed ? 'Passed' : 'Failed',
                    $timeFormatted,
                    $attempt->submitted_at?->format('Y-m-d H:i:s') ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function destroy(Quiz $quiz): RedirectResponse
    {
        $teacher = auth()->user();

        if ($quiz->created_by !== $teacher->id) {
            abort(403);
        }

        if ($quiz->is_published) {
            abort(403, 'Published quizzes cannot be deleted.');
        }

        $quiz->delete();

        return redirect()->route('teacher.quizzes.index')
            ->with('success', __('Quiz deleted.'));
    }
}
