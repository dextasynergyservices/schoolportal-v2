<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\PlatformAnnouncement;
use App\Models\School;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(): View
    {
        $announcements = PlatformAnnouncement::with('creator')
            ->withCount('reads')
            ->latest()
            ->paginate(15);

        $totalSchools = School::tenants()->where('is_active', true)->count();

        return view('super-admin.announcements.index', compact('announcements', 'totalSchools'));
    }

    public function create(): View
    {
        return view('super-admin.announcements.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'priority' => ['required', 'in:info,warning,critical'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        PlatformAnnouncement::create([
            ...$data,
            'is_active' => true,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('super-admin.announcements.index')
            ->with('success', __('Announcement published successfully.'));
    }

    public function show(PlatformAnnouncement $announcement): View
    {
        $announcement->load(['creator', 'reads.school', 'reads.reader']);

        $allSchools = School::tenants()->where('is_active', true)->orderBy('name')->get();
        $readSchoolIds = $announcement->reads->pluck('school_id')->toArray();

        $readSchools = $allSchools->filter(fn ($s) => in_array($s->id, $readSchoolIds));
        $unreadSchools = $allSchools->filter(fn ($s) => ! in_array($s->id, $readSchoolIds));

        return view('super-admin.announcements.show', compact(
            'announcement',
            'readSchools',
            'unreadSchools',
        ));
    }

    public function edit(PlatformAnnouncement $announcement): View
    {
        return view('super-admin.announcements.edit', compact('announcement'));
    }

    public function update(Request $request, PlatformAnnouncement $announcement): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'priority' => ['required', 'in:info,warning,critical'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        $announcement->update($data);

        return redirect()->route('super-admin.announcements.show', $announcement)
            ->with('success', __('Announcement updated.'));
    }

    public function deactivate(PlatformAnnouncement $announcement): RedirectResponse
    {
        $announcement->update(['is_active' => false]);

        return back()->with('success', __('Announcement deactivated.'));
    }

    public function activate(PlatformAnnouncement $announcement): RedirectResponse
    {
        $announcement->update(['is_active' => true]);

        return back()->with('success', __('Announcement activated.'));
    }

    public function destroy(PlatformAnnouncement $announcement): RedirectResponse
    {
        $announcement->delete();

        return redirect()->route('super-admin.announcements.index')
            ->with('success', __('Announcement deleted.'));
    }
}
