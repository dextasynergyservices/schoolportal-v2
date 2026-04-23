<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PlatformAnnouncement;
use App\Models\PlatformAnnouncementRead;
use App\Models\SchoolAnnouncementDismissal;
use Illuminate\Http\RedirectResponse;

class AnnouncementReadController extends Controller
{
    /** School admin marks a platform announcement as read */
    public function markPlatformRead(PlatformAnnouncement $announcement): RedirectResponse
    {
        $user = auth()->user();

        PlatformAnnouncementRead::firstOrCreate(
            [
                'announcement_id' => $announcement->id,
                'school_id' => $user->school_id,
            ],
            [
                'read_by' => $user->id,
                'read_at' => now(),
            ]
        );

        return back()->with('success', __('Announcement marked as read.'));
    }

    /** Any user dismisses a school announcement */
    public function dismissSchool(int $announcementId): RedirectResponse
    {
        SchoolAnnouncementDismissal::firstOrCreate(
            [
                'announcement_id' => $announcementId,
                'user_id' => auth()->id(),
            ],
            [
                'dismissed_at' => now(),
            ]
        );

        return back();
    }
}
