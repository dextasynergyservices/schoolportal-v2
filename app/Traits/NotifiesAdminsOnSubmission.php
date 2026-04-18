<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\TeacherAction;
use App\Models\User;
use App\Notifications\SubmissionPendingApproval;

trait NotifiesAdminsOnSubmission
{
    /**
     * Notify school admins (with email) that a teacher submission is pending approval.
     */
    protected function notifyAdminsOfPendingSubmission(TeacherAction $action, User $teacher): void
    {
        $admins = User::withoutGlobalScopes()
            ->where('school_id', $teacher->school_id)
            ->where('role', 'school_admin')
            ->where('is_active', true)
            ->whereNotNull('email')
            ->get();

        foreach ($admins as $admin) {
            $admin->notify(new SubmissionPendingApproval($action, $teacher->name));
        }
    }
}
