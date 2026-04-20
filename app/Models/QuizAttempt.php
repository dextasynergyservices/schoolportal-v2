<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizAttempt extends Model
{
    use BelongsToTenant, HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'quiz_id',
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
    ];

    protected function casts(): array
    {
        return [
            'quiz_id' => 'integer',
            'student_id' => 'integer',
            'school_id' => 'integer',
            'percentage' => 'decimal:2',
            'passed' => 'boolean',
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(QuizAnswer::class, 'attempt_id');
    }
}
