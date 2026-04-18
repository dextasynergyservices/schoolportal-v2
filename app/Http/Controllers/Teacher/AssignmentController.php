<?php

declare(strict_types=1);

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\SchoolClass;
use App\Models\TeacherAction;
use App\Traits\NotifiesAdminsOnSubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AssignmentController extends Controller
{
    use NotifiesAdminsOnSubmission;

    public function index(Request $request): View
    {
        $teacher = auth()->user();
        $classIds = $teacher->assignedClasses()->pluck('id');

        $query = Assignment::with(['class:id,name', 'session:id,name', 'term:id,name'])
            ->where('uploaded_by', $teacher->id);

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->input('class_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $assignments = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        $classes = SchoolClass::whereIn('id', $classIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('teacher.assignments.index', compact('assignments', 'classes'));
    }

    public function create(): View
    {
        $teacher = auth()->user();
        $school = app('current.school');
        $classIds = $teacher->assignedClasses()->pluck('id');

        $classes = SchoolClass::whereIn('id', $classIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $currentSession = $school->currentSession();
        $currentTerm = $school->currentTerm();

        return view('teacher.assignments.create', compact('classes', 'currentSession', 'currentTerm'));
    }

    public function store(Request $request): RedirectResponse
    {
        $teacher = auth()->user();
        $classIds = $teacher->assignedClasses()->pluck('id')->toArray();

        $validated = $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'session_id' => ['required', 'exists:academic_sessions,id'],
            'term_id' => ['required', 'exists:terms,id'],
            'week_number' => ['required', 'integer', 'min:1', 'max:12'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
            'file_url' => ['nullable', 'url'],
            'file_public_id' => ['nullable', 'string'],
        ]);

        if (! in_array((int) $validated['class_id'], $classIds, true)) {
            abort(403, 'You can only create assignments for your assigned classes.');
        }

        DB::transaction(function () use ($validated, $teacher) {
            $assignment = Assignment::create([
                ...$validated,
                'uploaded_by' => $teacher->id,
                'status' => 'pending',
            ]);

            $action = TeacherAction::create([
                'school_id' => $teacher->school_id,
                'teacher_id' => $teacher->id,
                'action_type' => 'upload_assignment',
                'entity_type' => 'assignment',
                'entity_id' => $assignment->id,
                'status' => 'pending',
            ]);

            $this->notifyAdminsOfPendingSubmission($action, $teacher);
        });

        return redirect()->route('teacher.assignments.index')
            ->with('success', __('Assignment submitted for approval.'));
    }

    public function edit(Assignment $assignment): View
    {
        $teacher = auth()->user();

        if ($assignment->uploaded_by !== $teacher->id) {
            abort(403);
        }

        if ($assignment->status === 'approved') {
            return redirect()->route('teacher.assignments.index')
                ->with('error', __('Approved assignments cannot be edited.'));
        }

        $classIds = $teacher->assignedClasses()->pluck('id');
        $classes = SchoolClass::whereIn('id', $classIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('teacher.assignments.edit', compact('assignment', 'classes'));
    }

    public function update(Request $request, Assignment $assignment): RedirectResponse
    {
        $teacher = auth()->user();

        if ($assignment->uploaded_by !== $teacher->id) {
            abort(403);
        }

        if ($assignment->status === 'approved') {
            return redirect()->route('teacher.assignments.index')
                ->with('error', __('Approved assignments cannot be edited.'));
        }

        $classIds = $teacher->assignedClasses()->pluck('id')->toArray();

        $validated = $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'week_number' => ['required', 'integer', 'min:1', 'max:12'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
        ]);

        if (! in_array((int) $validated['class_id'], $classIds, true)) {
            abort(403, 'You can only create assignments for your assigned classes.');
        }

        $assignment->update($validated);

        return redirect()->route('teacher.assignments.index')
            ->with('success', __('Assignment updated.'));
    }
}
