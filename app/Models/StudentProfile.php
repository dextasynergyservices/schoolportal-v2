<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentProfile extends Model
{
    use BelongsToTenant, HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'school_id',
        'class_id',
        'admission_number',
        'date_of_birth',
        'address',
        'blood_group',
        'medical_notes',
        'enrolled_session_id',
        'enrolled_term_id',
        'login_streak',
        'best_login_streak',
        'last_streak_date',
        'quiz_pass_streak',
        'best_quiz_pass_streak',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'school_id' => 'integer',
            'class_id' => 'integer',
            'enrolled_session_id' => 'integer',
            'enrolled_term_id' => 'integer',
            'date_of_birth' => 'date',
            'login_streak' => 'integer',
            'best_login_streak' => 'integer',
            'last_streak_date' => 'date',
            'quiz_pass_streak' => 'integer',
            'best_quiz_pass_streak' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function enrolledSession(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'enrolled_session_id');
    }

    public function enrolledTerm(): BelongsTo
    {
        return $this->belongsTo(Term::class, 'enrolled_term_id');
    }

    public function scopeEnrolledByTerm(Builder $query, Term $term): Builder
    {
        $sessionStart = $term->session?->start_date ?? $term->session()->value('start_date');

        return $query->where(function (Builder $eligible) use ($term, $sessionStart): void {
            $eligible->whereNull('enrolled_session_id')
                ->orWhereHas('enrolledSession', fn (Builder $session) => $session->whereDate('start_date', '<', $sessionStart))
                ->orWhere(function (Builder $sameSession) use ($term): void {
                    $sameSession->where('enrolled_session_id', $term->session_id)
                        ->where(function (Builder $eligibleTerm) use ($term): void {
                            $eligibleTerm->whereNull('enrolled_term_id')
                                ->orWhereHas('enrolledTerm', fn (Builder $enrolledTerm) => $enrolledTerm->where('term_number', '<=', $term->term_number));
                        });
                });
        });
    }

    public function scopeEnrolledBySession(Builder $query, AcademicSession $session): Builder
    {
        return $query->where(function (Builder $eligible) use ($session): void {
            $eligible->whereNull('enrolled_session_id')
                ->orWhereHas('enrolledSession', fn (Builder $enrolledSession) => $enrolledSession->whereDate('start_date', '<=', $session->start_date));
        });
    }
}
