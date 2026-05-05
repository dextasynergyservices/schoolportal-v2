<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\QuestionBank;
use App\Models\Subject;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class QuestionBankBrowser extends Component
{
    use WithPagination;

    // ── Filters ────────────────────────────────────────────────────
    public string $search = '';

    public string $filterType = '';

    public string $filterDifficulty = '';

    public int|string $filterSubjectId = '';

    // ── Selection ─────────────────────────────────────────────────
    /** @var array<int, true> */
    public array $selected = [];

    // ── UI state ──────────────────────────────────────────────────
    public bool $open = false;

    // ── Lifecycle ─────────────────────────────────────────────────

    #[On('open-question-bank-browser')]
    public function openBrowser(): void
    {
        $this->open = true;
        $this->selected = [];
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterType(): void
    {
        $this->resetPage();
    }

    public function updatedFilterDifficulty(): void
    {
        $this->resetPage();
    }

    public function updatedFilterSubjectId(): void
    {
        $this->resetPage();
    }

    // ── Actions ───────────────────────────────────────────────────

    public function toggleSelect(int $id): void
    {
        if (isset($this->selected[$id])) {
            unset($this->selected[$id]);
        } else {
            $this->selected[$id] = true;
        }
    }

    public function selectAll(): void
    {
        foreach ($this->getPageQuestions() as $q) {
            $this->selected[$q->id] = true;
        }
    }

    public function deselectAll(): void
    {
        $this->selected = [];
    }

    public function importSelected(): void
    {
        if (empty($this->selected)) {
            return;
        }

        $school = app('current.school');

        $questions = QuestionBank::where('school_id', $school->id)
            ->whereIn('id', array_keys($this->selected))
            ->get()
            ->map(fn ($q) => [
                'id' => null,          // new question for the exam
                'bank_id' => $q->id,        // track origin
                'type' => $q->type,
                'question_text' => $q->question_text,
                'options' => $q->options ?? [],
                'correct_answer' => $q->correct_answer ?? '',
                'explanation' => $q->explanation ?? '',
                'marking_guide' => $q->marking_guide ?? '',
                'sample_answer' => $q->sample_answer ?? '',
                'points' => $q->points,
                'min_words' => $q->min_words,
                'max_words' => $q->max_words,
            ])
            ->values()
            ->toArray();

        // Increment times_used on selected bank questions
        QuestionBank::whereIn('id', array_keys($this->selected))->increment('times_used');

        $this->dispatch('questions-from-bank', questions: $questions);
        $this->open = false;
        $this->selected = [];
    }

    // ── Helpers ───────────────────────────────────────────────────

    private function getPageQuestions()
    {
        return $this->buildQuery()->simplePaginate(12);
    }

    private function buildQuery()
    {
        $school = app('current.school');

        return QuestionBank::with('subject:id,name')
            ->where('school_id', $school->id)
            ->when($this->search, fn ($q) => $q->where('question_text', 'like', '%'.$this->search.'%'))
            ->when($this->filterType, fn ($q) => $q->where('type', $this->filterType))
            ->when($this->filterDifficulty, fn ($q) => $q->where('difficulty', $this->filterDifficulty))
            ->when($this->filterSubjectId, fn ($q) => $q->where('subject_id', (int) $this->filterSubjectId))
            ->latest();
    }

    // ── Render ────────────────────────────────────────────────────

    public function render(): View
    {
        $questions = $this->buildQuery()->simplePaginate(12);
        $subjects = Subject::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('livewire.admin.question-bank-browser', [
            'questions' => $questions,
            'subjects' => $subjects,
            'selectedCount' => count($this->selected),
        ]);
    }
}
