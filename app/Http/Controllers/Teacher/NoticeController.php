<?php

declare(strict_types=1);

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use App\Models\SchoolLevel;
use App\Models\TeacherAction;
use App\Traits\NotifiesAdminsOnSubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class NoticeController extends Controller
{
    use NotifiesAdminsOnSubmission;

    public function index(): View
    {
        $teacher = auth()->user();

        $notices = Notice::where('created_by', $teacher->id)
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('teacher.notices.index', compact('notices'));
    }

    public function create(): View
    {
        $levels = SchoolLevel::where('is_active', true)->orderBy('sort_order')->get();

        return view('teacher.notices.create', compact('levels'));
    }

    public function store(Request $request): RedirectResponse
    {
        $teacher = auth()->user();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'target_levels' => ['nullable', 'array'],
            'target_levels.*' => ['exists:school_levels,id'],
            'target_roles' => ['nullable', 'array'],
            'target_roles.*' => ['in:student,parent,teacher'],
            'expires_at' => ['nullable', 'date', 'after:today'],
        ]);

        DB::transaction(function () use ($validated, $teacher) {
            $notice = Notice::create([
                ...$validated,
                'created_by' => $teacher->id,
                'is_published' => false,
                'status' => 'pending',
            ]);

            $action = TeacherAction::create([
                'school_id' => $teacher->school_id,
                'teacher_id' => $teacher->id,
                'action_type' => 'post_notice',
                'entity_type' => 'notice',
                'entity_id' => $notice->id,
                'status' => 'pending',
            ]);

            $this->notifyAdminsOfPendingSubmission($action, $teacher);
        });

        return redirect()->route('teacher.notices.index')
            ->with('success', __('Notice submitted for approval.'));
    }
}
