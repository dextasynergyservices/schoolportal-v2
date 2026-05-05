<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamAttempt extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'exam_id',
        'student_id',
        'school_id',
        'attempt_number',
        'score',
        'total_points',
        'percentage',
        'passed',
        'started_at',
        'submitted_at',
        'time_spent_seconds',
        'status',
        'tab_switches',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'attempt_number' => 'integer',
            'score' => 'integer',
            'total_points' => 'integer',
            'percentage' => 'decimal:2',
            'passed' => 'boolean',
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'time_spent_seconds' => 'integer',
            'tab_switches' => 'integer',
        ];
    }

    // ── Relationships ──

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ExamAnswer::class, 'attempt_id');
    }

    // ── Status Checks ──

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isSubmitted(): bool
    {
        return in_array($this->status, ['submitted', 'timed_out']);
    }

    public function isGrading(): bool
    {
        return $this->status === 'grading';
    }

    public function isGraded(): bool
    {
        return $this->status === 'graded';
    }

    public function isComplete(): bool
    {
        return in_array($this->status, ['submitted', 'timed_out', 'grading', 'graded']);
    }

    public function isFullyGraded(): bool
    {
        return in_array($this->status, ['submitted', 'timed_out', 'graded']);
    }

    // ── Helpers ──

    public function hasTimedOut(): bool
    {
        if (! $this->exam->time_limit_minutes || ! $this->isInProgress()) {
            return false;
        }

        return now()->diffInSeconds($this->started_at) >= ($this->exam->time_limit_minutes * 60);
    }

    public function remainingSeconds(): ?int
    {
        if (! $this->exam->time_limit_minutes || ! $this->isInProgress()) {
            return null;
        }

        $elapsed = (int) now()->diffInSeconds($this->started_at);
        $limit = $this->exam->time_limit_minutes * 60;

        return max(0, $limit - $elapsed);
    }

    public function answeredCount(): int
    {
        return $this->answers()->whereNotNull('selected_answer')->count();
    }
}
