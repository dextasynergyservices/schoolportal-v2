<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Game extends Model
{
    use Auditable, BelongsToTenant, HasFactory;

    protected $fillable = [
        'school_id',
        'class_id',
        'session_id',
        'term_id',
        'title',
        'description',
        'game_type',
        'source_type',
        'source_document_url',
        'source_document_public_id',
        'source_prompt',
        'game_data',
        'difficulty',
        'time_limit_minutes',
        'is_published',
        'published_at',
        'expires_at',
        'created_by',
        'approved_by',
        'approved_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'school_id' => 'integer',
            'class_id' => 'integer',
            'session_id' => 'integer',
            'term_id' => 'integer',
            'created_by' => 'integer',
            'approved_by' => 'integer',
            'game_data' => 'array',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function plays(): HasMany
    {
        return $this->hasMany(GamePlay::class);
    }

    public function latestTeacherAction(): HasOne
    {
        return $this->hasOne(TeacherAction::class, 'entity_id')
            ->where('entity_type', 'game')
            ->latestOfMany();
    }

    // ── Scopes ──

    public function scopePublished($query)
    {
        return $query->where('is_published', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            });
    }

    public function scopeForClass($query, int $classId)
    {
        return $query->where('class_id', $classId);
    }

    // ── Helpers ──

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isAvailableForStudent(): bool
    {
        return $this->is_published && ! $this->isExpired();
    }

    public function bestPlayForStudent(int $studentId): ?GamePlay
    {
        return $this->plays()
            ->where('student_id', $studentId)
            ->where('completed', true)
            ->orderByDesc('percentage')
            ->first();
    }

    public function playCountForStudent(int $studentId): int
    {
        return $this->plays()
            ->where('student_id', $studentId)
            ->where('completed', true)
            ->count();
    }

    public function gameTypeLabel(): string
    {
        return match ($this->game_type) {
            'memory_match' => 'Memory Match',
            'word_scramble' => 'Word Scramble',
            'quiz_race' => 'Quiz Race',
            'flashcard' => 'Flashcard Study',
            default => $this->game_type,
        };
    }
}
