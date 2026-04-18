<?php

declare(strict_types=1);

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Result;
use App\Models\SchoolClass;
use App\Models\TeacherAction;
use App\Models\User;
use App\Traits\NotifiesAdminsOnSubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ResultController extends Controller
{
    use NotifiesAdminsOnSubmission;

    public function index(Request $request): View
    {
        $teacher = auth()->user();
        $classIds = $teacher->assignedClasses()->pluck('id');

        $query = Result::with([
            'student:id,name,username',
            'class:id,name',
            'session:id,name',
            'term:id,name',
        ])->where('uploaded_by', $teacher->id);

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->input('class_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('student', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('username', 'like', "%{$search}%"));
        }

        $results = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        $classes = SchoolClass::whereIn('id', $classIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('teacher.results.index', compact('results', 'classes'));
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

        $students = User::where('role', 'student')
            ->where('is_active', true)
            ->whereHas('studentProfile', fn ($q) => $q->whereIn('class_id', $classIds))
            ->orderBy('name')
            ->get();

        $currentSession = $school->currentSession();
        $currentTerm = $school->currentTerm();

        return view('teacher.results.create', compact('classes', 'students', 'currentSession', 'currentTerm'));
    }

    public function store(Request $request): RedirectResponse
    {
        $teacher = auth()->user();
        $classIds = $teacher->assignedClasses()->pluck('id')->toArray();

        $validated = $request->validate([
            'student_id' => ['required', 'exists:users,id'],
            'class_id' => ['required', 'exists:classes,id'],
            'session_id' => ['required', 'exists:academic_sessions,id'],
            'term_id' => ['required', 'exists:terms,id'],
            'file_url' => ['required', 'url'],
            'file_public_id' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        // Ensure teacher can only upload to their assigned classes
        if (! in_array((int) $validated['class_id'], $classIds, true)) {
            abort(403, 'You can only upload results for your assigned classes.');
        }

        DB::transaction(function () use ($validated, $teacher) {
            $result = Result::create([
                ...$validated,
                'uploaded_by' => $teacher->id,
                'status' => 'pending',
            ]);

            $action = TeacherAction::create([
                'school_id' => $teacher->school_id,
                'teacher_id' => $teacher->id,
                'action_type' => 'upload_result',
                'entity_type' => 'result',
                'entity_id' => $result->id,
                'status' => 'pending',
            ]);

            $this->notifyAdminsOfPendingSubmission($action, $teacher);
        });

        return redirect()->route('teacher.results.index')
            ->with('success', __('Result uploaded and submitted for approval.'));
    }
}
