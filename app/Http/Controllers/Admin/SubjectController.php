<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassSubject;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SubjectController extends Controller
{
    public function index(): View
    {
        $subjects = Subject::withCount('classes')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.subjects.index', compact('subjects'));
    }

    public function create(): View
    {
        $classes = SchoolClass::with('level:id,name')
            ->where('is_active', true)
            ->orderBy('level_id')
            ->orderBy('sort_order')
            ->get();

        return view('admin.subjects.create', compact('classes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'short_name' => ['nullable', 'string', 'max:20'],
            'category' => ['nullable', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'class_ids' => ['nullable', 'array'],
            'class_ids.*' => ['exists:classes,id'],
        ]);

        $classIds = $validated['class_ids'] ?? [];
        unset($validated['class_ids']);

        $validated['slug'] = Str::slug($validated['name']);

        $subject = Subject::create($validated);

        if ($classIds) {
            $school = app('current.school');
            foreach ($classIds as $classId) {
                ClassSubject::firstOrCreate(
                    ['class_id' => $classId, 'subject_id' => $subject->id],
                    ['school_id' => $school->id]
                );
            }
        }

        $assignedCount = count($classIds);
        $message = __('Subject ":name" created.', ['name' => $validated['name']]);
        if ($assignedCount > 0) {
            $message .= ' '.__('Assigned to :count class(es).', ['count' => $assignedCount]);
        }

        return redirect()->route('admin.subjects.index')
            ->with('success', $message);
    }

    public function edit(Subject $subject): View
    {
        $classes = SchoolClass::with('level:id,name')
            ->where('is_active', true)
            ->orderBy('level_id')
            ->orderBy('sort_order')
            ->get();

        $assignedClassIds = $subject->classes()->pluck('classes.id')->toArray();

        return view('admin.subjects.edit', compact('subject', 'classes', 'assignedClassIds'));
    }

    public function update(Request $request, Subject $subject): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'short_name' => ['nullable', 'string', 'max:20'],
            'category' => ['nullable', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'class_ids' => ['nullable', 'array'],
            'class_ids.*' => ['exists:classes,id'],
        ]);

        $classIds = $validated['class_ids'] ?? [];
        unset($validated['class_ids']);

        $validated['slug'] = Str::slug($validated['name']);

        $subject->update($validated);

        // Sync class assignments — add new, remove unchecked
        $school = app('current.school');
        $currentClassIds = $subject->classes()->pluck('classes.id')->toArray();

        $toAdd = array_diff($classIds, $currentClassIds);
        $toRemove = array_diff($currentClassIds, $classIds);

        foreach ($toAdd as $classId) {
            ClassSubject::firstOrCreate(
                ['class_id' => $classId, 'subject_id' => $subject->id],
                ['school_id' => $school->id]
            );
        }

        if ($toRemove) {
            ClassSubject::where('subject_id', $subject->id)
                ->whereIn('class_id', $toRemove)
                ->delete();
        }

        return redirect()->route('admin.subjects.index')
            ->with('success', __('Subject updated.'));
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        if ($subject->classes()->exists()) {
            return redirect()->route('admin.subjects.index')
                ->with('error', __('Cannot delete a subject that is assigned to classes. Remove assignments first.'));
        }

        $subject->delete();

        return redirect()->route('admin.subjects.index')
            ->with('success', __('Subject deleted.'));
    }

    /**
     * Show class-subject assignment page.
     */
    public function assignments(): View
    {
        $classes = SchoolClass::with(['level:id,name', 'subjects'])
            ->where('is_active', true)
            ->orderBy('level_id')
            ->orderBy('sort_order')
            ->get();

        $subjects = Subject::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
        $teachers = User::where('role', 'teacher')->where('is_active', true)->orderBy('name')->get();

        return view('admin.subjects.assignments', compact('classes', 'subjects', 'teachers'));
    }

    /**
     * Save class-subject assignments (including subject teachers).
     */
    public function saveAssignments(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'assignments' => ['required', 'array'],
            'assignments.*.class_id' => ['required', 'exists:classes,id'],
            'assignments.*.subject_id' => ['required', 'exists:subjects,id'],
            'assignments.*.teacher_id' => ['nullable', 'exists:users,id'],
        ]);

        $school = app('current.school');

        // Get all current assignments for this school
        $existingKeys = ClassSubject::pluck('id', DB::raw("CONCAT(class_id, '-', subject_id)"))->toArray();

        $submittedKeys = [];
        foreach ($validated['assignments'] as $assignment) {
            $key = $assignment['class_id'].'-'.$assignment['subject_id'];
            $submittedKeys[$key] = $assignment;
        }

        // Upsert submitted assignments
        foreach ($submittedKeys as $key => $assignment) {
            ClassSubject::updateOrCreate(
                [
                    'class_id' => $assignment['class_id'],
                    'subject_id' => $assignment['subject_id'],
                ],
                [
                    'school_id' => $school->id,
                    'teacher_id' => $assignment['teacher_id'] ?? null,
                ]
            );
        }

        // Delete assignments that were removed
        $toDelete = array_diff(array_keys($existingKeys), array_keys($submittedKeys));
        if ($toDelete) {
            ClassSubject::whereIn('id', array_intersect_key($existingKeys, array_flip($toDelete)))->delete();
        }

        return redirect()->route('admin.subjects.assignments')
            ->with('success', __('Subject assignments saved.'));
    }

    /**
     * Quick assign: assign all subjects to a class at once.
     */
    public function quickAssign(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'subject_ids' => ['required', 'array'],
            'subject_ids.*' => ['exists:subjects,id'],
        ]);

        $school = app('current.school');

        foreach ($validated['subject_ids'] as $subjectId) {
            ClassSubject::firstOrCreate(
                [
                    'class_id' => $validated['class_id'],
                    'subject_id' => $subjectId,
                ],
                [
                    'school_id' => $school->id,
                ]
            );
        }

        return redirect()->route('admin.subjects.assignments')
            ->with('success', __('Subjects assigned to class.'));
    }

    public function removeAssignment(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
        ]);

        ClassSubject::where('class_id', $validated['class_id'])
            ->where('subject_id', $validated['subject_id'])
            ->delete();

        return redirect()->route('admin.subjects.assignments')
            ->with('success', __('Subject removed from class.'));
    }
}
