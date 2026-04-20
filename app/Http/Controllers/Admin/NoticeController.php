<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use App\Models\SchoolLevel;
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

        return view('admin.notices.create', compact('levels'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'target_levels' => ['nullable', 'array'],
            'target_levels.*' => ['exists:school_levels,id'],
            'target_roles' => ['nullable', 'array'],
            'target_roles.*' => ['in:student,parent,teacher'],
            'is_published' => ['boolean'],
            'expires_at' => ['nullable', 'date', 'after:today'],
        ]);

        Notice::create([
            ...$validated,
            'created_by' => auth()->id(),
            'published_at' => ($validated['is_published'] ?? false) ? now() : null,
            'status' => 'approved',
        ]);

        return redirect()->route('admin.notices.index')
            ->with('success', __('Notice created.'));
    }

    public function edit(Notice $notice): View
    {
        $levels = SchoolLevel::where('is_active', true)->orderBy('sort_order')->get();

        return view('admin.notices.edit', compact('notice', 'levels'));
    }

    public function update(Request $request, Notice $notice): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'target_levels' => ['nullable', 'array'],
            'target_levels.*' => ['exists:school_levels,id'],
            'target_roles' => ['nullable', 'array'],
            'target_roles.*' => ['in:student,parent,teacher'],
            'is_published' => ['boolean'],
            'expires_at' => ['nullable', 'date'],
        ]);

        if (($validated['is_published'] ?? false) && ! $notice->published_at) {
            $validated['published_at'] = now();
        }

        $notice->update($validated);

        return redirect()->route('admin.notices.index')
            ->with('success', __('Notice updated.'));
    }

    public function destroy(Notice $notice): RedirectResponse
    {
        $notice->delete();

        return redirect()->route('admin.notices.index')
            ->with('success', __('Notice deleted.'));
    }
}
