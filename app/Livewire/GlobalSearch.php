<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class GlobalSearch extends Component
{
    public string $query = '';

    /** @var array<string, list<array<string, string>>> */
    public array $results = [];

    public function updatedQuery(): void
    {
        $q = trim($this->query);

        if (strlen($q) < 2) {
            $this->results = [];

            return;
        }

        $school = app('current.school');

        $students = User::where('school_id', $school->id)
            ->where('role', 'student')
            ->where(function ($query) use ($q): void {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('username', 'like', "%{$q}%")
                    ->orWhereHas('studentProfile', fn ($p) => $p->where('admission_number', 'like', "%{$q}%"));
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
    }

    public function render(): View
    {
        return view('livewire.global-search');
    }
}
