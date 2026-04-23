<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\TeacherAction;
use App\Models\User;
use App\Notifications\SubmissionPendingApproval;
use App\Notifications\SubmissionPendingNotification;

trait NotifiesAdminsOnSubmission
{
    /**
     * Notify school admins (via email and database) that a teacher submission is pending approval.
     */
    protected function notifyAdminsOfPendingSubmission(TeacherAction $action, User $teacher): void
    {
        $admins = User::withoutGlobalScopes()
            ->where('school_id', $teacher->school_id)
            ->where('role', 'school_admin')
            ->where('is_active', true)
            ->get();

        foreach ($admins as $admin) {
            // Database notification (appears in bell icon)
            $admin->notify(new SubmissionPendingNotification(
                entityType: $action->entity_type,
                teacherName: $teacher->name,
                entityId: $action->entity_id,
            ));

            // Email notification (only if admin has email)
            if ($admin->email) {
                $admin->notify(new SubmissionPendingApproval($action, $teacher->name));
            }
        }
    }
}
