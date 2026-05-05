<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class GlobalSearch extends Component
{
    public string $query = '';

    public bool $isOpen = false;

    /** @var array<string, list<array<string, string>>> */
    public array $results = [];

    /** @var list<array{label: string, url: string}> */
    public array $recentSearches = [];

    public function mount(): void
    {
        $this->recentSearches = session('global_search_recent', []);
    }

    public function updatedQuery(): void
    {
        $q = trim($this->query);

        if (strlen($q) < 2) {
            $this->results = [];
            $this->isOpen = ! empty($this->recentSearches);

            return;
        }

        try {
            $school = app('current.school');

            // Build name tokens for multi-word matching (e.g. "john doe" → ['john', 'doe'])
            $tokens = array_filter(explode(' ', $q));

            $students = User::where('school_id', $school->id)
                ->where('role', 'student')
                ->where(function ($query) use ($q, $tokens): void {
                    $query->where('name', 'like', "%{$q}%")
                        ->orWhere('username', 'like', "%{$q}%")
                        ->orWhereHas('studentProfile', fn ($p) => $p->where('admission_number', 'like', "%{$q}%"));

                    // Multi-token name matching: all tokens must appear somewhere in name
                    if (count($tokens) > 1) {
                        $query->orWhere(function ($sub) use ($tokens): void {
                            foreach ($tokens as $token) {
                                $sub->where('name', 'like', "%{$token}%");
                            }
                        });
                    }
                })
                ->with('studentProfile.class:id,name')
                ->limit(5)
                ->get(['id', 'name', 'username'])
                ->map(fn ($s) => [
                    'label' => $s->name,
                    'sub' => $s->studentProfile?->class?->name ?? $s->username,
                    'url' => route('admin.students.show', $s->id),
                    'icon' => 'academic-cap',
                ])
                ->toArray();

            $teachers = User::where('school_id', $school->id)
                ->where('role', 'teacher')
                ->where(function ($query) use ($q): void {
                    $query->where('name', 'like', "%{$q}%")
                        ->orWhere('username', 'like', "%{$q}%");
                })
                ->limit(4)
                ->get(['id', 'name', 'username'])
                ->map(fn ($t) => [
                    'label' => $t->name,
                    'sub' => $t->username,
                    'url' => route('admin.teachers.index'),
                    'icon' => 'user',
                ])
                ->toArray();

            $classes = SchoolClass::where('school_id', $school->id)
                ->where('is_active', true)
                ->where('name', 'like', "%{$q}%")
                ->with('level:id,name')
                ->limit(4)
                ->get(['id', 'name', 'level_id'])
                ->map(fn ($c) => [
                    'label' => $c->name,
                    'sub' => $c->level?->name ?? '',
                    'url' => route('admin.classes.index'),
                    'icon' => 'squares-2x2',
                ])
                ->toArray();

            $this->results = array_filter([
                'students' => $students,
                'teachers' => $teachers,
                'classes' => $classes,
            ]);

            $this->isOpen = true;

        } catch (\Throwable $e) {
            Log::error('[GlobalSearch] exception in updatedQuery', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            $this->results = [];
        }
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->query = '';
        $this->results = [];
    }

    /**
     * Saves a clicked result to recent searches (max 5, most recent first).
     */
    public function saveRecentSearch(string $label, string $url): void
    {
        $recent = session('global_search_recent', []);

        // Remove duplicate if present
        $recent = array_values(array_filter($recent, fn ($r) => $r['url'] !== $url));

        // Prepend new item
        array_unshift($recent, ['label' => $label, 'url' => $url]);

        // Keep only 5
        $recent = array_slice($recent, 0, 5);

        session(['global_search_recent' => $recent]);

        $this->recentSearches = $recent;
    }

    /**
     * Clear the recent searches list.
     */
    public function clearRecentSearches(): void
    {
        session()->forget('global_search_recent');
        $this->recentSearches = [];
    }

    public function render(): View
    {
        return view('livewire.global-search');
    }
}
