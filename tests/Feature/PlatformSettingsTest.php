<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\PlatformSetting;
use App\Models\School;
use App\Models\User;
use App\Services\SchoolSetupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformSettingsTest extends TestCase
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

    // ── Access control ────────────────────────────────────────────────────────

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('super-admin.settings.index'))
            ->assertRedirect(route('login'));
    }

    public function test_school_admin_gets_403(): void
    {
        $admin = User::withoutGlobalScopes()
            ->where('school_id', $this->targetSchool->id)
            ->where('role', 'school_admin')
            ->firstOrFail();
        $admin->update(['must_change_password' => false, 'email_verified_at' => now()]);

        $this->actingAs($admin)
            ->get(route('super-admin.settings.index'))
            ->assertForbidden();
    }

    public function test_super_admin_can_view_settings_page(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('super-admin.settings.index'))
            ->assertOk()
            ->assertSee('platform_name')
            ->assertSee('default_free_ai_credits');
    }

    public function test_settings_page_shows_default_values(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('super-admin.settings.index'))
            ->assertOk()
            ->assertSee('DX-SchoolPortal')   // default platform name
            ->assertSee('15');                // default free credits
    }

    // ── Updating settings ─────────────────────────────────────────────────────

    public function test_super_admin_can_update_settings(): void
    {
        $this->actingAs($this->superAdmin)
            ->put(route('super-admin.settings.update'), [
                'platform_name' => 'My Cool Platform',
                'default_free_ai_credits' => 20,
                'maintenance_mode' => '0',
                'maintenance_message' => '',
                'allowed_file_types' => 'pdf,png,jpg',
                'max_upload_size_mb' => 15,
                'credit_price_per_5' => 1500,
            ])
            ->assertRedirect();

        $this->assertSame('My Cool Platform', PlatformSetting::get('platform_name'));
        $this->assertSame(20, PlatformSetting::get('default_free_ai_credits'));
        $this->assertSame(15, PlatformSetting::get('max_upload_size_mb'));
        $this->assertSame(1500, PlatformSetting::get('credit_price_per_5'));
        $this->assertSame('pdf,png,jpg', PlatformSetting::get('allowed_file_types'));
    }

    public function test_update_shows_success_flash(): void
    {
        $this->actingAs($this->superAdmin)
            ->put(route('super-admin.settings.update'), [
                'platform_name' => 'Updated Name',
                'default_free_ai_credits' => 10,
                'maintenance_mode' => '0',
                'maintenance_message' => '',
                'allowed_file_types' => 'pdf,doc',
                'max_upload_size_mb' => 10,
                'credit_price_per_5' => 1000,
            ])
            ->assertSessionHas('success');
    }

    public function test_validation_rejects_invalid_data(): void
    {
        $this->actingAs($this->superAdmin)
            ->put(route('super-admin.settings.update'), [
                'platform_name' => '',                // required
                'default_free_ai_credits' => 999,              // max 100
                'max_upload_size_mb' => 999,              // max 100
                'credit_price_per_5' => 10,               // min 100
                'allowed_file_types' => 'pdf',
            ])
            ->assertSessionHasErrors(['platform_name', 'default_free_ai_credits', 'max_upload_size_mb', 'credit_price_per_5']);
    }

    public function test_school_admin_cannot_update_settings(): void
    {
        $admin = User::withoutGlobalScopes()
            ->where('school_id', $this->targetSchool->id)
            ->where('role', 'school_admin')
            ->firstOrFail();
        $admin->update(['must_change_password' => false, 'email_verified_at' => now()]);

        $this->actingAs($admin)
            ->put(route('super-admin.settings.update'), [
                'platform_name' => 'Hacked',
                'default_free_ai_credits' => 99,
                'maintenance_mode' => '0',
                'maintenance_message' => '',
                'allowed_file_types' => 'pdf',
                'max_upload_size_mb' => 10,
                'credit_price_per_5' => 1000,
            ])
            ->assertForbidden();
    }

    // ── Default credits used on school creation ────────────────────────────────

    public function test_new_school_uses_default_free_credits_from_settings(): void
    {
        PlatformSetting::set('default_free_ai_credits', 25);

        $newSchool = app(SchoolSetupService::class)->create([
            'name' => 'Credits Test School',
            'email' => 'credits@school.test',
            'levels' => ['primary'],
            'admin_name' => 'Admin',
            'admin_email' => 'admin@credits.test',
            'admin_username' => 'credits_admin',
            'admin_password' => 'Password1!',
            'session_name' => '2025/2026',
            'session_start_date' => '2025-09-01',
            'session_end_date' => '2026-07-31',
        ]);

        $this->assertSame(25, $newSchool->fresh()->ai_free_credits);
    }

    // ── Maintenance mode ──────────────────────────────────────────────────────

    public function test_maintenance_mode_blocks_school_admin(): void
    {
        PlatformSetting::set('maintenance_mode', true);
        PlatformSetting::set('maintenance_message', 'Down for upgrades');

        $admin = User::withoutGlobalScopes()
            ->where('school_id', $this->targetSchool->id)
            ->where('role', 'school_admin')
            ->firstOrFail();
        $admin->update(['must_change_password' => false, 'email_verified_at' => now()]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertStatus(503);
    }

    public function test_maintenance_mode_allows_super_admin_through(): void
    {
        PlatformSetting::set('maintenance_mode', true);

        $this->actingAs($this->superAdmin)
            ->get(route('super-admin.dashboard'))
            ->assertOk();
    }

    public function test_maintenance_mode_off_allows_all_users(): void
    {
        PlatformSetting::set('maintenance_mode', false);

        $admin = User::withoutGlobalScopes()
            ->where('school_id', $this->targetSchool->id)
            ->where('role', 'school_admin')
            ->firstOrFail();
        $admin->update(['must_change_password' => false, 'email_verified_at' => now()]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    // ── PlatformSetting model ─────────────────────────────────────────────────

    public function test_platform_setting_get_returns_typed_integer(): void
    {
        PlatformSetting::set('default_free_ai_credits', 30);
        $this->assertSame(30, PlatformSetting::get('default_free_ai_credits'));
        $this->assertIsInt(PlatformSetting::get('default_free_ai_credits'));
    }

    public function test_platform_setting_get_returns_typed_boolean(): void
    {
        PlatformSetting::set('maintenance_mode', true);
        $this->assertTrue(PlatformSetting::get('maintenance_mode'));
        $this->assertIsBool(PlatformSetting::get('maintenance_mode'));
    }

    public function test_platform_setting_get_returns_default_when_not_set(): void
    {
        $this->assertSame('fallback', PlatformSetting::get('nonexistent_key', 'fallback'));
    }

    public function test_platform_setting_all_values_returns_all_keys(): void
    {
        $all = PlatformSetting::allValues();

        $this->assertArrayHasKey('platform_name', $all);
        $this->assertArrayHasKey('default_free_ai_credits', $all);
        $this->assertArrayHasKey('maintenance_mode', $all);
        $this->assertArrayHasKey('maintenance_message', $all);
        $this->assertArrayHasKey('allowed_file_types', $all);
        $this->assertArrayHasKey('max_upload_size_mb', $all);
        $this->assertArrayHasKey('credit_price_per_5', $all);
    }
}
