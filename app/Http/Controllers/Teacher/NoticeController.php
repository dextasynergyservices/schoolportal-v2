<?php

declare(strict_types=1);

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use App\Models\SchoolLevel;
use App\Models\TeacherAction;
use App\Services\FileUploadService;
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
            ->paginate(10);

        // Load rejection reasons for rejected notices
        $rejectedNoticeIds = $notices->getCollection()->where('status', 'rejected')->pluck('id')->toArray();
        $rejectionReasons = [];
        if ($rejectedNoticeIds) {
            $rejectionReasons = TeacherAction::where('entity_type', 'notice')
                ->whereIn('entity_id', $rejectedNoticeIds)
                ->where('status', 'rejected')
                ->pluck('rejection_reason', 'entity_id')
                ->toArray();
        }

        return view('teacher.notices.index', compact('notices', 'rejectionReasons'));
    }

    public function create(): View
    {
        $teacher = auth()->user();
        $classes = $teacher->assignedClasses()->where('is_active', true)->orderBy('sort_order')->get();
        $assignedLevelIds = $classes->pluck('level_id')->unique()->values();
        $levels = SchoolLevel::whereIn('id', $assignedLevelIds)->where('is_active', true)->orderBy('sort_order')->get();

        return view('teacher.notices.create', compact('levels', 'classes'));
    }

    public function store(Request $request, FileUploadService $uploadService): RedirectResponse
    {
        $teacher = auth()->user();
        $assignedClasses = $teacher->assignedClasses()->get();
        $assignedClassIds = $assignedClasses->pluck('id')->toArray();
        $assignedLevelIds = $assignedClasses->pluck('level_id')->unique()->values()->toArray();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'file' => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx'],
            'target_levels' => ['nullable', 'array'],
            'target_levels.*' => ['in:'.implode(',', $assignedLevelIds)],
            'target_classes' => ['nullable', 'array'],
            'target_classes.*' => ['in:'.implode(',', $assignedClassIds)],
            'target_roles' => ['nullable', 'array'],
            'target_roles.*' => ['in:student,parent'],
            'expires_at' => ['nullable', 'date', 'after:today'],
        ]);

        unset($validated['file']);

        $fileData = [];
        if ($request->hasFile('file')) {
            $schoolId = $teacher->school_id;
            $uploaded = $uploadService->uploadNoticeFile($request->file('file'), $schoolId);
            $fileData = [
                'file_url' => $uploaded['url'],
                'file_public_id' => $uploaded['public_id'],
                'file_name' => $request->file('file')->getClientOriginalName(),
            ];
        }

        DB::transaction(function () use ($validated, $fileData, $teacher) {
            $notice = Notice::create([
                ...$validated,
                ...$fileData,
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

    public function edit(Notice $notice): View
    {
        $teacher = auth()->user();

        abort_unless($notice->created_by === $teacher->id, 403);

        $classes = $teacher->assignedClasses()->where('is_active', true)->orderBy('sort_order')->get();
        $assignedLevelIds = $classes->pluck('level_id')->unique()->values();
        $levels = SchoolLevel::whereIn('id', $assignedLevelIds)->where('is_active', true)->orderBy('sort_order')->get();

        $rejectionReason = TeacherAction::where('entity_type', 'notice')
            ->where('entity_id', $notice->id)
            ->where('status', 'rejected')
            ->latest('reviewed_at')
            ->value('rejection_reason');

        return view('teacher.notices.edit', compact('notice', 'levels', 'classes', 'rejectionReason'));
    }

    public function update(Request $request, Notice $notice, FileUploadService $uploadService): RedirectResponse
    {
        $teacher = auth()->user();

        abort_unless($notice->created_by === $teacher->id, 403);

        $assignedClasses = $teacher->assignedClasses()->get();
        $assignedClassIds = $assignedClasses->pluck('id')->toArray();
        $assignedLevelIds = $assignedClasses->pluck('level_id')->unique()->values()->toArray();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'file' => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx'],
            'target_levels' => ['nullable', 'array'],
            'target_levels.*' => ['in:'.implode(',', $assignedLevelIds)],
            'target_classes' => ['nullable', 'array'],
            'target_classes.*' => ['in:'.implode(',', $assignedClassIds)],
            'target_roles' => ['nullable', 'array'],
            'target_roles.*' => ['in:student,parent'],
            'expires_at' => ['nullable', 'date', 'after:today'],
        ]);

        unset($validated['file'], $validated['remove_file']);

        // Handle file upload
        if ($request->hasFile('file')) {
            if ($notice->file_public_id) {
                $uploadService->delete($notice->file_public_id);
            }
            $uploaded = $uploadService->uploadNoticeFile($request->file('file'), $teacher->school_id);
            $validated['file_url'] = $uploaded['url'];
            $validated['file_public_id'] = $uploaded['public_id'];
            $validated['file_name'] = $request->file('file')->getClientOriginalName();
        }

        // Handle file removal
        if ($request->boolean('remove_file') && $notice->file_public_id) {
            $uploadService->delete($notice->file_public_id);
            $validated['file_url'] = null;
            $validated['file_public_id'] = null;
            $validated['file_name'] = null;
        }

        DB::transaction(function () use ($validated, $notice, $teacher) {
            $notice->update([
                ...$validated,
                'status' => 'pending',
                'is_published' => false,
            ]);

            // Reset the existing TeacherAction to pending
            $action = TeacherAction::where('entity_type', 'notice')
                ->where('entity_id', $notice->id)
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

        return redirect()->route('teacher.notices.index')
            ->with('success', __('Notice updated and resubmitted for approval.'));
    }

    public function unpublish(Notice $notice): RedirectResponse
    {
        $teacher = auth()->user();
        abort_unless($notice->created_by === $teacher->id, 403);

        $notice->update([
            'is_published' => false,
        ]);

        return redirect()->route('teacher.notices.index')
            ->with('success', __('Notice unpublished.'));
    }

    public function publish(Notice $notice): RedirectResponse
    {
        $teacher = auth()->user();
        abort_unless($notice->created_by === $teacher->id, 403);
        abort_unless($notice->status === 'approved', 403);

        // Block publishing if the notice has expired
        if ($notice->expires_at && $notice->expires_at->isPast()) {
            return redirect()->route('teacher.notices.index')
                ->with('error', __('Cannot publish — this notice has passed its expiry date.'));
        }

        $notice->update([
            'is_published' => true,
            'published_at' => $notice->published_at ?? now(),
        ]);

        return redirect()->route('teacher.notices.index')
            ->with('success', __('Notice published.'));
    }

    public function destroy(Notice $notice, FileUploadService $uploadService): RedirectResponse
    {
        $teacher = auth()->user();
        abort_unless($notice->created_by === $teacher->id, 403);

        if ($notice->file_public_id) {
            $uploadService->delete($notice->file_public_id);
        }

        // Also clean up the teacher action record
        TeacherAction::where('entity_type', 'notice')
            ->where('entity_id', $notice->id)
            ->where('teacher_id', $teacher->id)
            ->delete();

        $notice->delete();

        return redirect()->route('teacher.notices.index')
            ->with('success', __('Notice deleted.'));
    }
}
