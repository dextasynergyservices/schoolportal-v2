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
            ->paginate(10);

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

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'class_ids' => ['required', 'array', 'min:1'],
            'class_ids.*' => ['integer', 'exists:classes,id'],
        ]);

        $classes = SchoolClass::withCount('students')
            ->whereIn('id', $validated['class_ids'])
            ->get();

        $deletable = $classes->where('students_count', 0);
        $skipped = $classes->count() - $deletable->count();

        foreach ($deletable as $class) {
            $class->delete();
        }

        if ($deletable->isEmpty()) {
            return redirect()->route('admin.classes.index')
                ->with('error', __('No classes were deleted. Move students out of selected classes first.'));
        }

        $message = trans_choice(':count class deleted.|:count classes deleted.', $deletable->count(), ['count' => $deletable->count()]);

        if ($skipped > 0) {
            $message .= ' '.__(':count selected class(es) with students were skipped.', ['count' => $skipped]);
        }

        return redirect()->route('admin.classes.index')
            ->with('success', $message);
    }
}
