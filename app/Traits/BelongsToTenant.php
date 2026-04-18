<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\School;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Automatically scopes all queries to the current school (tenant).
 * Also auto-sets school_id on creation.
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder): void {
            if (app()->bound('current.school') && $school = app('current.school')) {
                $builder->where($builder->getModel()->getTable().'.school_id', $school->id);
            }
        });

        static::creating(function (Model $model): void {
            if (! $model->school_id && app()->bound('current.school') && $school = app('current.school')) {
                $model->school_id = $school->id;
            }
        });
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
