<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamAccessReset extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id',
        'exam_id',
        'student_id',
        'granted_by',
        'attempt_id',
        'available_from',
        'available_until',
        'used_at',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'school_id' => 'integer',
            'exam_id' => 'integer',
            'student_id' => 'integer',
            'granted_by' => 'integer',
            'attempt_id' => 'integer',
            'available_from' => 'datetime',
            'available_until' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class);
    }

    public function scopeActive($query)
    {
        return $query
            ->whereNull('used_at')
            ->where('available_from', '<=', now())
            ->where('available_until', '>=', now());
    }

    public function isActive(): bool
    {
        return $this->used_at === null
            && $this->available_from->lte(now())
            && $this->available_until->gte(now());
    }
}
