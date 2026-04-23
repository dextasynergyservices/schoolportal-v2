<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use App\Models\SchoolClass;
use App\Models\SchoolLevel;
use App\Services\FileUploadService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NoticeController extends Controller
{
    public function index(): View
    {
        $notices = Notice::with('creator:id,name')
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('admin.notices.index', compact('notices'));
    }

    public function create(): View
    {
        $levels = SchoolLevel::where('is_active', true)->orderBy('sort_order')->get();
        $classes = SchoolClass::where('is_active', true)->orderBy('level_id')->orderBy('sort_order')->get();

        return view('admin.notices.create', compact('levels', 'classes'));
    }

    public function store(Request $request, FileUploadService $uploadService): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'file' => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx'],
            'target_levels' => ['nullable', 'array'],
            'target_levels.*' => ['exists:school_levels,id'],
            'target_classes' => ['nullable', 'array'],
            'target_classes.*' => ['exists:classes,id'],
            'target_roles' => ['nullable', 'array'],
            'target_roles.*' => ['in:student,parent,teacher'],
            'is_published' => ['boolean'],
            'expires_at' => ['nullable', 'date', 'after:today'],
        ]);

        unset($validated['file']);

        $fileData = [];
        if ($request->hasFile('file')) {
            $schoolId = app('current.school')->id;
            $uploaded = $uploadService->uploadNoticeFile($request->file('file'), $schoolId);
            $fileData = [
                'file_url' => $uploaded['url'],
                'file_public_id' => $uploaded['public_id'],
                'file_name' => $request->file('file')->getClientOriginalName(),
            ];
        }

        $notice = Notice::create([
            ...$validated,
            ...$fileData,
            'created_by' => auth()->id(),
            'published_at' => ($validated['is_published'] ?? false) ? now() : null,
            'status' => 'approved',
        ]);

        if ($notice->is_published) {
            app(NotificationService::class)->notifyNoticePublished($notice);
        }

        return redirect()->route('admin.notices.index')
            ->with('success', __('Notice created.'));
    }

    public function edit(Notice $notice): View
    {
        abort_unless($notice->created_by === auth()->id(), 403);

        $levels = SchoolLevel::where('is_active', true)->orderBy('sort_order')->get();
        $classes = SchoolClass::where('is_active', true)->orderBy('level_id')->orderBy('sort_order')->get();

        return view('admin.notices.edit', compact('notice', 'levels', 'classes'));
    }

    public function update(Request $request, Notice $notice, FileUploadService $uploadService): RedirectResponse
    {
        abort_unless($notice->created_by === auth()->id(), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'file' => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx'],
            'target_levels' => ['nullable', 'array'],
            'target_levels.*' => ['exists:school_levels,id'],
            'target_classes' => ['nullable', 'array'],
            'target_classes.*' => ['exists:classes,id'],
            'target_roles' => ['nullable', 'array'],
            'target_roles.*' => ['in:student,parent,teacher'],
            'is_published' => ['boolean'],
            'expires_at' => ['nullable', 'date'],
        ]);

        unset($validated['file'], $validated['remove_file']);

        if (($validated['is_published'] ?? false) && ! $notice->published_at) {
            $validated['published_at'] = now();
        }

        // Handle file upload
        if ($request->hasFile('file')) {
            // Delete old file from Cloudinary
            if ($notice->file_public_id) {
                $uploadService->delete($notice->file_public_id);
            }
            $schoolId = app('current.school')->id;
            $uploaded = $uploadService->uploadNoticeFile($request->file('file'), $schoolId);
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

        $wasPublished = $notice->is_published;

        $notice->update($validated);

        // Notify if notice just became published
        if (! $wasPublished && $notice->is_published) {
            app(NotificationService::class)->notifyNoticePublished($notice);
        }

        return redirect()->route('admin.notices.index')
            ->with('success', __('Notice updated.'));
    }

    public function destroy(Notice $notice, FileUploadService $uploadService): RedirectResponse
    {
        abort_unless($notice->created_by === auth()->id(), 403);

        if ($notice->file_public_id) {
            $uploadService->delete($notice->file_public_id);
        }
        if ($notice->image_public_id) {
            $uploadService->delete($notice->image_public_id);
        }

        $notice->delete();

        return redirect()->route('admin.notices.index')
            ->with('success', __('Notice deleted.'));
    }

    public function unpublish(Notice $notice): RedirectResponse
    {
        abort_unless($notice->created_by === auth()->id(), 403);

        $notice->update([
            'is_published' => false,
        ]);

        return redirect()->route('admin.notices.index')
            ->with('success', __('Notice unpublished.'));
    }

    public function publish(Notice $notice): RedirectResponse
    {
        abort_unless($notice->created_by === auth()->id(), 403);
        abort_unless($notice->status === 'approved', 403);

        // Block publishing if the notice has expired
        if ($notice->expires_at && $notice->expires_at->isPast()) {
            return redirect()->route('admin.notices.index')
                ->with('error', __('Cannot publish — this notice has passed its expiry date.'));
        }

        $notice->update([
            'is_published' => true,
            'published_at' => $notice->published_at ?? now(),
        ]);

        app(NotificationService::class)->notifyNoticePublished($notice);

        return redirect()->route('admin.notices.index')
            ->with('success', __('Notice published.'));
    }
}
