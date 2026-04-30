<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class School extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'custom_domain',
        'domain_verified_at',
        'logo_url',
        'logo_public_id',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'country',
        'website',
        'motto',
        'ai_free_credits',
        'ai_purchased_credits',
        'ai_free_credits_reset_at',
        'ai_credits_total_purchased',
        'timezone',
        'is_active',
        'deactivation_reason',
        'deactivated_at',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'is_active' => 'boolean',
            'deactivated_at' => 'datetime',
            'domain_verified_at' => 'datetime',
            'ai_free_credits_reset_at' => 'date',
        ];
    }

    /**
     * Get total available AI credits (free + purchased).
     */
    public function aiCreditsBalance(): int
    {
        return $this->ai_free_credits + $this->ai_purchased_credits;
    }

    /**
     * Get a specific setting value with dot notation.
     */
    public function setting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Slug used for the platform-owner school (owns the super_admin user).
     * Excluded from tenant-facing lists and counts.
     */
    public const PLATFORM_SLUG = 'platform';

    public function isPlatform(): bool
    {
        return $this->slug === self::PLATFORM_SLUG;
    }

    public function scopeTenants(Builder $query): Builder
    {
        return $query->where('slug', '!=', self::PLATFORM_SLUG);
    }

    // ── Relationships ──

    public function levels(): HasMany
    {
        return $this->hasMany(SchoolLevel::class)->orderBy('sort_order');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class);
    }

    public function academicSessions(): HasMany
    {
        return $this->hasMany(AcademicSession::class);
    }

    public function currentSession(): ?AcademicSession
    {
        return $this->academicSessions()->where('is_current', true)->first();
    }

    public function terms(): HasMany
    {
        return $this->hasMany(Term::class);
    }

    public function currentTerm(): ?Term
    {
        return $this->terms()->where('is_current', true)->first();
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class)->orderBy('sort_order');
    }

    public function gradingScales(): HasMany
    {
        return $this->hasMany(GradingScale::class);
    }

    public function scoreComponents(): HasMany
    {
        return $this->hasMany(ScoreComponent::class)->orderBy('sort_order');
    }

    public function reportCardConfig(): HasOne
    {
        return $this->hasOne(ReportCardConfig::class);
    }

    // ── Content relationships (used by super-admin content overview) ──

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }

    public function games(): HasMany
    {
        return $this->hasMany(Game::class);
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(Result::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }
}
