<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolLevel;
use App\Models\User;
use App\Services\SchoolSetupService;

/**
 * Sets up a full school context for feature tests that need tenant resolution.
 *
 * Provides $this->school, $this->admin, $this->level, $this->class
 * and binds 'current.school' in the container.
 */
trait WithSchoolContext
{
    protected School $school;

    protected User $admin;

    protected SchoolLevel $level;

    protected SchoolClass $class;

    protected function setUpSchoolContext(): void
    {
        $this->school = app(SchoolSetupService::class)->create([
            'name' => 'Test School',
            'email' => 'test@school.test',
            'levels' => ['primary'],
            'admin_name' => 'Test Admin',
            'admin_email' => 'admin@school.test',
            'admin_username' => 'test_admin',
            'admin_password' => 'Password1!',
            'session_name' => '2025/2026',
            'session_start_date' => '2025-09-01',
            'session_end_date' => '2026-07-31',
        ]);

        $this->admin = User::withoutGlobalScopes()
            ->where('username', 'test_admin')
            ->first();
        $this->admin->update([
            'must_change_password' => false,
            'email_verified_at' => now(),
        ]);

        app()->instance('current.school', $this->school);

        $this->level = SchoolLevel::withoutGlobalScopes()
            ->where('school_id', $this->school->id)
            ->first();

        $this->class = SchoolClass::withoutGlobalScopes()
            ->where('school_id', $this->school->id)
            ->first();
    }

    /**
     * Create a user with a given role in the test school.
     */
    protected function createSchoolUser(string $role, array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'school_id' => $this->school->id,
            'role' => $role,
            'level_id' => $this->level->id,
        ], $attributes));
    }
}
