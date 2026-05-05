<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\AcademicSession;
use App\Models\SchoolClass;
use App\Models\SchoolLevel;
use App\Models\ScoreComponent;
use App\Models\StudentSubjectScore;
use App\Models\Term;
use App\Services\ScoreAggregationService;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;

class Gradebook extends Component
{
    public string $role = 'admin'; // 'admin' | 'teacher'

    public ?int $classId = null;

    public ?int $termId = null;

    // Rendered grid data (plain arrays for Livewire serialisation)
    public array $grid = [];

    public array $subjects = [];

    public array $components = [];

    public int $studentCount = 0;

    public int $filledCells = 0;

    public int $totalCells = 0;

    public int $cbtCells = 0;

    public int $lockedCells = 0;

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    // ── Lifecycle ────────────────────────────────────────────────────────────

    public function mount(string $role = 'admin'): void
    {
        $this->role = $role;

        $school = app('current.school');

        $currentTerm = Term::where('school_id', $school->id)
            ->where('is_current', true)
            ->first();

        if ($currentTerm) {
            $this->termId = $currentTerm->id;
        }
    }

    // ── Watchers ─────────────────────────────────────────────────────────────

    public function updatedClassId(): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;
        $this->loadGrid();
    }

    public function updatedTermId(): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;
        $this->loadGrid();
    }

    // ── Data Loading ─────────────────────────────────────────────────────────

    private function loadGrid(): void
    {
        $this->grid = [];
        $this->subjects = [];
        $this->components = [];
        $this->studentCount = 0;
        $this->filledCells = 0;
        $this->totalCells = 0;
        $this->cbtCells = 0;
        $this->lockedCells = 0;

        if (! $this->classId || ! $this->termId) {
            return;
        }

        $school = app('current.school');

        // Teachers can only access their own class
        if ($this->role === 'teacher') {
            $allowed = SchoolClass::where('teacher_id', auth()->id())
                ->where('id', $this->classId)
                ->where('school_id', $school->id)
                ->exists();

            if (! $allowed) {
                $this->errorMessage = 'You do not have access to this class.';

                return;
            }
        }

        /** @var ScoreAggregationService $service */
        $service = app(ScoreAggregationService::class);
        $data = $service->getClassScoreGrid($this->classId, $this->termId, $school->id);

        $this->subjects = $data['subjects']->map(fn ($s) => [
            'id' => $s->id,
            'name' => $s->name,
            'short_name' => $s->short_name ?? mb_substr($s->name, 0, 4),
        ])->values()->all();

        $this->components = $data['components']->map(fn ($c) => [
            'id' => $c->id,
            'name' => $c->name,
            'short_name' => $c->short_name,
            'max_score' => (float) $c->max_score,
            'weight' => (float) $c->weight,
        ])->values()->all();

        $this->grid = $data['students'];
        $this->studentCount = count($this->grid);

        // Completion stats
        $numComps = count($this->components);
        $numSubjects = count($this->subjects);
        $this->totalCells = $this->studentCount * $numSubjects * $numComps;

        foreach ($this->grid as $row) {
            foreach ($this->subjects as $subj) {
                foreach ($this->components as $comp) {
                    $cell = $row['subjects'][$subj['id']]['components'][$comp['id']] ?? null;
                    if ($cell && $cell['score'] !== null) {
                        $this->filledCells++;
                        if (($cell['source_type'] ?? '') === 'cbt') {
                            $this->cbtCells++;
                        }
                        if ($cell['is_locked'] ?? false) {
                            $this->lockedCells++;
                        }
                    }
                }
            }
        }
    }

    // ── Actions ───────────────────────────────────────────────────────────────

    /**
     * Batch-save scores sent from Alpine.js.
     *
     * $changes = [['student_id'=>…,'subject_id'=>…,'component_id'=>…,'score'=>…], …]
     */
    public function saveScores(array $changes): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;

        if (empty($changes)) {
            return;
        }

        $school = app('current.school');
        $user = auth()->user();

        // Teacher scope guard
        if ($this->role === 'teacher') {
            $allowed = SchoolClass::where('teacher_id', $user->id)
                ->where('id', $this->classId)
                ->where('school_id', $school->id)
                ->exists();

            if (! $allowed) {
                $this->errorMessage = 'Access denied.';

                return;
            }
        }

        $term = Term::where('school_id', $school->id)->find($this->termId);
        if (! $term) {
            $this->errorMessage = 'Term not found.';

            return;
        }

        // Pre-load components to avoid N+1
        $componentMap = ScoreComponent::withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->get()
            ->keyBy('id');

        $updated = 0;
        $skipped = 0;

        DB::transaction(function () use ($changes, $school, $term, $user, $componentMap, &$updated, &$skipped) {
            foreach ($changes as $change) {
                $studentId = (int) ($change['student_id'] ?? 0);
                $subjectId = (int) ($change['subject_id'] ?? 0);
                $componentId = (int) ($change['component_id'] ?? 0);
                $score = $change['score'] ?? '';

                if ($studentId === 0 || $subjectId === 0 || $componentId === 0) {
                    continue;
                }

                if ($score === '' || $score === null) {
                    continue;
                }

                $component = $componentMap->get($componentId);
                if (! $component) {
                    continue;
                }

                // Skip locked
                $existing = StudentSubjectScore::withoutGlobalScopes()
                    ->where('student_id', $studentId)
                    ->where('subject_id', $subjectId)
                    ->where('term_id', $term->id)
                    ->where('score_component_id', $componentId)
                    ->first();

                if ($existing && $existing->is_locked) {
                    $skipped++;

                    continue;
                }

                $scoreValue = min((float) $score, (float) $component->max_score);
                $scoreValue = max(0.0, $scoreValue);

                StudentSubjectScore::withoutGlobalScopes()->updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'subject_id' => $subjectId,
                        'term_id' => $term->id,
                        'score_component_id' => $componentId,
                    ],
                    [
                        'school_id' => $school->id,
                        'class_id' => $this->classId,
                        'session_id' => $term->session_id,
                        'score' => $scoreValue,
                        'max_score' => $component->max_score,
                        'source_type' => 'manual',
                        'entered_by' => $user->id,
                    ]
                );

                $updated++;
            }
        });

        if ($updated > 0) {
            $noun = $updated === 1 ? 'score' : 'scores';
            $this->successMessage = "{$updated} {$noun} saved successfully.";
            if ($skipped > 0) {
                $this->successMessage .= " ({$skipped} locked scores were skipped.)";
            }
            $this->dispatch('scoresSaved');
            $this->loadGrid();
        } elseif ($skipped > 0) {
            $this->errorMessage = 'All selected scores are locked and cannot be changed.';
        } else {
            $this->errorMessage = 'No scores were updated.';
        }
    }

    // ── Render ────────────────────────────────────────────────────────────────

    public function render(): View
    {
        $school = app('current.school');
        $user = auth()->user();

        if ($this->role === 'teacher') {
            $levels = collect();
            $classes = SchoolClass::where('teacher_id', $user->id)
                ->where('school_id', $school->id)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();
        } else {
            $levels = SchoolLevel::with([
                'classes' => fn ($q) => $q
                    ->where('school_id', $school->id)
                    ->where('is_active', true)
                    ->orderBy('sort_order'),
            ])
                ->where('school_id', $school->id)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();
            $classes = collect();
        }

        $sessions = AcademicSession::where('school_id', $school->id)
            ->with(['terms' => fn ($q) => $q->orderBy('term_number')])
            ->orderByDesc('start_date')
            ->get();

        $fillPercent = $this->totalCells > 0
            ? (int) round(($this->filledCells / $this->totalCells) * 100)
            : 0;

        $selectedClass = $this->classId
            ? SchoolClass::withoutGlobalScopes()->find($this->classId)
            : null;

        $selectedTerm = $this->termId
            ? Term::withoutGlobalScopes()->with('session')->find($this->termId)
            : null;

        return view('livewire.admin.gradebook', compact(
            'levels',
            'classes',
            'sessions',
            'fillPercent',
            'selectedClass',
            'selectedTerm',
        ));
    }
}
