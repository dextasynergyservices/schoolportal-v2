<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolLevel;
use App\Models\Term;
use App\Models\User;
use App\Services\SchoolSetupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private School $schoolA;

    private School $schoolB;

    protected function setUp(): void
    {
        parent::setUp();

        $setup = app(SchoolSetupService::class);

        $this->schoolA = $setup->create([
            'name' => 'School A',
            'email' => 'a@example.test',
            'levels' => ['primary'],
            'admin_name' => 'Admin A',
            'admin_email' => 'admin-a@example.test',
            'admin_username' => 'admin_a',
            'admin_password' => 'password123',
            'session_name' => '2025/2026',
            'session_start_date' => '2025-09-01',
            'session_end_date' => '2026-07-31',
        ]);

        $this->schoolB = $setup->create([
            'name' => 'School B',
            'email' => 'b@example.test',
            'levels' => ['primary'],
            'admin_name' => 'Admin B',
            'admin_email' => 'admin-b@example.test',
            'admin_username' => 'admin_b',
            'admin_password' => 'password123',
            'session_name' => '2025/2026',
            'session_start_date' => '2025-09-01',
            'session_end_date' => '2026-07-31',
        ]);
    }

    public function test_tenant_scope_filters_users_to_current_school(): void
    {
        app()->instance('current.school', $this->schoolA);

        $usernames = User::query()->pluck('username')->all();

        $this->assertContains('admin_a', $usernames);
        $this->assertNotContains('admin_b', $usernames);
    }

    public function test_school_b_data_still_exists_without_global_scope(): void
    {
        app()->instance('current.school', $this->schoolA);

        $this->assertTrue(
            User::withoutGlobalScopes()->where('username', 'admin_b')->exists(),
            'School B admin should exist in DB — the scope filters, does not delete.'
        );
    }

    public function test_tenant_scope_applies_to_classes_levels_sessions_and_terms(): void
    {
        app()->instance('current.school', $this->schoolA);

        $this->assertSame(
            $this->schoolA->id,
            SchoolLevel::query()->value('school_id'),
        );
        $this->assertSame(
            $this->schoolA->id,
            SchoolClass::query()->value('school_id'),
        );
        $this->assertSame(
            $this->schoolA->id,
            AcademicSession::query()->value('school_id'),
        );
        $this->assertSame(
            $this->schoolA->id,
            Term::query()->value('school_id'),
        );

        $this->assertSame(
            $this->schoolA->levels()->count(),
            SchoolLevel::query()->count(),
        );
        $this->assertSame(
            $this->schoolA->classes()->count(),
            SchoolClass::query()->count(),
        );
    }

    public function test_switching_tenant_switches_visible_data(): void
    {
        app()->instance('current.school', $this->schoolA);
        $this->assertSame('admin_a', User::query()->value('username'));

        app()->instance('current.school', $this->schoolB);
        $this->assertSame('admin_b', User::query()->value('username'));
    }

    public function test_without_current_school_scope_is_inert(): void
    {
        app()->forgetInstance('current.school');

        $this->assertSame(
            User::withoutGlobalScopes()->count(),
            User::query()->count(),
            'With no bound tenant, the scope should not filter anything.',
        );
    }

    public function test_auto_sets_school_id_on_create_when_tenant_is_bound(): void
    {
        app()->instance('current.school', $this->schoolB);

        $user = User::create([
            'name' => 'Auto Tenant',
            'username' => 'auto_tenant',
            'email' => 'auto@example.test',
            'password' => 'password123',
            'role' => 'teacher',
        ]);

        $this->assertSame($this->schoolB->id, $user->school_id);
    }
}
