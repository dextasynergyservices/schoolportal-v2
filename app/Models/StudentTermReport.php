<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentTermReport extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id',
        'student_id',
        'class_id',
        'session_id',
        'term_id',
        'report_type',
        'subject_scores_snapshot',
        'total_weighted_score',
        'average_weighted_score',
        'subjects_count',
        'position',
        'out_of',
        'psychomotor_ratings',
        'affective_ratings',
        'attendance_present',
        'attendance_absent',
        'attendance_total',
        'teacher_comment',
        'teacher_id',
        'principal_comment',
        'signature_url',
        'signature_public_id',
        'status',
        'approved_by',
        'approved_at',
        'published_at',
        'finalized_at',
    ];

    protected function casts(): array
    {
        return [
            'subject_scores_snapshot' => 'array',
            'report_type' => 'string',
            'total_weighted_score' => 'decimal:2',
            'average_weighted_score' => 'decimal:2',
            'subjects_count' => 'integer',
            'position' => 'integer',
            'out_of' => 'integer',
            'psychomotor_ratings' => 'array',
            'affective_ratings' => 'array',
            'attendance_present' => 'integer',
            'attendance_absent' => 'integer',
            'attendance_total' => 'integer',
            'approved_at' => 'datetime',
            'published_at' => 'datetime',
            'finalized_at' => 'datetime',
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

    public function session(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'session_id');
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ── Status checks ──

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isPendingApproval(): bool
    {
        return $this->status === 'pending_approval';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isFinalized(): bool
    {
        return $this->finalized_at !== null;
    }

    // ── Report type checks ──

    public function isMidterm(): bool
    {
        return $this->report_type === 'midterm';
    }

    public function isFullTerm(): bool
    {
        return $this->report_type === 'full_term';
    }

    public function isSession(): bool
    {
        return $this->report_type === 'session';
    }
}
