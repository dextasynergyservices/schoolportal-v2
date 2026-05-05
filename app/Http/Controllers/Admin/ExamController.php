<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassSubject;
use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Models\ExamQuestion;
use App\Models\SchoolClass;
use App\Models\SchoolLevel;
use App\Models\ScoreComponent;
use App\Models\Subject;
use App\Models\TeacherAction;
use App\Services\AiCreditService;
use App\Services\ExamGeneratorService;
use App\Services\ExamGradingService;
use App\Services\FileUploadService;
use App\Services\NotificationService;
use App\Services\ScoreAggregationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExamController extends Controller
{
    public function __construct(
        private readonly ExamGeneratorService $examGenerator,
        private readonly AiCreditService $creditService,
        private readonly FileUploadService $fileUploader,
        private readonly ExamGradingService $gradingService,
        private readonly ScoreAggregationService $scoreService,
    ) {}

    // ── Category & Route Helpers ──

    private function resolveCategory(): ?string
    {
        // 1. Check query param first (unified index page uses ?category=)
        $queryCategory = request()->query('category');
        if (in_array($queryCategory, ['exam', 'assessment', 'assignment'], true)) {
            return $queryCategory;
        }

        // 2. Check route-bound exam model (for show/edit/results/etc.)
        $exam = request()->route('exam');
        if ($exam instanceof Exam) {
            return $exam->category;
        }

        // 3. For the unified index (admin.exams.index without ?category), return null = all
        $name = request()->route()->getName();
        if ($name === 'admin.exams.index' && ! $queryCategory) {
            return null;
        }

        return 'exam';
    }

    private function routePrefix(): string
    {
        return 'admin.exams';
    }

    private function categoryLabel(): string
    {
        return match ($this->resolveCategory()) {
            'assessment' => __('Assessment'),
            'assignment' => __('Assignment'),
            null => __('CBT'),
            default => __('Exam'),
        };
    }

    private function viewData(): array
    {
        return [
            'category' => $this->resolveCategory(),
            'categoryLabel' => $this->categoryLabel(),
            'routePrefix' => $this->routePrefix(),
        ];
    }

    // ── CRUD ──

    public function index(Request $request): View
    {
        $category = $this->resolveCategory();

        $query = Exam::with([
            'class:id,name',
            'subject:id,name',
            'scoreComponent:id,name,short_name',
            'creator:id,name',
            'session:id,name',
            'term:id,name',
        ])->withCount('questions');

        // When category is null (unified view), show all; otherwise filter
        if ($category !== null) {
            $query->forCategory($category);
        }

        if ($request->filled('level_id')) {
            $classIds = SchoolClass::where('level_id', $request->input('level_id'))->pluck('id');
            $query->whereIn('class_id', $classIds);
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->input('class_id'));
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->input('subject_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $exams = $query->orderByDesc('created_at')->paginate(15)->withQueryString();
        $levels = SchoolLevel::where('is_active', true)->orderBy('sort_order')->get();
        $classes = SchoolClass::where('is_active', true)->orderBy('name')->get();
        $subjects = Subject::where('is_active', true)->orderBy('name')->get();

        return view('admin.exams.index', array_merge(
            compact('exams', 'levels', 'classes', 'subjects'),
            $this->viewData(),
        ));
    }

    public function create(): View
    {
        $school = app('current.school');
        $classes = SchoolClass::with('level:id,name')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $subjects = Subject::where('is_active', true)->orderBy('name')->get();
        $scoreComponents = ScoreComponent::where('is_active', true)->orderBy('sort_order')->get();
        $currentSession = $school->currentSession();
        $currentTerm = $school->currentTerm();
        $availableCredits = $this->creditService->getAvailableCredits($school);

        return view('admin.exams.create', array_merge(
            compact('classes', 'subjects', 'scoreComponents', 'currentSession', 'currentTerm', 'availableCredits'),
            $this->viewData(),
        ));
    }

    /**
     * AI-generate exam questions from a prompt or document.
     */
    public function generate(Request $request): RedirectResponse|View
    {
        $school = app('current.school');
        $category = $this->resolveCategory();
        $routePrefix = $this->routePrefix();

        $validated = $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'score_component_id' => ['nullable', 'exists:score_components,id'],
            'source_type' => ['required', 'in:file,link'],
            'source_file' => ['required_if:source_type,file', 'nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
            'document_url' => ['required_if:source_type,link', 'nullable', 'url', 'max:2000'],
            'question_count' => ['required', 'integer', 'in:5,10,15,20,25,30'],
            'question_types' => ['required', 'array', 'min:1'],
            'question_types.*' => ['in:multiple_choice,true_false,fill_blank,short_answer,theory,matching'],
            'difficulty' => ['required', 'in:easy,medium,hard'],
        ]);

        if (! $this->creditService->hasCredits($school)) {
            return redirect()->route("{$routePrefix}.create")
                ->with('error', __('No AI credits remaining. You can create manually or purchase more credits.'));
        }

        $class = SchoolClass::with('level:id,name')->findOrFail($validated['class_id']);
        $subject = Subject::findOrFail($validated['subject_id']);
        $classLevel = $class->level->name.' - '.$class->name;

        $sourceDocumentUrl = null;
        $sourceDocumentPublicId = null;

        if ($validated['source_type'] === 'file') {
            $uploaded = $this->fileUploader->uploadRawDocument($request->file('source_file'), $school->id);
            $sourceDocumentUrl = $uploaded['url'];
            $sourceDocumentPublicId = $uploaded['public_id'];

            $mimeType = $request->file('source_file')->getMimeType() ?? 'application/pdf';
            $content = $this->examGenerator->extractTextFromDocument($sourceDocumentUrl, $mimeType);
        } else {
            $sourceDocumentUrl = $validated['document_url'];
            $content = $this->examGenerator->extractTextFromUrl($validated['document_url']);
        }

        if (empty($content)) {
            return redirect()->route("{$routePrefix}.create")
                ->with('error', $this->examGenerator->lastError ?? __('Could not extract text from the source. Please try a different file or link.'));
        }

        $questions = $this->examGenerator->generateFromContent(
            content: $content,
            classLevel: $classLevel,
            subjectName: $subject->name,
            questionCount: (int) $validated['question_count'],
            questionTypes: $validated['question_types'],
            difficulty: $validated['difficulty'],
            category: $category,
        );

        if (empty($questions)) {
            return redirect()->route("{$routePrefix}.create")
                ->with('error', $this->examGenerator->lastError ?? __('AI could not generate questions. Please try again or create manually.'));
        }

        $this->creditService->deductCredit($school, auth()->user(), $category);

        $classes = SchoolClass::with('level:id,name')->where('is_active', true)->orderBy('name')->get();
        $subjects = Subject::where('is_active', true)->orderBy('name')->get();
        $scoreComponents = ScoreComponent::where('is_active', true)->orderBy('sort_order')->get();
        $currentSession = $school->currentSession();
        $currentTerm = $school->currentTerm();

        return view('admin.exams.review', array_merge([
            'questions' => $questions,
            'classes' => $classes,
            'subjects' => $subjects,
            'scoreComponents' => $scoreComponents,
            'selectedClassId' => (int) $validated['class_id'],
            'selectedSubjectId' => (int) $validated['subject_id'],
            'selectedScoreComponentId' => $validated['score_component_id'] ? (int) $validated['score_component_id'] : null,
            'currentSession' => $currentSession,
            'currentTerm' => $currentTerm,
            'sourceType' => 'document',
            'sourcePrompt' => null,
            'sourceDocumentUrl' => $sourceDocumentUrl,
            'sourceDocumentPublicId' => $sourceDocumentPublicId,
            'difficulty' => $validated['difficulty'],
        ], $this->viewData()));
    }

    /**
     * Store exam with questions (after AI review or manual creation).
     */
    public function store(Request $request): RedirectResponse|View
    {
        $school = app('current.school');
        $category = $this->resolveCategory();
        $routePrefix = $this->routePrefix();

        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'class_id' => ['required', 'exists:classes,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'score_component_id' => ['nullable', 'exists:score_components,id'],
            'source_type' => ['required', 'in:document,manual'],
            'source_prompt' => ['nullable', 'string'],
            'source_document_url' => ['nullable', 'url'],
            'source_document_public_id' => ['nullable', 'string'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1', 'max:300'],
            'max_score' => ['required', 'integer', 'min:1', 'max:200'],
            'passing_score' => ['required', 'integer', 'min:1', 'max:100'],
            'max_attempts' => ['required', 'integer', 'min:1', 'max:5'],
            'shuffle_questions' => ['boolean'],
            'shuffle_options' => ['boolean'],
            'show_correct_answers' => ['boolean'],
            'difficulty' => ['nullable', 'in:easy,medium,hard'],
            'available_from' => ['nullable', 'date'],
            'available_until' => ['nullable', 'date', 'after_or_equal:available_from'],
            'prevent_tab_switch' => ['boolean'],
            'prevent_copy_paste' => ['boolean'],
            'randomize_per_student' => ['boolean'],
            'max_tab_switches' => ['nullable', 'integer', 'min:1', 'max:10'],
            'instructions' => ['nullable', 'string', 'max:2000'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.type' => ['required', 'in:multiple_choice,true_false,fill_blank,short_answer,theory,matching'],
            'questions.*.question_text' => ['required', 'string', 'max:5000'],
            'questions.*.options' => ['nullable', 'array'],
            'questions.*.correct_answer' => ['nullable', 'string', 'max:2000'],
            'questions.*.marking_guide' => ['nullable', 'string', 'max:3000'],
            'questions.*.sample_answer' => ['nullable', 'string', 'max:5000'],
            'questions.*.min_words' => ['nullable', 'integer', 'min:1'],
            'questions.*.max_words' => ['nullable', 'integer', 'min:1'],
            'questions.*.explanation' => ['nullable', 'string', 'max:1000'],
            'questions.*.points' => ['required', 'integer', 'min:1', 'max:100'],
            'questions.*.section_label' => ['nullable', 'string', 'max:100'],
        ]);

        if ($validator->fails()) {
            // Re-render the form with errors and submitted data (prevents data loss from POST-rendered review pages)
            $classes = SchoolClass::with('level:id,name')->where('is_active', true)->orderBy('name')->get();
            $subjects = Subject::where('is_active', true)->orderBy('name')->get();
            $scoreComponents = ScoreComponent::where('is_active', true)->orderBy('sort_order')->get();

            $sourceType = $request->input('source_type', 'manual');
            $viewName = $sourceType === 'document' ? 'admin.exams.review' : 'admin.exams.create';

            return view($viewName, array_merge([
                'questions' => $request->input('questions', []),
                'classes' => $classes,
                'subjects' => $subjects,
                'scoreComponents' => $scoreComponents,
                'currentSession' => $school->currentSession(),
                'currentTerm' => $school->currentTerm(),
                'selectedClassId' => (int) $request->input('class_id'),
                'selectedSubjectId' => (int) $request->input('subject_id'),
                'selectedScoreComponentId' => $request->input('score_component_id') ? (int) $request->input('score_component_id') : null,
                'sourceType' => $sourceType,
                'sourcePrompt' => $request->input('source_prompt'),
                'sourceDocumentUrl' => $request->input('source_document_url'),
                'sourceDocumentPublicId' => $request->input('source_document_public_id'),
                'difficulty' => $request->input('difficulty', 'medium'),
                'availableCredits' => $this->creditService->getAvailableCredits($school),
            ], $this->viewData()))->withErrors($validator);
        }

        $validated = $validator->validated();

        $currentSession = $school->currentSession();
        $currentTerm = $school->currentTerm();

        if (! $currentSession || ! $currentTerm) {
            return redirect()->route("{$routePrefix}.index")
                ->with('error', __('No active academic session or term.'));
        }

        $totalPoints = collect($validated['questions'])->sum('points');

        DB::transaction(function () use ($validated, $currentSession, $currentTerm, $totalPoints, $category) {
            $exam = Exam::create([
                'class_id' => $validated['class_id'],
                'subject_id' => $validated['subject_id'],
                'score_component_id' => $validated['score_component_id'] ?? null,
                'session_id' => $currentSession->id,
                'term_id' => $currentTerm->id,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'category' => $category,
                'source_type' => $validated['source_type'],
                'source_prompt' => $validated['source_prompt'] ?? null,
                'source_document_url' => $validated['source_document_url'] ?? null,
                'source_document_public_id' => $validated['source_document_public_id'] ?? null,
                'time_limit_minutes' => $validated['time_limit_minutes'] ?? null,
                'max_score' => $validated['max_score'],
                'passing_score' => $validated['passing_score'],
                'max_attempts' => $validated['max_attempts'],
                'shuffle_questions' => $validated['shuffle_questions'] ?? false,
                'shuffle_options' => $validated['shuffle_options'] ?? false,
                'show_correct_answers' => $validated['show_correct_answers'] ?? false,
                'difficulty' => $validated['difficulty'] ?? 'medium',
                'available_from' => $validated['available_from'] ?? null,
                'available_until' => $validated['available_until'] ?? null,
                'prevent_tab_switch' => $validated['prevent_tab_switch'] ?? true,
                'prevent_copy_paste' => $validated['prevent_copy_paste'] ?? true,
                'randomize_per_student' => $validated['randomize_per_student'] ?? false,
                'max_tab_switches' => $validated['max_tab_switches'] ?? 3,
                'instructions' => $validated['instructions'] ?? null,
                'created_by' => auth()->id(),
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'is_published' => true,
                'published_at' => now(),
                'total_questions' => count($validated['questions']),
                'total_points' => $totalPoints,
            ]);

            foreach ($validated['questions'] as $index => $qData) {
                ExamQuestion::create([
                    'exam_id' => $exam->id,
                    'school_id' => auth()->user()->school_id,
                    'type' => $qData['type'],
                    'question_text' => $qData['question_text'],
                    'options' => $qData['options'] ?? null,
                    'correct_answer' => $qData['correct_answer'] ?? null,
                    'marking_guide' => $qData['marking_guide'] ?? null,
                    'sample_answer' => $qData['sample_answer'] ?? null,
                    'min_words' => $qData['min_words'] ?? null,
                    'max_words' => $qData['max_words'] ?? null,
                    'explanation' => $qData['explanation'] ?? null,
                    'points' => $qData['points'],
                    'sort_order' => $index,
                    'section_label' => $qData['section_label'] ?? null,
                ]);
            }
        });

        // Notify students and parents about the new exam
        $createdExam = Exam::where('class_id', $validated['class_id'])
            ->where('created_by', auth()->id())
            ->latest()
            ->first();

        if ($createdExam) {
            app(NotificationService::class)->notifyExamPublished($createdExam);
        }

        return redirect()->route("{$routePrefix}.index")
            ->with('success', __(':type created successfully.', ['type' => $this->categoryLabel()]));
    }

    public function show(Exam $exam): View
    {
        $exam->load([
            'class:id,name',
            'subject:id,name',
            'scoreComponent:id,name,short_name',
            'creator:id,name',
            'questions',
            'session:id,name',
            'term:id,name',
        ]);

        $teacherAction = TeacherAction::where('entity_type', 'exam')
            ->where('entity_id', $exam->id)
            ->latest()
            ->first();

        return view('admin.exams.show', array_merge(
            compact('exam', 'teacherAction'),
            $this->viewData(),
        ));
    }

    public function edit(Exam $exam): View
    {
        if (! in_array($exam->status, ['draft', 'pending', 'approved', 'rejected'])) {
            abort(403, 'This item cannot be edited.');
        }

        $exam->load('questions');

        $classes = SchoolClass::with('level:id,name')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $subjects = Subject::where('is_active', true)->orderBy('name')->get();
        $scoreComponents = ScoreComponent::where('is_active', true)->orderBy('sort_order')->get();

        return view('admin.exams.edit', array_merge(
            compact('exam', 'classes', 'subjects', 'scoreComponents'),
            $this->viewData(),
        ));
    }

    public function update(Request $request, Exam $exam): RedirectResponse
    {
        $routePrefix = $this->routePrefix();

        // Block editing if students have already taken the exam (answers reference question IDs)
        if ($exam->attempts()->exists()) {
            return redirect()->back()->with('error', __('This :type cannot be edited because students have already taken it. Unpublish it or create a new one instead.', ['type' => $this->categoryLabel()]));
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'class_id' => ['required', 'exists:classes,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'score_component_id' => ['nullable', 'exists:score_components,id'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1', 'max:300'],
            'max_score' => ['required', 'integer', 'min:1', 'max:200'],
            'passing_score' => ['required', 'integer', 'min:1', 'max:100'],
            'max_attempts' => ['required', 'integer', 'min:1', 'max:5'],
            'shuffle_questions' => ['boolean'],
            'shuffle_options' => ['boolean'],
            'show_correct_answers' => ['boolean'],
            'difficulty' => ['nullable', 'in:easy,medium,hard'],
            'available_from' => ['nullable', 'date'],
            'available_until' => ['nullable', 'date', 'after_or_equal:available_from'],
            'prevent_tab_switch' => ['boolean'],
            'prevent_copy_paste' => ['boolean'],
            'randomize_per_student' => ['boolean'],
            'max_tab_switches' => ['nullable', 'integer', 'min:1', 'max:10'],
            'instructions' => ['nullable', 'string', 'max:2000'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.type' => ['required', 'in:multiple_choice,true_false,fill_blank,short_answer,theory,matching'],
            'questions.*.question_text' => ['required', 'string', 'max:5000'],
            'questions.*.options' => ['nullable', 'array'],
            'questions.*.correct_answer' => ['nullable', 'string', 'max:2000'],
            'questions.*.marking_guide' => ['nullable', 'string', 'max:3000'],
            'questions.*.sample_answer' => ['nullable', 'string', 'max:5000'],
            'questions.*.min_words' => ['nullable', 'integer', 'min:1'],
            'questions.*.max_words' => ['nullable', 'integer', 'min:1'],
            'questions.*.explanation' => ['nullable', 'string', 'max:1000'],
            'questions.*.points' => ['required', 'integer', 'min:1', 'max:100'],
            'questions.*.section_label' => ['nullable', 'string', 'max:100'],
        ]);

        $totalPoints = collect($validated['questions'])->sum('points');

        DB::transaction(function () use ($validated, $exam, $totalPoints) {
            $exam->update([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'class_id' => $validated['class_id'],
                'subject_id' => $validated['subject_id'],
                'score_component_id' => $validated['score_component_id'] ?? null,
                'time_limit_minutes' => $validated['time_limit_minutes'] ?? null,
                'max_score' => $validated['max_score'],
                'passing_score' => $validated['passing_score'],
                'max_attempts' => $validated['max_attempts'],
                'shuffle_questions' => $validated['shuffle_questions'] ?? false,
                'shuffle_options' => $validated['shuffle_options'] ?? false,
                'show_correct_answers' => $validated['show_correct_answers'] ?? false,
                'difficulty' => $validated['difficulty'] ?? 'medium',
                'available_from' => $validated['available_from'] ?? null,
                'available_until' => $validated['available_until'] ?? null,
                'prevent_tab_switch' => $validated['prevent_tab_switch'] ?? true,
                'prevent_copy_paste' => $validated['prevent_copy_paste'] ?? true,
                'randomize_per_student' => $validated['randomize_per_student'] ?? false,
                'max_tab_switches' => $validated['max_tab_switches'] ?? 3,
                'instructions' => $validated['instructions'] ?? null,
                'total_questions' => count($validated['questions']),
                'total_points' => $totalPoints,
            ]);

            $exam->questions()->delete();

            foreach ($validated['questions'] as $index => $qData) {
                ExamQuestion::create([
                    'exam_id' => $exam->id,
                    'school_id' => $exam->school_id,
                    'type' => $qData['type'],
                    'question_text' => $qData['question_text'],
                    'options' => $qData['options'] ?? null,
                    'correct_answer' => $qData['correct_answer'] ?? null,
                    'marking_guide' => $qData['marking_guide'] ?? null,
                    'sample_answer' => $qData['sample_answer'] ?? null,
                    'min_words' => $qData['min_words'] ?? null,
                    'max_words' => $qData['max_words'] ?? null,
                    'explanation' => $qData['explanation'] ?? null,
                    'points' => $qData['points'],
                    'sort_order' => $index,
                    'section_label' => $qData['section_label'] ?? null,
                ]);
            }
        });

        return redirect()->route("{$routePrefix}.show", $exam)
            ->with('success', __(':type updated successfully.', ['type' => $this->categoryLabel()]));
    }

    public function publish(Exam $exam): RedirectResponse
    {
        $routePrefix = $this->routePrefix();

        if (! in_array($exam->status, ['approved', 'draft'])) {
            if ($exam->created_by !== auth()->id() || $exam->status !== 'draft') {
                return redirect()->back()->with('error', __('This item cannot be published.'));
            }
        }

        $exam->update([
            'is_published' => true,
            'published_at' => now(),
            'status' => 'approved',
            'approved_by' => $exam->approved_by ?? auth()->id(),
            'approved_at' => $exam->approved_at ?? now(),
        ]);

        // Notify students and parents
        app(NotificationService::class)->notifyExamPublished($exam);

        return redirect()->route("{$routePrefix}.index")
            ->with('success', __(':type published and visible to students.', ['type' => $this->categoryLabel()]));
    }

    public function unpublish(Exam $exam): RedirectResponse
    {
        $routePrefix = $this->routePrefix();

        $exam->update([
            'is_published' => false,
            'published_at' => null,
        ]);

        return redirect()->route("{$routePrefix}.index")
            ->with('success', __(':type unpublished.', ['type' => $this->categoryLabel()]));
    }

    public function destroy(Exam $exam): RedirectResponse
    {
        $routePrefix = $this->routePrefix();

        // Block deletion if students have already taken the exam
        if ($exam->attempts()->exists()) {
            return redirect()->back()->with('error', __('This :type cannot be deleted because students have already taken it. Unpublish it instead to hide it from students.', ['type' => $this->categoryLabel()]));
        }

        $exam->delete();

        return redirect()->route("{$routePrefix}.index")
            ->with('success', __(':type deleted.', ['type' => $this->categoryLabel()]));
    }

    /**
     * Inline subject creation: create a subject and optionally assign to a class.
     */
    public function storeSubject(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'short_name' => ['nullable', 'string', 'max:20'],
            'class_id' => ['nullable', 'exists:classes,id'],
        ]);

        $school = app('current.school');
        $slug = Str::slug($validated['name']);

        $existing = Subject::where('school_id', $school->id)->where('slug', $slug)->first();

        if ($existing) {
            return response()->json([
                'message' => 'A subject with this name already exists.',
            ], 422);
        }

        $subject = Subject::create([
            'school_id' => $school->id,
            'name' => $validated['name'],
            'slug' => $slug,
            'short_name' => $validated['short_name'] ?? null,
            'is_active' => true,
        ]);

        if (! empty($validated['class_id'])) {
            // Verify class belongs to the current school
            $classExists = SchoolClass::where('id', $validated['class_id'])
                ->where('school_id', $school->id)
                ->exists();

            if ($classExists) {
                ClassSubject::firstOrCreate([
                    'school_id' => $school->id,
                    'class_id' => $validated['class_id'],
                    'subject_id' => $subject->id,
                ]);
            }
        }

        return response()->json([
            'subject' => [
                'id' => $subject->id,
                'name' => $subject->name,
            ],
        ]);
    }

    // ── Results & Grading ──

    public function results(Exam $exam): View
    {
        $exam->load(['class:id,name', 'subject:id,name', 'session:id,name', 'term:id,name']);

        $attempts = ExamAttempt::where('exam_id', $exam->id)
            ->whereIn('status', ['submitted', 'timed_out', 'grading', 'graded'])
            ->with('student:id,name,username')
            ->orderBy('submitted_at', 'desc')
            ->get();

        $theoryQuestionCount = $exam->questions()->whereIn('type', ['short_answer', 'theory'])->count();

        $gradedAttempts = $attempts->whereNotNull('percentage');
        $stats = [
            'total_attempts' => $attempts->count(),
            'pending_grading' => $attempts->where('status', 'grading')->count(),
            'graded' => $gradedAttempts->count(),
            'average_score' => $gradedAttempts->count() > 0 ? round($gradedAttempts->avg('percentage'), 1) : null,
            'highest_score' => $gradedAttempts->max('percentage'),
            'lowest_score' => $gradedAttempts->min('percentage'),
            'pass_count' => $gradedAttempts->where('passed', true)->count(),
            'fail_count' => $gradedAttempts->where('passed', false)->count(),
        ];

        // Build grade map for each attempt
        $schoolId = auth()->user()->school_id;
        $grades = $attempts->mapWithKeys(fn (ExamAttempt $a) => [
            $a->id => $a->percentage !== null
                ? $this->scoreService->getGrade($schoolId, (float) $a->percentage)
                : null,
        ]);

        return view('admin.exams.results', array_merge(
            compact('exam', 'attempts', 'theoryQuestionCount', 'stats', 'grades'),
            $this->viewData(),
        ));
    }

    public function gradeStudent(Exam $exam, ExamAttempt $attempt): View
    {
        if ($attempt->exam_id !== $exam->id) {
            abort(404);
        }

        $exam->load(['class:id,name', 'subject:id,name']);

        $theoryQuestions = $exam->questions()
            ->whereIn('type', ['short_answer', 'theory'])
            ->orderBy('sort_order')
            ->get();

        $answers = $attempt->answers()
            ->whereIn('question_id', $theoryQuestions->pluck('id'))
            ->get()
            ->keyBy('question_id');

        $objectiveScore = $attempt->answers()
            ->whereNotIn('question_id', $theoryQuestions->pluck('id'))
            ->sum('points_earned');

        $objectiveTotal = $exam->questions()
            ->whereNotIn('type', ['short_answer', 'theory'])
            ->sum('points');

        return view('admin.exams.grade-student', array_merge(
            compact('exam', 'attempt', 'theoryQuestions', 'answers', 'objectiveScore', 'objectiveTotal'),
            $this->viewData(),
        ));
    }

    public function saveGrade(Request $request, Exam $exam, ExamAttempt $attempt): RedirectResponse
    {
        $routePrefix = $this->routePrefix();

        if ($attempt->exam_id !== $exam->id) {
            abort(404);
        }

        $validated = $request->validate([
            'grades' => ['required', 'array'],
            'grades.*.answer_id' => ['required', 'integer', 'exists:exam_answers,id'],
            'grades.*.points' => ['required', 'numeric', 'min:0'],
            'grades.*.comment' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($validated, $attempt) {
            foreach ($validated['grades'] as $grade) {
                $answer = ExamAnswer::where('id', $grade['answer_id'])
                    ->where('attempt_id', $attempt->id)
                    ->firstOrFail();

                $maxPoints = $answer->question->points;
                $points = min((float) $grade['points'], $maxPoints);

                $answer->update([
                    'points_earned' => $points,
                    'is_correct' => $points > 0,
                    'teacher_comment' => $grade['comment'] ?? null,
                    'graded_by' => auth()->id(),
                    'graded_at' => now(),
                ]);
            }

            $this->gradingService->recalculateAttemptScore($attempt);
        });

        return redirect()->route("{$routePrefix}.results", $exam)
            ->with('success', __('Grades saved successfully.'));
    }

    public function bulkGrade(Request $request, Exam $exam): View
    {
        $exam->load(['class:id,name', 'subject:id,name']);

        $theoryQuestions = $exam->questions()
            ->whereIn('type', ['short_answer', 'theory'])
            ->orderBy('sort_order')
            ->get();

        $currentQuestionId = $request->input('question', $theoryQuestions->first()?->id);
        $currentQuestion = $theoryQuestions->firstWhere('id', (int) $currentQuestionId);

        if (! $currentQuestion) {
            $currentQuestion = $theoryQuestions->first();
        }

        $attempts = collect();
        $answersForQuestion = collect();
        if ($currentQuestion) {
            $attempts = ExamAttempt::where('exam_id', $exam->id)
                ->whereIn('status', ['submitted', 'timed_out', 'grading', 'graded'])
                ->with('student:id,name,username')
                ->orderBy('started_at')
                ->get();

            $answersForQuestion = ExamAnswer::where('question_id', $currentQuestion->id)
                ->whereIn('attempt_id', $attempts->pluck('id'))
                ->get()
                ->keyBy('attempt_id');
        }

        return view('admin.exams.bulk-grade', array_merge(
            compact('exam', 'theoryQuestions', 'currentQuestion', 'attempts', 'answersForQuestion'),
            $this->viewData(),
        ));
    }

    public function saveBulkGrade(Request $request, Exam $exam): RedirectResponse
    {
        $routePrefix = $this->routePrefix();

        $validated = $request->validate([
            'question_id' => ['required', 'exists:exam_questions,id'],
            'grades' => ['required', 'array'],
            'grades.*.answer_id' => ['required', 'integer', 'exists:exam_answers,id'],
            'grades.*.points' => ['required', 'numeric', 'min:0'],
            'grades.*.comment' => ['nullable', 'string', 'max:500'],
        ]);

        $question = ExamQuestion::findOrFail($validated['question_id']);

        DB::transaction(function () use ($validated, $question) {
            foreach ($validated['grades'] as $grade) {
                $answer = ExamAnswer::where('id', $grade['answer_id'])->firstOrFail();
                $attempt = ExamAttempt::findOrFail($answer->attempt_id);

                $points = min((float) $grade['points'], $question->points);

                $answer->update([
                    'points_earned' => $points,
                    'is_correct' => $points > 0,
                    'teacher_comment' => $grade['comment'] ?? null,
                    'graded_by' => auth()->id(),
                    'graded_at' => now(),
                ]);

                $this->gradingService->recalculateAttemptScore($attempt);
            }
        });

        return redirect()->route("{$routePrefix}.results", $exam)
            ->with('success', __('Bulk grades saved successfully.'));
    }

    // ── Preview ──

    public function preview(Exam $exam): View
    {
        $exam->load(['questions', 'class:id,name', 'subject:id,name']);

        $questions = $exam->shuffle_questions
            ? $exam->questions->shuffle(crc32((string) $exam->id))
            : $exam->questions->sortBy('sort_order');

        $category = $exam->category;
        $label = match ($category) {
            'assessment' => __('Assessment'),
            'assignment' => __('Assignment'),
            default => __('Exam'),
        };

        return view('shared.exams.preview', array_merge(
            compact('exam', 'questions', 'category', 'label'),
            ['routePrefix' => $this->routePrefix()],
        ));
    }

    // ── Live Monitor & Analytics ──

    public function monitor(Exam $exam): View
    {
        $exam->load(['class:id,name', 'subject:id,name', 'session:id,name', 'term:id,name']);

        return view('admin.exams.monitor', array_merge(
            compact('exam'),
            $this->viewData(),
        ));
    }

    public function analytics(Exam $exam): View
    {
        $exam->load(['class:id,name', 'subject:id,name', 'session:id,name', 'term:id,name']);

        return view('admin.exams.analytics', array_merge(
            compact('exam'),
            $this->viewData(),
        ));
    }

    /**
     * Export CBT results as CSV for a single exam.
     */
    public function exportResultsCsv(Exam $exam): StreamedResponse
    {
        $exam->load(['class:id,name', 'subject:id,name', 'session:id,name', 'term:id,name']);

        $schoolId = auth()->user()->school_id;
        $attempts = ExamAttempt::where('exam_id', $exam->id)
            ->whereIn('status', ['submitted', 'timed_out', 'grading', 'graded'])
            ->with('student:id,name,username')
            ->orderBy('submitted_at')
            ->get();

        $headers = ['Student Name', 'Username', 'Score', 'Total Points', 'Percentage', 'Grade', 'Passed', 'Status', 'Submitted At'];
        $rows = [];

        foreach ($attempts as $attempt) {
            $grade = $attempt->percentage !== null
                ? $this->scoreService->getGrade($schoolId, (float) $attempt->percentage)
                : null;

            $rows[] = [
                $attempt->student?->name ?? '—',
                $attempt->student?->username ?? '—',
                $attempt->score ?? '',
                $attempt->total_points ?? '',
                $attempt->percentage !== null ? number_format((float) $attempt->percentage, 1) : '',
                $grade ? $grade['grade'].' ('.$grade['label'].')' : '',
                $attempt->passed ? 'Yes' : ($attempt->passed === false ? 'No' : ''),
                ucfirst($attempt->status),
                $attempt->submitted_at?->format('Y-m-d H:i') ?? '',
            ];
        }

        $categoryLabel = $this->categoryLabel();
        $className = str_replace(' ', '_', $exam->class?->name ?? 'Class');
        $filename = "{$categoryLabel}_Results_{$exam->title}_{$className}.csv";

        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Export CBT results as CSV across multiple exams (bulk export).
     * Supports filtering by level, class, subject.
     */
    public function exportBulkResultsCsv(Request $request): StreamedResponse
    {
        $category = $this->resolveCategory();
        $schoolId = auth()->user()->school_id;

        $examQuery = Exam::forCategory($category)
            ->with(['class:id,name', 'subject:id,name']);

        if ($request->filled('level_id')) {
            $classIds = SchoolClass::where('level_id', $request->input('level_id'))->pluck('id');
            $examQuery->whereIn('class_id', $classIds);
        }

        if ($request->filled('class_id')) {
            $examQuery->where('class_id', $request->input('class_id'));
        }

        if ($request->filled('subject_id')) {
            $examQuery->where('subject_id', $request->input('subject_id'));
        }

        $exams = $examQuery->orderBy('title')->get();

        $headers = ['Exam Title', 'Subject', 'Class', 'Student Name', 'Username', 'Score', 'Total Points', 'Percentage', 'Grade', 'Passed', 'Status', 'Submitted At'];
        $rows = [];

        foreach ($exams as $exam) {
            $attempts = ExamAttempt::where('exam_id', $exam->id)
                ->whereIn('status', ['submitted', 'timed_out', 'grading', 'graded'])
                ->with('student:id,name,username')
                ->orderBy('submitted_at')
                ->get();

            foreach ($attempts as $attempt) {
                $grade = $attempt->percentage !== null
                    ? $this->scoreService->getGrade($schoolId, (float) $attempt->percentage)
                    : null;

                $rows[] = [
                    $exam->title,
                    $exam->subject?->name ?? '—',
                    $exam->class?->name ?? '—',
                    $attempt->student?->name ?? '—',
                    $attempt->student?->username ?? '—',
                    $attempt->score ?? '',
                    $attempt->total_points ?? '',
                    $attempt->percentage !== null ? number_format((float) $attempt->percentage, 1) : '',
                    $grade ? $grade['grade'].' ('.$grade['label'].')' : '',
                    $attempt->passed ? 'Yes' : ($attempt->passed === false ? 'No' : ''),
                    ucfirst($attempt->status),
                    $attempt->submitted_at?->format('Y-m-d H:i') ?? '',
                ];
            }
        }

        $categoryLabel = $this->categoryLabel();
        $filename = "{$categoryLabel}_Results_Bulk_Export_".now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
