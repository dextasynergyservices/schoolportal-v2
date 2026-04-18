<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditObserver
{
    public function created(Model $model): void
    {
        $this->log($model, 'created', null, $model->getAttributes());
    }

    public function updated(Model $model): void
    {
        $dirty = $model->getDirty();

        // Skip if only timestamps changed
        $ignoredKeys = ['updated_at', 'created_at', 'last_login_at', 'last_login_ip'];
        $meaningfulChanges = array_diff_key($dirty, array_flip($ignoredKeys));

        if (empty($meaningfulChanges)) {
            return;
        }

        $oldValues = array_intersect_key($model->getOriginal(), $dirty);

        // Never log password values
        unset($oldValues['password'], $dirty['password']);

        $this->log($model, 'updated', $oldValues, $dirty);
    }

    public function deleted(Model $model): void
    {
        $this->log($model, 'deleted', $model->getOriginal(), null);
    }

    private function log(Model $model, string $action, ?array $oldValues, ?array $newValues): void
    {
        // Don't log audit logs themselves (prevent infinite loop)
        if ($model instanceof AuditLog) {
            return;
        }

        $schoolId = $model->getAttribute('school_id');

        if (! $schoolId) {
            $school = app()->bound('current.school') ? app('current.school') : null;
            $schoolId = $school?->id;
        }

        if (! $schoolId) {
            return;
        }

        $entityType = class_basename($model);
        $actionName = strtolower($entityType).'.'.$action;

        AuditLog::withoutGlobalScopes()->create([
            'school_id' => $schoolId,
            'user_id' => auth()->id(),
            'action' => $actionName,
            'entity_type' => $entityType,
            'entity_id' => $model->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
