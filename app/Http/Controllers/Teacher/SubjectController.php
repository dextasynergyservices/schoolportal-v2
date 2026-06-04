<?php

declare(strict_types=1);

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\ClassSubject;
use App\Models\Exam;
use App\Models\StudentSubjectScore;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SubjectController extends Controller
{
    public function index(): View
    {
        $teacher = auth()->user();
        $classIds = $teacher->assignedClasses()->where('is_active', true)->pluck('id');

        $subjects = Subject::whereHas('classes', fn ($query) => $query->whereIn('classes.id', $classIds))
            ->with(['classes' => fn ($query) => $query->whereIn('classes.id', $classIds)->orderBy('name')])
            ->withCount('classes')
            ->orderBy('name')
            ->paginate(20);

        return view('teacher.subjects.index', compact('subjects'));
    }

    public function create(): View
    {
        $teacher = auth()->user();
        $classes = $teacher->assignedClasses()
            ->where('is_active', true)
            ->with('level:id,name')
            ->orderBy('name')
            ->get();

        $classIds = $classes->pluck('id');
        $subjects = Subject::where('is_active', true)
            ->with(['classes' => fn ($query) => $query->whereIn('classes.id', $classIds)->orderBy('name')])
            ->orderBy('name')
            ->get();

        return view('teacher.subjects.create', compact('classes', 'subjects'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'short_name' => ['nullable', 'string', 'max:20'],
            'category' => ['nullable', 'string', 'max:50'],
            'class_ids' => ['required', 'array', 'min:1'],
            'class_ids.*' => ['integer', 'exists:classes,id'],
        ]);

        $teacher = auth()->user();
        $classIds = collect($validated['class_ids'])->map(fn ($id) => (int) $id)->unique()->values();
        $allowedClassIds = $teacher->assignedClasses()->where('is_active', true)->pluck('id');

        if ($classIds->diff($allowedClassIds)->isNotEmpty()) {
            abort(403, __('You can only add subjects to your assigned classes.'));
        }

        $school = app('current.school');
        $slug = Str::slug($validated['name']);
        $existingSubject = Subject::where('slug', $slug)->first();

        if ($existingSubject) {
            $message = $existingSubject->is_active
                ? __('":name" already exists in the school subject pool. Use Assign Existing Subject instead.', [
                    'name' => $existingSubject->name,
                ])
                : __('":name" already exists but is inactive. Ask your school admin to reactivate it.', [
                    'name' => $existingSubject->name,
                ]);

            return redirect()->back()
                ->withInput()
                ->withErrors(['name' => $message]);
        }

        DB::transaction(function () use ($validated, $teacher, $school, $slug, $classIds): void {
            $subject = Subject::create([
                'school_id' => $school->id,
                'created_by' => $teacher->id,
                'name' => $validated['name'],
                'slug' => $slug,
                'short_name' => $validated['short_name'] ?? null,
                'category' => $validated['category'] ?? null,
                'is_active' => true,
            ]);

            foreach ($classIds as $classId) {
                ClassSubject::firstOrCreate(
                    ['class_id' => $classId, 'subject_id' => $subject->id],
                    ['school_id' => $school->id, 'teacher_id' => $teacher->id],
                );
            }
        });

        return redirect()->route('teacher.subjects.index')
            ->with('success', __('Subject added to your selected class(es).'));
    }

    public function assign(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'class_ids' => ['required', 'array', 'min:1'],
            'class_ids.*' => ['integer', 'exists:classes,id'],
        ]);

        $teacher = auth()->user();
        $classIds = collect($validated['class_ids'])->map(fn ($id) => (int) $id)->unique()->values();
        $allowedClassIds = $teacher->assignedClasses()->where('is_active', true)->pluck('id');

        if ($classIds->diff($allowedClassIds)->isNotEmpty()) {
            abort(403, __('You can only assign subjects to your assigned classes.'));
        }

        $subject = Subject::where('is_active', true)->findOrFail($validated['subject_id']);
        $school = app('current.school');
        $assignedCount = 0;

        DB::transaction(function () use ($classIds, $school, $subject, $teacher, &$assignedCount): void {
            foreach ($classIds as $classId) {
                $assignment = ClassSubject::firstOrCreate(
                    ['class_id' => $classId, 'subject_id' => $subject->id],
                    ['school_id' => $school->id, 'teacher_id' => $teacher->id],
                );

                if ($assignment->wasRecentlyCreated) {
                    $assignedCount++;
                }
            }
        });

        $message = $assignedCount > 0
            ? __('":name" assigned to :count class(es).', ['name' => $subject->name, 'count' => $assignedCount])
            : __('":name" is already assigned to the selected class(es).', ['name' => $subject->name]);

        return redirect()->route('teacher.subjects.index')->with('success', $message);
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        $teacher = auth()->user();

        if ((int) $subject->created_by !== (int) $teacher->id) {
            abort(403, __('You can only delete subjects you created.'));
        }

        $assignedClassIds = $subject->classes()->pluck('classes.id');
        $allowedClassIds = $teacher->assignedClasses()->pluck('id');

        if ($assignedClassIds->diff($allowedClassIds)->isNotEmpty()) {
            abort(403, __('This subject is assigned outside your classes.'));
        }

        if (Exam::where('subject_id', $subject->id)->exists()
            || StudentSubjectScore::where('subject_id', $subject->id)->exists()) {
            throw ValidationException::withMessages([
                'subject' => __('This subject has CBT or score history and cannot be deleted.'),
            ]);
        }

        $subject->delete();

        return redirect()->route('teacher.subjects.index')
            ->with('success', __('Subject deleted.'));
    }
}
