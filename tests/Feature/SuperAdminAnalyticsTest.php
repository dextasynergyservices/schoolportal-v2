<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AiCreditPurchase;
use App\Models\AiCreditUsageLog;
use App\Models\School;
use App\Models\User;
use App\Services\SchoolSetupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private School $platformSchool;

    private User $superAdmin;

    private School $targetSchool;

    private User $schoolAdmin;

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

        $this->schoolAdmin = User::withoutGlobalScopes()
            ->where('school_id', $this->targetSchool->id)
            ->where('role', 'school_admin')
            ->firstOrFail();
        $this->schoolAdmin->update(['must_change_password' => false, 'email_verified_at' => now()]);
    }

    // ── Access control ────────────────────────────────────────────────────────

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('super-admin.analytics'))
            ->assertRedirect(route('login'));
    }

    public function test_school_admin_gets_403(): void
    {
        $this->actingAs($this->schoolAdmin)
            ->get(route('super-admin.analytics'))
            ->assertForbidden();
    }

    public function test_teacher_gets_403(): void
    {
        $teacher = User::withoutGlobalScopes()->create([
            'school_id' => $this->targetSchool->id,
            'name' => 'Teacher',
            'email' => null,
            'username' => 'teacher_analytics',
            'password' => bcrypt('Password1!'),
            'role' => 'teacher',
            'is_active' => true,
            'email_verified_at' => now(),
        ])->refresh();

        $this->actingAs($teacher)
            ->get(route('super-admin.analytics'))
            ->assertForbidden();
    }

    // ── Successful responses ───────────────────────────────────────────────────

    public function test_super_admin_can_view_analytics_default_12m(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('super-admin.analytics'))
            ->assertOk()
            ->assertViewIs('super-admin.analytics')
            ->assertViewHas('range', '12m')
            ->assertViewHas('months', 12);
    }

    public function test_3m_range_returns_3_months_of_data(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('super-admin.analytics', ['range' => '3m']))
            ->assertOk()
            ->assertViewHas('range', '3m')
            ->assertViewHas('months', 3);

        $view = $response->viewData('schoolsData');
        $this->assertCount(3, $view);
    }

    public function test_6m_range_returns_6_months_of_data(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('super-admin.analytics', ['range' => '6m']))
            ->assertOk()
            ->assertViewHas('range', '6m')
            ->assertViewHas('months', 6);

        $view = $response->viewData('studentsData');
        $this->assertCount(6, $view);
    }

    public function test_12m_range_returns_12_months_of_data(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('super-admin.analytics', ['range' => '12m']))
            ->assertOk();

        $this->assertCount(12, $response->viewData('schoolsData'));
        $this->assertCount(12, $response->viewData('studentsData'));
        $this->assertCount(12, $response->viewData('revenueData'));
        $this->assertCount(12, $response->viewData('creditsData'));
    }

    public function test_invalid_range_defaults_to_12m(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('super-admin.analytics', ['range' => 'bad']))
            ->assertOk()
            ->assertViewHas('range', '12m');
    }

    // ── Data correctness ──────────────────────────────────────────────────────

    public function test_school_signups_counted_in_current_month(): void
    {
        // targetSchool already created in setUp (this month)
        $response = $this->actingAs($this->superAdmin)
            ->get(route('super-admin.analytics', ['range' => '3m']))
            ->assertOk();

        $schoolsData = $response->viewData('schoolsData');
        // Last element = current month
        $this->assertGreaterThanOrEqual(1, $schoolsData[array_key_last($schoolsData)]);
    }

    public function test_student_registrations_counted_in_current_month(): void
    {
        // Create a student this month
        $student = User::withoutGlobalScopes()->create([
            'school_id' => $this->targetSchool->id,
            'name' => 'Test Student',
            'username' => 'test_student_analytics',
            'password' => bcrypt('Password1!'),
            'role' => 'student',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('super-admin.analytics', ['range' => '3m']))
            ->assertOk();

        $studentsData = $response->viewData('studentsData');
        $this->assertGreaterThanOrEqual(1, $studentsData[array_key_last($studentsData)]);
    }

    public function test_revenue_is_zero_when_no_purchases(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('super-admin.analytics'))
            ->assertOk();

        $revenueData = $response->viewData('revenueData');
        $this->assertSame(0.0, (float) array_sum($revenueData));
    }

    public function test_revenue_includes_completed_purchases(): void
    {
        AiCreditPurchase::create([
            'school_id' => $this->targetSchool->id,
            'purchased_by' => $this->schoolAdmin->id,
            'credits' => 10,
            'amount_naira' => 2000.00,
            'reference' => 'REF_TEST_001',
            'payment_method' => 'paystack',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('super-admin.analytics', ['range' => '3m']))
            ->assertOk();

        $this->assertSame(2000.0, (float) $response->viewData('periodRevenue'));
    }

    public function test_revenue_excludes_pending_purchases(): void
    {
        AiCreditPurchase::create([
            'school_id' => $this->targetSchool->id,
            'purchased_by' => $this->schoolAdmin->id,
            'credits' => 5,
            'amount_naira' => 1000.00,
            'reference' => 'REF_PENDING_001',
            'payment_method' => 'paystack',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('super-admin.analytics', ['range' => '3m']))
            ->assertOk();

        $this->assertSame(0.0, (float) $response->viewData('periodRevenue'));
    }

    public function test_credit_usage_is_counted(): void
    {
        AiCreditUsageLog::create([
            'school_id' => $this->targetSchool->id,
            'user_id' => $this->schoolAdmin->id,
            'usage_type' => 'quiz',
            'credits_used' => 3,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('super-admin.analytics', ['range' => '3m']))
            ->assertOk();

        $this->assertSame(3, (int) $response->viewData('periodCredits'));
    }

    public function test_period_summary_totals_match_data_arrays(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('super-admin.analytics'))
            ->assertOk();

        $this->assertSame(
            (int) array_sum($response->viewData('schoolsData')),
            (int) $response->viewData('periodSchools'),
        );
        $this->assertSame(
            (int) array_sum($response->viewData('studentsData')),
            (int) $response->viewData('periodStudents'),
        );
    }

    public function test_view_contains_all_time_totals(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('super-admin.analytics'))
            ->assertOk();

        $this->assertNotNull($response->viewData('totalSchools'));
        $this->assertNotNull($response->viewData('totalStudents'));
    }

    public function test_page_renders_expected_headings(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('super-admin.analytics'))
            ->assertOk()
            ->assertSee('Platform Analytics')
            ->assertSee('School Signups')
            ->assertSee('Revenue');
    }

    // ── Custom date range ─────────────────────────────────────────────────────

    public function test_custom_date_range_returns_correct_month_count(): void
    {
        $from = now()->subMonths(5)->startOfMonth()->format('Y-m-d');
        $to = now()->endOfMonth()->format('Y-m-d');

        $response = $this->actingAs($this->superAdmin)
            ->get(route('super-admin.analytics', ['range' => 'custom', 'from' => $from, 'to' => $to]))
            ->assertOk()
            ->assertViewHas('range', 'custom')
            ->assertViewHas('mode', 'custom');

        $this->assertCount(6, $response->viewData('schoolsData'));
    }

    public function test_custom_range_with_missing_from_defaults_to_12m(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('super-admin.analytics', ['range' => 'custom', 'to' => now()->format('Y-m-d')]))
            ->assertOk()
            ->assertViewHas('range', '12m')
            ->assertViewHas('months', 12);
    }

    public function test_custom_range_with_missing_to_defaults_to_12m(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('super-admin.analytics', ['range' => 'custom', 'from' => now()->subMonths(3)->format('Y-m-d')]))
            ->assertOk()
            ->assertViewHas('range', '12m');
    }

    public function test_custom_range_is_capped_at_24_months(): void
    {
        $from = now()->subMonths(30)->format('Y-m-d'); // 30m back
        $to = now()->format('Y-m-d');

        $response = $this->actingAs($this->superAdmin)
            ->get(route('super-admin.analytics', ['range' => 'custom', 'from' => $from, 'to' => $to]))
            ->assertOk()
            ->assertViewHas('range', 'custom');

        // Should not exceed 24 months even though we asked for 30
        $this->assertLessThanOrEqual(24, $response->viewData('months'));
    }

    // ── Export ────────────────────────────────────────────────────────────────

    public function test_export_requires_authentication(): void
    {
        $this->get(route('super-admin.analytics.export'))
            ->assertRedirect(route('login'));
    }

    public function test_export_requires_super_admin_role(): void
    {
        $this->actingAs($this->schoolAdmin)
            ->get(route('super-admin.analytics.export'))
            ->assertForbidden();
    }

    public function test_export_returns_csv_for_super_admin(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('super-admin.analytics.export'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_export_csv_contains_correct_headers(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('super-admin.analytics.export'))
            ->assertOk();

        $body = $response->getContent();
        $this->assertStringContainsString('"Month"', $body);
        $this->assertStringContainsString('"New Schools"', $body);
        $this->assertStringContainsString('"New Students"', $body);
        $this->assertStringContainsString('"Revenue (NGN)"', $body);
        $this->assertStringContainsString('"AI Credits Used"', $body);
    }

    public function test_export_contains_total_row(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('super-admin.analytics.export'))
            ->assertOk();

        $this->assertStringContainsString('"TOTAL"', $response->getContent());
    }

    public function test_export_csv_has_correct_filename(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('super-admin.analytics.export'))
            ->assertOk();

        $disposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('analytics-', $disposition);
        $this->assertStringContainsString('.csv', $disposition);
    }

    public function test_export_excludes_pending_purchases(): void
    {
        AiCreditPurchase::create([
            'school_id' => $this->targetSchool->id,
            'purchased_by' => $this->schoolAdmin->id,
            'credits' => 5,
            'amount_naira' => 1000.00,
            'reference' => 'EXPORT_PENDING_01',
            'payment_method' => 'paystack',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('super-admin.analytics.export'))
            ->assertOk();

        // Total revenue row should show 0 (pending excluded)
        $lines = explode("\r\n", trim($response->getContent()));
        $totalLine = end($lines);
        $cols = str_getcsv($totalLine);
        // Revenue column (index 3)
        $this->assertSame('0', trim($cols[3], '"'));
    }

    public function test_export_with_custom_range(): void
    {
        $from = now()->startOfMonth()->subMonths(2)->format('Y-m-d');
        $to = now()->endOfMonth()->format('Y-m-d');

        $response = $this->actingAs($this->superAdmin)
            ->get(route('super-admin.analytics.export', ['range' => 'custom', 'from' => $from, 'to' => $to]))
            ->assertOk();

        // 3 months of data rows + 1 header + 1 total = 5 lines
        $lines = array_filter(explode("\r\n", trim($response->getContent())));
        $this->assertCount(5, array_values($lines));
    }
}
