<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\AuditLog;

/**
 * Add this trait to any model that should be audit-logged.
 * Registers created/updated/deleted event listeners directly (no observer class).
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            static::logAudit($model, 'created', null, $model->getAttributes());
        });

        static::updated(function ($model) {
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

            static::logAudit($model, 'updated', $oldValues, $dirty);
        });

        static::deleted(function ($model) {
            static::logAudit($model, 'deleted', $model->getOriginal(), null);
        });
    }

    protected static function logAudit($model, string $action, ?array $oldValues, ?array $newValues): void
    {
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
