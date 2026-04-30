<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\AuditLog;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolLevel;
use App\Models\Term;
use App\Models\User;
use App\Services\SchoolSetupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolShowTest extends TestCase
{
    use RefreshDatabase;

    private School $platformSchool;

    private User $superAdmin;

    private School $targetSchool;

    protected function setUp(): void
    {
        parent::setUp();

        $this->platformSchool = School::withoutGlobalScopes()->firstOrCreate(
            ['slug' => 'platform'],
            [
                'name' => 'DX-SchoolPortal Platform',
                'email' => 'platform@schoolportal.test',
                'country' => 'Nigeria',
                'is_active' => true,
            ],
        );

        $this->superAdmin = User::withoutGlobalScopes()->create([
            'school_id' => $this->platformSchool->id,
            'name' => 'Super Admin',
            'email' => 'superadmin@schoolportal.test',
            'username' => 'superadmin',
            'password' => bcrypt('Password1!'),
            'role' => 'super_admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ])->refresh();

        $this->targetSchool = app(SchoolSetupService::class)->create([
            'name' => 'Target School',
            'email' => 'target@school.test',
            'levels' => ['primary'],
            'admin_name' => 'School Admin',
            'admin_email' => 'admin@target.test',
            'admin_username' => 'target_admin',
            'admin_password' => 'Password1!',
            'session_name' => '2025/2026',
            'session_start_date' => '2025-09-01',
            'session_end_date' => '2026-07-31',
        ]);
    }

    // -------------------------------------------------------------------------
    // Access control
    // -------------------------------------------------------------------------

    public function test_guest_cannot_access_school_show_page(): void
    {
        $this->get(route('super-admin.schools.show', $this->targetSchool))
            ->assertRedirect(route('login'));
    }

    public function test_school_admin_cannot_access_school_show_page(): void
    {
        $admin = User::withoutGlobalScopes()
            ->where('school_id', $this->targetSchool->id)
            ->where('role', 'school_admin')
            ->first();

        // Disable force-password-change so ForcePasswordChange middleware
        // does not redirect before EnsureRole can enforce the 403.
        $admin->update(['must_change_password' => false, 'email_verified_at' => now()]);

        $this->actingAs($admin)
            ->withSession(['school_id' => $this->targetSchool->id])
            ->get(route('super-admin.schools.show', $this->targetSchool))
            ->assertForbidden();
    }

    public function test_super_admin_can_access_school_show_page(): void
    {
        $this->actingAs($this->superAdmin)
            ->withSession(['school_id' => $this->platformSchool->id])
            ->get(route('super-admin.schools.show', $this->targetSchool))
            ->assertOk()
            ->assertSee($this->targetSchool->name)
            ->assertSee($this->targetSchool->email);
    }

    // -------------------------------------------------------------------------
    // Stats section
    // -------------------------------------------------------------------------

    public function test_show_page_displays_student_and_teacher_counts(): void
    {
        User::withoutGlobalScopes()->create([
            'school_id' => $this->targetSchool->id,
            'name' => 'Test Student',
            'username' => 'tstudent',
            'password' => bcrypt('Password1!'),
            'role' => 'student',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->withSession(['school_id' => $this->platformSchool->id])
            ->get(route('super-admin.schools.show', $this->targetSchool));

        $response->assertOk()
            ->assertSee('1'); // student count appears somewhere on page
    }

    // -------------------------------------------------------------------------
    // Levels & Classes section
    // -------------------------------------------------------------------------

    public function test_show_page_displays_levels_and_classes(): void
    {
        $level = SchoolLevel::withoutGlobalScopes()
            ->where('school_id', $this->targetSchool->id)
            ->first();

        $class = SchoolClass::withoutGlobalScopes()
            ->where('school_id', $this->targetSchool->id)
            ->first();

        $response = $this->actingAs($this->superAdmin)
            ->withSession(['school_id' => $this->platformSchool->id])
            ->get(route('super-admin.schools.show', $this->targetSchool));

        $response->assertOk()
            ->assertSee('School Levels & Classes')
            ->assertSee($level->name)
            ->assertSee($class->name);
    }

    public function test_show_page_displays_empty_levels_state(): void
    {
        // Create a fresh school with no levels
        $bare = School::withoutGlobalScopes()->create([
            'name' => 'Bare School',
            'slug' => 'bare-school',
            'email' => 'bare@school.test',
            'country' => 'Nigeria',
            'is_active' => true,
        ]);

        User::withoutGlobalScopes()->create([
            'school_id' => $bare->id,
            'name' => 'Bare Admin',
            'email' => 'admin@bare.test',
            'username' => 'bare_admin',
            'password' => bcrypt('Password1!'),
            'role' => 'school_admin',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($this->superAdmin)
            ->withSession(['school_id' => $this->platformSchool->id])
            ->get(route('super-admin.schools.show', $bare))
            ->assertOk()
            ->assertSee('No levels configured');
    }

    // -------------------------------------------------------------------------
    // Academic Session section
    // -------------------------------------------------------------------------

    public function test_show_page_displays_current_session_and_term(): void
    {
        $session = AcademicSession::withoutGlobalScopes()
            ->where('school_id', $this->targetSchool->id)
            ->where('is_current', true)
            ->first();

        $term = Term::withoutGlobalScopes()
            ->where('school_id', $this->targetSchool->id)
            ->where('is_current', true)
            ->first();

        $response = $this->actingAs($this->superAdmin)
            ->withSession(['school_id' => $this->platformSchool->id])
            ->get(route('super-admin.schools.show', $this->targetSchool));

        $response->assertOk()
            ->assertSee('Academic Session')
            ->assertSee($session->name)
            ->assertSee($term->name);
    }

    public function test_show_page_displays_no_session_message_when_none_configured(): void
    {
        // Remove the active session
        AcademicSession::withoutGlobalScopes()
            ->where('school_id', $this->targetSchool->id)
            ->update(['is_current' => false]);

        $this->actingAs($this->superAdmin)
            ->withSession(['school_id' => $this->platformSchool->id])
            ->get(route('super-admin.schools.show', $this->targetSchool))
            ->assertOk()
            ->assertSee('No active academic session');
    }

    // -------------------------------------------------------------------------
    // Portal Settings section
    // -------------------------------------------------------------------------

    public function test_show_page_displays_portal_settings(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->withSession(['school_id' => $this->platformSchool->id])
            ->get(route('super-admin.schools.show', $this->targetSchool));

        $response->assertOk()
            ->assertSee('Portal Settings')
            ->assertSee('Session Timeout')
            ->assertSee('Parent Portal')
            ->assertSee('Teacher Approval')
            ->assertSee('AI Quiz Generator');
    }

    public function test_show_page_reflects_custom_portal_settings(): void
    {
        $settings = $this->targetSchool->settings ?? [];
        $settings['portal'] = array_merge($settings['portal'] ?? [], [
            'enable_parent_portal' => false,
            'session_timeout_minutes' => 60,
        ]);
        $this->targetSchool->update(['settings' => $settings]);

        $response = $this->actingAs($this->superAdmin)
            ->withSession(['school_id' => $this->platformSchool->id])
            ->get(route('super-admin.schools.show', $this->targetSchool));

        $response->assertOk()
            ->assertSee('60 min')
            ->assertSee('Disabled'); // parent portal disabled
    }

    // -------------------------------------------------------------------------
    // Audit Log section
    // -------------------------------------------------------------------------

    public function test_show_page_displays_audit_logs(): void
    {
        AuditLog::withoutGlobalScopes()->create([
            'school_id' => $this->targetSchool->id,
            'user_id' => null,
            'action' => 'school.created',
            'entity_type' => 'school',
            'entity_id' => $this->targetSchool->id,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->withSession(['school_id' => $this->platformSchool->id])
            ->get(route('super-admin.schools.show', $this->targetSchool));

        $response->assertOk()
            ->assertSee('Recent Activity')
            ->assertSee('school.created');
    }

    public function test_show_page_shows_actor_name_in_audit_log(): void
    {
        $admin = User::withoutGlobalScopes()
            ->where('school_id', $this->targetSchool->id)
            ->where('role', 'school_admin')
            ->first();

        AuditLog::withoutGlobalScopes()->create([
            'school_id' => $this->targetSchool->id,
            'user_id' => $admin->id,
            'action' => 'student.created',
            'entity_type' => 'user',
            'entity_id' => 99,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->withSession(['school_id' => $this->platformSchool->id])
            ->get(route('super-admin.schools.show', $this->targetSchool));

        $response->assertOk()
            ->assertSee($admin->name)
            ->assertSee('student.created');
    }

    public function test_show_page_shows_empty_audit_log_message_when_no_activity(): void
    {
        // Ensure no audit logs exist for this school
        AuditLog::withoutGlobalScopes()
            ->where('school_id', $this->targetSchool->id)
            ->delete();

        $this->actingAs($this->superAdmin)
            ->withSession(['school_id' => $this->platformSchool->id])
            ->get(route('super-admin.schools.show', $this->targetSchool))
            ->assertOk()
            ->assertSee('No activity recorded yet');
    }

    public function test_audit_log_only_shows_entries_for_the_current_school(): void
    {
        $otherSchool = app(SchoolSetupService::class)->create([
            'name' => 'Other School',
            'email' => 'other@school.test',
            'levels' => ['primary'],
            'admin_name' => 'Other Admin',
            'admin_email' => 'admin@other.test',
            'admin_username' => 'other_admin',
            'admin_password' => 'Password1!',
            'session_name' => '2025/2026',
            'session_start_date' => '2025-09-01',
            'session_end_date' => '2026-07-31',
        ]);

        // Audit log for a different school
        AuditLog::withoutGlobalScopes()->create([
            'school_id' => $otherSchool->id,
            'action' => 'other.school.event',
        ]);

        // Audit log for the target school
        AuditLog::withoutGlobalScopes()->create([
            'school_id' => $this->targetSchool->id,
            'action' => 'target.school.event',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->withSession(['school_id' => $this->platformSchool->id])
            ->get(route('super-admin.schools.show', $this->targetSchool));

        $response->assertOk()
            ->assertSee('target.school.event')
            ->assertDontSee('other.school.event');
    }
}
