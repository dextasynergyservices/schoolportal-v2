<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use App\Services\SchoolSetupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * S6 — System Health monitoring page tests.
 *
 * Covers:
 *  - Access control (guest, school_admin → forbidden)
 *  - Page renders 200 with expected sections
 *  - Health score present (0-100)
 *  - Core service cards rendered
 *  - Environment info present
 */
class SuperAdminSystemHealthTest extends TestCase
{
    use RefreshDatabase;

    private School $platformSchool;

    private User $superAdmin;

    private User $schoolAdmin;

    // ─────────────────────────────────────────────────────────────────────────

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

        $targetSchool = app(SchoolSetupService::class)->create([
            'name' => 'Test School',
            'email' => 'info@test-school.test',
            'levels' => ['primary'],
            'admin_name' => 'School Admin',
            'admin_email' => 'admin@test-school.test',
            'admin_username' => 'test_school_admin',
            'admin_password' => 'Password1!',
            'session_name' => '2025/2026',
            'session_start_date' => '2025-09-01',
            'session_end_date' => '2026-07-31',
        ]);

        $this->schoolAdmin = User::withoutGlobalScopes()
            ->where('school_id', $targetSchool->id)
            ->where('role', 'school_admin')
            ->firstOrFail();

        $this->schoolAdmin->update([
            'must_change_password' => false,
            'email_verified_at' => now(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Access Control
    // ─────────────────────────────────────────────────────────────────────────

    public function test_guest_cannot_access_system_health(): void
    {
        $this->get(route('super-admin.system-health'))
            ->assertRedirectToRoute('login');
    }

    public function test_school_admin_cannot_access_system_health(): void
    {
        $this->actingAs($this->schoolAdmin)
            ->get(route('super-admin.system-health'))
            ->assertForbidden();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Page Rendering
    // ─────────────────────────────────────────────────────────────────────────

    public function test_system_health_returns_200_for_super_admin(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('super-admin.system-health'))
            ->assertOk();
    }

    public function test_system_health_contains_core_service_section(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('super-admin.system-health'))
            ->assertOk()
            ->assertSee('Core Services')
            ->assertSee('Database')
            ->assertSee('Cache')
            ->assertSee('Queue')
            ->assertSee('Storage');
    }

    public function test_system_health_contains_environment_section(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('super-admin.system-health'))
            ->assertOk()
            ->assertSee('Runtime')
            ->assertSee('PHP Version')
            ->assertSee('Laravel Version');
    }

    public function test_system_health_contains_writable_directories_section(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('super-admin.system-health'))
            ->assertOk()
            ->assertSee('Writable Directories');
    }

    public function test_system_health_contains_production_checklist(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('super-admin.system-health'))
            ->assertOk()
            ->assertSee('Production Readiness');
    }

    public function test_system_health_shows_platform_stats(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('super-admin.system-health'))
            ->assertOk()
            ->assertSee('schools active')
            ->assertSee('users active');
    }

    public function test_system_health_shows_current_php_version(): void
    {
        $phpVersion = PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;

        $this->actingAs($this->superAdmin)
            ->get(route('super-admin.system-health'))
            ->assertOk()
            ->assertSee($phpVersion);
    }

    public function test_system_health_view_data_has_required_keys(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('super-admin.system-health'));

        $response->assertOk();
        $response->assertViewHasAll([
            'healthScore',
            'healthStatus',
            'passedChecks',
            'totalChecks',
            'dbStatus',
            'dbLatencyMs',
            'cacheStatus',
            'cacheDriver',
            'queueDriver',
            'sessionDriver',
            'pendingJobs',
            'failedJobs',
            'diskTotal',
            'diskFree',
            'diskUsed',
            'diskUsedPercent',
            'phpVersion',
            'laravelVersion',
            'env',
            'debugMode',
            'timezone',
            'extensions',
            'paths',
            'totalSchools',
            'activeSchools',
            'totalUsers',
            'activeUsers',
            'recentLogins',
        ]);
    }

    public function test_health_score_is_between_0_and_100(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('super-admin.system-health'));

        $response->assertOk();
        $score = $response->viewData('healthScore');

        $this->assertIsInt($score);
        $this->assertGreaterThanOrEqual(0, $score);
        $this->assertLessThanOrEqual(100, $score);
    }

    public function test_health_status_is_valid_enum(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('super-admin.system-health'));

        $response->assertOk();
        $this->assertContains($response->viewData('healthStatus'), ['healthy', 'degraded', 'critical']);
    }

    public function test_passed_checks_does_not_exceed_total_checks(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('super-admin.system-health'));

        $response->assertOk();
        $this->assertLessThanOrEqual(
            $response->viewData('totalChecks'),
            $response->viewData('passedChecks')
        );
    }

    public function test_db_is_reachable_in_test_environment(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('super-admin.system-health'));

        $response->assertOk();
        $this->assertSame('ok', $response->viewData('dbStatus'));
    }

    public function test_extensions_array_contains_required_extensions(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('super-admin.system-health'));

        $response->assertOk();
        $extensions = $response->viewData('extensions');

        $this->assertIsArray($extensions);
        $this->assertArrayHasKey('pdo', $extensions);
        $this->assertArrayHasKey('mbstring', $extensions);
        $this->assertArrayHasKey('openssl', $extensions);
    }

    public function test_paths_array_contains_storage_path(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('super-admin.system-health'));

        $response->assertOk();
        $paths = $response->viewData('paths');

        $this->assertIsArray($paths);
        // At least one path entry should contain 'storage'
        $hasStorage = collect($paths)->keys()->contains(fn ($k) => str_contains($k, 'storage'));
        $this->assertTrue($hasStorage);
    }
}
