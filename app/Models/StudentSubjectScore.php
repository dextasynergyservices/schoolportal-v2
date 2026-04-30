<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentSubjectScore extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id',
        'student_id',
        'class_id',
        'subject_id',
        'session_id',
        'term_id',
        'score_component_id',
        'score',
        'max_score',
        'source_type',
        'source_exam_id',
        'source_attempt_id',
        'is_locked',
        'entered_by',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'max_score' => 'integer',
            'is_locked' => 'boolean',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'session_id');
    }

    public function scoreComponent(): BelongsTo
    {
        return $this->belongsTo(ScoreComponent::class);
    }

    public function sourceExam(): BelongsTo
    {
        return $this->belongsTo(Exam::class, 'source_exam_id');
    }

    public function sourceAttempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class, 'source_attempt_id');
    }

    public function enteredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entered_by');
    }

    /**
     * Calculate weighted score for this component: (score / max_score) * weight
     */
    public function weightedScore(): float
    {
        if ($this->score === null || $this->max_score <= 0) {
            return 0.0;
        }

        $weight = $this->scoreComponent?->weight ?? 0;

        return round(($this->score / $this->max_score) * $weight, 2);
    }
}
