<?php

declare(strict_types=1);

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\SchoolClass;
use App\Models\TeacherAction;
use App\Services\FileUploadService;
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

        $assignments = $query->orderByDesc('created_at')->paginate(10)->withQueryString();

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
        $assignedClassIds = $teacher->assignedClasses()->pluck('id')->toArray();

        $currentSession = $school->currentSession();
        $currentTerm = $school->currentTerm();

        return view('teacher.assignments.create', compact('assignedClassIds', 'currentSession', 'currentTerm'));
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
            'assignment_file' => ['nullable', 'file', 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,jpg,jpeg,png,gif,webp', 'max:10240'],
            'file_url' => ['nullable', 'url'],
        ]);

        if (! in_array((int) $validated['class_id'], $classIds, true)) {
            abort(403, 'You can only create assignments for your assigned classes.');
        }

        $fileUrl = $validated['file_url'] ?? null;
        $filePublicId = null;

        if ($request->hasFile('assignment_file')) {
            $school = app('current.school');
            $upload = app(FileUploadService::class)->uploadAssignment($request->file('assignment_file'), $school->id);
            $fileUrl = $upload['url'];
            $filePublicId = $upload['public_id'];
        }

        DB::transaction(function () use ($validated, $teacher, $fileUrl, $filePublicId) {
            $assignment = Assignment::create([
                'class_id' => $validated['class_id'],
                'session_id' => $validated['session_id'],
                'term_id' => $validated['term_id'],
                'week_number' => $validated['week_number'],
                'title' => $validated['title'] ?? null,
                'description' => $validated['description'] ?? null,
                'due_date' => $validated['due_date'] ?? null,
                'file_url' => $fileUrl,
                'file_public_id' => $filePublicId,
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
            'assignment_file' => ['nullable', 'file', 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,jpg,jpeg,png,gif,webp', 'max:10240'],
            'file_url' => ['nullable', 'url'],
        ]);

        if (! in_array((int) $validated['class_id'], $classIds, true)) {
            abort(403, 'You can only create assignments for your assigned classes.');
        }

        $data = [
            'class_id' => $validated['class_id'],
            'week_number' => $validated['week_number'],
            'title' => $validated['title'] ?? null,
            'description' => $validated['description'] ?? null,
            'due_date' => $validated['due_date'] ?? null,
        ];

        if ($request->hasFile('assignment_file')) {
            if ($assignment->file_public_id) {
                app(FileUploadService::class)->delete($assignment->file_public_id);
            }
            $school = app('current.school');
            $upload = app(FileUploadService::class)->uploadAssignment($request->file('assignment_file'), $school->id);
            $data['file_url'] = $upload['url'];
            $data['file_public_id'] = $upload['public_id'];
        } elseif ($request->filled('file_url') && $request->input('file_url') !== $assignment->getRawOriginal('file_url')) {
            if ($assignment->file_public_id) {
                app(FileUploadService::class)->delete($assignment->file_public_id);
            }
            $data['file_url'] = $validated['file_url'];
            $data['file_public_id'] = null;
        }

        DB::transaction(function () use ($data, $assignment, $teacher) {
            $data['status'] = 'pending';
            $assignment->update($data);

            // Reset the existing TeacherAction to pending and re-notify admins
            $action = TeacherAction::where('entity_type', 'assignment')
                ->where('entity_id', $assignment->id)
                ->where('teacher_id', $teacher->id)
                ->first();

            if ($action) {
                $action->update([
                    'status' => 'pending',
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                    'rejection_reason' => null,
                ]);

                $this->notifyAdminsOfPendingSubmission($action, $teacher);
            }
        });

        return redirect()->route('teacher.assignments.index')
            ->with('success', __('Assignment updated and resubmitted for approval.'));
    }
}
