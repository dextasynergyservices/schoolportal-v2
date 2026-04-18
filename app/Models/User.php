<?php

declare(strict_types=1);

namespace App\Models;

use App\Notifications\ResetPassword;
use App\Notifications\VerifyEmail;
use App\Traits\Auditable;
use App\Traits\BelongsToTenant;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use Auditable, BelongsToTenant, HasFactory, Notifiable, TwoFactorAuthenticatable;

    protected $fillable = [
        'school_id',
        'name',
        'email',
        'username',
        'password',
        'role',
        'level_id',
        'avatar_url',
        'phone',
        'gender',
        'is_active',
        'deactivation_reason',
        'deactivated_at',
        'must_change_password',
        'email_verified_at',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'deactivated_at' => 'datetime',
            'must_change_password' => 'boolean',
        ];
    }

    // ── Role Checks ──

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPassword($token));
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmail);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isSchoolAdmin(): bool
    {
        return $this->role === 'school_admin';
    }

    public function isTeacher(): bool
    {
        return $this->role === 'teacher';
    }

    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    public function isParent(): bool
    {
        return $this->role === 'parent';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['super_admin', 'school_admin']);
    }

    // ── Relationships ──

    public function level(): BelongsTo
    {
        return $this->belongsTo(SchoolLevel::class, 'level_id');
    }

    public function studentProfile(): HasOne
    {
        return $this->hasOne(StudentProfile::class);
    }

    public function parentProfile(): HasOne
    {
        return $this->hasOne(ParentProfile::class);
    }

    /**
     * Classes this teacher is assigned to.
     */
    public function assignedClasses(): HasMany
    {
        return $this->hasMany(SchoolClass::class, 'teacher_id');
    }

    /**
     * Children linked to this parent.
     */
    public function children(): HasMany
    {
        return $this->hasMany(ParentStudent::class, 'parent_id');
    }

    /**
     * Parents linked to this student.
     */
    public function parents(): HasMany
    {
        return $this->hasMany(ParentStudent::class, 'student_id');
    }

    // ── Helpers ──

    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }
}
