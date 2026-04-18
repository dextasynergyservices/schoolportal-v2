<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\SchoolLevel;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ClassController extends Controller
{
    public function index(): View
    {
        $classes = SchoolClass::with(['level:id,name', 'teacher:id,name'])
            ->withCount(['students'])
            ->orderBy('level_id')
            ->orderBy('sort_order')
            ->paginate(20);

        return view('admin.classes.index', compact('classes'));
    }

    public function create(): View
    {
        $levels = SchoolLevel::where('is_active', true)->orderBy('sort_order')->get();
        $teachers = User::where('role', 'teacher')->where('is_active', true)->orderBy('name')->get();

        return view('admin.classes.create', compact('levels', 'teachers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'level_id' => ['required', 'exists:school_levels,id'],
            'teacher_id' => ['nullable', 'exists:users,id'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        SchoolClass::create($validated);

        return redirect()->route('admin.classes.index')
            ->with('success', __('Class ":name" created.', ['name' => $validated['name']]));
    }

    public function edit(SchoolClass $class): View
    {
        $levels = SchoolLevel::where('is_active', true)->orderBy('sort_order')->get();
        $teachers = User::where('role', 'teacher')->where('is_active', true)->orderBy('name')->get();

        return view('admin.classes.edit', compact('class', 'levels', 'teachers'));
    }

    public function update(Request $request, SchoolClass $class): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'level_id' => ['required', 'exists:school_levels,id'],
            'teacher_id' => ['nullable', 'exists:users,id'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $class->update($validated);

        return redirect()->route('admin.classes.index')
            ->with('success', __('Class updated.'));
    }

    public function destroy(SchoolClass $class): RedirectResponse
    {
        if ($class->students()->exists()) {
            return redirect()->route('admin.classes.index')
                ->with('error', __('Cannot delete a class that has students. Move students first.'));
        }

        $class->delete();

        return redirect()->route('admin.classes.index')
            ->with('success', __('Class deleted.'));
    }
}
