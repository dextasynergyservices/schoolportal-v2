<?php

declare(strict_types=1);

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\SchoolClass;
use App\Models\TeacherAction;
use App\Services\AiCreditService;
use App\Services\GameGeneratorService;
use App\Services\QuizGeneratorService;
use App\Traits\NotifiesAdminsOnSubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GameController extends Controller
{
    use NotifiesAdminsOnSubmission;

    public function __construct(
        private readonly GameGeneratorService $gameGenerator,
        private readonly AiCreditService $creditService,
    ) {}

    public function index(Request $request): View
    {
        $teacher = auth()->user();

        $query = Game::with(['class:id,name', 'session:id,name', 'term:id,name', 'latestTeacherAction'])
            ->where('created_by', $teacher->id);

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->input('class_id'));
        }

        if ($request->filled('game_type')) {
            $query->where('game_type', $request->input('game_type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $games = $query->orderByDesc('created_at')->paginate(10)->withQueryString();

        $classIds = $teacher->assignedClasses()->pluck('id');
        $classes = SchoolClass::whereIn('id', $classIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('teacher.games.index', compact('games', 'classes'));
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
        $availableCredits = $this->creditService->getAvailableCredits($school, $teacher->level_id ? (int) $teacher->level_id : null);

        return view('teacher.games.create', compact('classes', 'currentSession', 'currentTerm', 'availableCredits'));
    }

    public function generate(Request $request): RedirectResponse|View
    {
        $teacher = auth()->user();
        $school = app('current.school');
        $classIds = $teacher->assignedClasses()->pluck('id')->toArray();

        $validated = $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'game_type' => ['required', 'in:memory_match,word_scramble,quiz_race,flashcard'],
            'source_type' => ['required', 'in:prompt,document'],
            'prompt' => ['required_if:source_type,prompt', 'nullable', 'string', 'max:2000'],
            'document_url' => ['required_if:source_type,document', 'nullable', 'url'],
            'document_public_id' => ['nullable', 'string'],
            'difficulty' => ['required', 'in:easy,medium,hard'],
        ]);

        if (! in_array((int) $validated['class_id'], $classIds, true)) {
            abort(403);
        }

        $levelId = $teacher->level_id ? (int) $teacher->level_id : null;
        if (! $this->creditService->hasCredits($school, $levelId)) {
            return redirect()->route('teacher.games.create')
                ->with('error', __('No AI credits remaining. Create games manually or ask your admin to purchase more.'));
        }

        $class = SchoolClass::with('level:id,name')->findOrFail($validated['class_id']);
        $classLevel = $class->level->name.' - '.$class->name;

        if ($validated['source_type'] === 'document') {
            $quizGen = app(QuizGeneratorService::class);
            $content = $quizGen->extractTextFromDocument($validated['document_url']);
            if (empty($content)) {
                return redirect()->route('teacher.games.create')
                    ->with('error', $quizGen->lastError ?? __('Could not extract text from the document.'));
            }
        } else {
            $content = $validated['prompt'];
        }

        $gameData = $this->gameGenerator->generateGameContent(
            content: $content,
            gameType: $validated['game_type'],
            classLevel: $classLevel,
            difficulty: $validated['difficulty'],
        );

        if (empty($gameData)) {
            return redirect()->route('teacher.games.create')
                ->with('error', $this->gameGenerator->lastError ?? __('AI could not generate game content. Please try again.'));
        }

        $this->creditService->deductCredit($school, $teacher, 'game', levelId: $levelId);

        $classes = SchoolClass::whereIn('id', $classIds)->where('is_active', true)->orderBy('name')->get();

        return view('teacher.games.review', [
            'gameData' => $gameData,
            'gameType' => $validated['game_type'],
            'classes' => $classes,
            'selectedClassId' => (int) $validated['class_id'],
            'currentSession' => $school->currentSession(),
            'currentTerm' => $school->currentTerm(),
            'sourceType' => $validated['source_type'],
            'sourcePrompt' => $validated['prompt'] ?? null,
            'sourceDocumentUrl' => $validated['document_url'] ?? null,
            'sourceDocumentPublicId' => $validated['document_public_id'] ?? null,
            'difficulty' => $validated['difficulty'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $teacher = auth()->user();
        $school = app('current.school');
        $classIds = $teacher->assignedClasses()->pluck('id')->toArray();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'class_id' => ['required', 'exists:classes,id'],
            'game_type' => ['required', 'in:memory_match,word_scramble,quiz_race,flashcard'],
            'source_type' => ['required', 'in:prompt,document,manual'],
            'source_prompt' => ['nullable', 'string'],
            'source_document_url' => ['nullable', 'url'],
            'source_document_public_id' => ['nullable', 'string'],
            'difficulty' => ['required', 'in:easy,medium,hard'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1', 'max:60'],
            'game_data' => ['required', 'json'],
        ]);

        if (! in_array((int) $validated['class_id'], $classIds, true)) {
            abort(403);
        }

        $currentSession = $school->currentSession();
        $currentTerm = $school->currentTerm();

        if (! $currentSession || ! $currentTerm) {
            return redirect()->route('teacher.games.index')
                ->with('error', __('No active academic session or term.'));
        }

        DB::transaction(function () use ($validated, $teacher, $currentSession, $currentTerm) {
            $game = Game::create([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'class_id' => $validated['class_id'],
                'session_id' => $currentSession->id,
                'term_id' => $currentTerm->id,
                'game_type' => $validated['game_type'],
                'source_type' => $validated['source_type'] === 'manual' ? 'prompt' : $validated['source_type'],
                'source_prompt' => $validated['source_prompt'] ?? null,
                'source_document_url' => $validated['source_document_url'] ?? null,
                'source_document_public_id' => $validated['source_document_public_id'] ?? null,
                'game_data' => json_decode($validated['game_data'], true),
                'difficulty' => $validated['difficulty'],
                'time_limit_minutes' => $validated['time_limit_minutes'] ?? null,
                'created_by' => $teacher->id,
                'status' => 'pending',
            ]);

            $action = TeacherAction::create([
                'school_id' => $teacher->school_id,
                'teacher_id' => $teacher->id,
                'action_type' => 'create_game',
                'entity_type' => 'game',
                'entity_id' => $game->id,
                'status' => 'pending',
            ]);

            $this->notifyAdminsOfPendingSubmission($action, $teacher);
        });

        return redirect()->route('teacher.games.index')
            ->with('success', __('Game created and submitted for approval.'));
    }

    public function show(Game $game): View
    {
        if ($game->created_by !== auth()->id()) {
            abort(403);
        }

        $game->load(['class:id,name', 'session:id,name', 'term:id,name', 'latestTeacherAction']);

        return view('teacher.games.show', compact('game'));
    }

    public function edit(Game $game): View
    {
        if ($game->created_by !== auth()->id()) {
            abort(403);
        }

        if (! in_array($game->status, ['draft', 'pending', 'rejected'])) {
            abort(403, 'Approved games cannot be edited.');
        }

        $classIds = auth()->user()->assignedClasses()->pluck('id');
        $classes = SchoolClass::whereIn('id', $classIds)->where('is_active', true)->orderBy('name')->get();

        return view('teacher.games.edit', compact('game', 'classes'));
    }

    public function update(Request $request, Game $game): RedirectResponse
    {
        if ($game->created_by !== auth()->id()) {
            abort(403);
        }

        if (! in_array($game->status, ['draft', 'pending', 'rejected'])) {
            abort(403);
        }

        $classIds = auth()->user()->assignedClasses()->pluck('id')->toArray();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'class_id' => ['required', 'exists:classes,id'],
            'difficulty' => ['required', 'in:easy,medium,hard'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1', 'max:60'],
            'game_data' => ['required', 'json'],
        ]);

        if (! in_array((int) $validated['class_id'], $classIds, true)) {
            abort(403);
        }

        $teacher = auth()->user();

        $game->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'class_id' => $validated['class_id'],
            'difficulty' => $validated['difficulty'],
            'time_limit_minutes' => $validated['time_limit_minutes'] ?? null,
            'game_data' => json_decode($validated['game_data'], true),
            'status' => 'pending',
        ]);

        // Reset the existing TeacherAction back to pending
        $action = TeacherAction::where('entity_type', 'game')
            ->where('entity_id', $game->id)
            ->latest()
            ->first();

        if ($action) {
            $action->update([
                'status' => 'pending',
                'reviewed_by' => null,
                'reviewed_at' => null,
                'rejection_reason' => null,
            ]);
        } else {
            $action = TeacherAction::create([
                'school_id' => $teacher->school_id,
                'teacher_id' => $teacher->id,
                'action_type' => 'create_game',
                'entity_type' => 'game',
                'entity_id' => $game->id,
                'status' => 'pending',
            ]);
        }

        $this->notifyAdminsOfPendingSubmission($action, $teacher);

        return redirect()->route('teacher.games.index')
            ->with('success', __('Game updated and resubmitted for approval.'));
    }

    public function stats(Game $game): View
    {
        if ($game->created_by !== auth()->id()) {
            abort(403);
        }

        $game->load('class:id,name');

        $baseQuery = $game->plays()->where('completed', true);

        $stats = [
            'total_plays' => (clone $baseQuery)->count(),
            'unique_players' => (clone $baseQuery)->distinct('student_id')->count('student_id'),
            'average_score' => round((float) (clone $baseQuery)->avg('percentage'), 1),
            'highest_score' => (float) ((clone $baseQuery)->max('percentage') ?? 0),
        ];

        $plays = $baseQuery
            ->with('student:id,name,username')
            ->orderByDesc('percentage')
            ->paginate(10);

        return view('teacher.games.stats', compact('game', 'plays', 'stats'));
    }

    public function destroy(Game $game): RedirectResponse
    {
        if ($game->created_by !== auth()->id()) {
            abort(403);
        }

        if ($game->is_published) {
            abort(403, 'Published games cannot be deleted.');
        }

        $game->delete();

        return redirect()->route('teacher.games.index')
            ->with('success', __('Game deleted.'));
    }
}
