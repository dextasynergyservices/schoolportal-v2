<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\GradingScale;
use App\Models\GradingScaleItem;
use App\Models\ReportCardConfig;
use App\Models\ScoreComponent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithSchoolContext;
use Tests\TestCase;

class AdminGradingTest extends TestCase
{
    use RefreshDatabase;
    use WithSchoolContext;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpSchoolContext();
    }

    public function test_admin_can_view_grading_index(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.grading.index'))
            ->assertOk()
            ->assertViewIs('admin.grading.index');
    }

    public function test_admin_can_create_grading_scale(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.grading.scales.store'), [
                'name' => 'WAEC Scale',
                'is_default' => true,
                'items' => [
                    ['grade' => 'A1', 'label' => 'Excellent', 'min_score' => 75, 'max_score' => 100],
                    ['grade' => 'B2', 'label' => 'Very Good', 'min_score' => 70, 'max_score' => 74],
                    ['grade' => 'F9', 'label' => 'Fail', 'min_score' => 0, 'max_score' => 39],
                ],
            ])
            ->assertRedirect(route('admin.grading.index'));

        $this->assertDatabaseHas('grading_scales', [
            'name' => 'WAEC Scale',
            'school_id' => $this->school->id,
            'is_default' => true,
        ]);

        $scale = GradingScale::withoutGlobalScopes()
            ->where('name', 'WAEC Scale')
            ->where('school_id', $this->school->id)
            ->first();

        $this->assertCount(3, $scale->items);
    }

    public function test_grading_scale_requires_items(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.grading.scales.store'), [
                'name' => 'Empty Scale',
            ])
            ->assertSessionHasErrors('items');
    }

    public function test_admin_can_update_grading_scale(): void
    {
        $scale = GradingScale::create([
            'school_id' => $this->school->id,
            'name' => 'Old Scale',
            'is_default' => false,
        ]);

        GradingScaleItem::create([
            'grading_scale_id' => $scale->id,
            'school_id' => $this->school->id,
            'grade' => 'A',
            'label' => 'Pass',
            'min_score' => 50,
            'max_score' => 100,
            'sort_order' => 0,
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.grading.scales.update', $scale), [
                'name' => 'Updated Scale',
                'items' => [
                    ['grade' => 'A', 'label' => 'Excellent', 'min_score' => 80, 'max_score' => 100],
                    ['grade' => 'B', 'label' => 'Good', 'min_score' => 60, 'max_score' => 79],
                ],
            ])
            ->assertRedirect(route('admin.grading.index'));

        $this->assertEquals('Updated Scale', $scale->fresh()->name);
    }

    public function test_admin_can_delete_grading_scale(): void
    {
        $scale = GradingScale::create([
            'school_id' => $this->school->id,
            'name' => 'Delete Me',
            'is_default' => false,
        ]);

        $this->actingAs($this->admin)
            ->delete(route('admin.grading.scales.destroy', $scale))
            ->assertRedirect(route('admin.grading.index'));

        $this->assertDatabaseMissing('grading_scales', ['id' => $scale->id]);
    }

    // ── Score Components ──

    public function test_admin_can_store_score_components(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.grading.components.store'), [
                'components' => [
                    ['name' => 'CA 1', 'short_name' => 'CA1', 'max_score' => 20, 'weight' => 30, 'sort_order' => 1, 'is_active' => true],
                    ['name' => 'Exam', 'short_name' => 'EXM', 'max_score' => 60, 'weight' => 70, 'sort_order' => 2, 'is_active' => true],
                ],
            ])
            ->assertRedirect(route('admin.grading.index'));

        $this->assertDatabaseHas('score_components', [
            'name' => 'CA 1',
            'school_id' => $this->school->id,
            'weight' => 30,
        ]);
    }

    public function test_teacher_cannot_access_grading(): void
    {
        $teacher = $this->createSchoolUser('teacher');

        $this->actingAs($teacher)
            ->get(route('admin.grading.index'))
            ->assertForbidden();
    }

    // ── Phase 2: Score Components — Mid-Term Toggle ──

    public function test_admin_can_save_include_in_midterm_per_component(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.grading.components.store'), [
                'components' => [
                    ['name' => 'CA1', 'short_name' => 'CA1', 'max_score' => 10, 'weight' => 10, 'include_in_midterm' => '1'],
                    ['name' => 'CA2', 'short_name' => 'CA2', 'max_score' => 10, 'weight' => 10, 'include_in_midterm' => '1'],
                    ['name' => 'Mid-Term', 'short_name' => 'MT', 'max_score' => 20, 'weight' => 20, 'include_in_midterm' => '1'],
                    ['name' => 'Exam', 'short_name' => 'EXM', 'max_score' => 60, 'weight' => 60, 'include_in_midterm' => '0'],
                ],
            ])
            ->assertRedirect(route('admin.grading.index'));

        $this->assertDatabaseHas('score_components', [
            'short_name' => 'CA1',
            'school_id' => $this->school->id,
            'include_in_midterm' => true,
        ]);
        $this->assertDatabaseHas('score_components', [
            'short_name' => 'EXM',
            'school_id' => $this->school->id,
            'include_in_midterm' => false,
        ]);
    }

    public function test_include_in_midterm_defaults_to_false(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.grading.components.store'), [
                'components' => [
                    ['name' => 'CA1', 'short_name' => 'CA1', 'max_score' => 40, 'weight' => 40],
                    ['name' => 'Exam', 'short_name' => 'EXM', 'max_score' => 60, 'weight' => 60],
                ],
            ])
            ->assertRedirect(route('admin.grading.index'));

        $this->assertDatabaseHas('score_components', [
            'short_name' => 'CA1',
            'school_id' => $this->school->id,
            'include_in_midterm' => false,
        ]);
    }

    // ── Phase 2: Report Card Config — Report Types & Session Method ──

    public function test_admin_can_enable_report_types(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.grading.report-card.update'), [
                'enabled_report_types' => ['full_term', 'session'],
                'session_calculation_method' => 'average_of_terms',
                'show_term_breakdown_in_session' => '1',
            ])
            ->assertRedirect(route('admin.grading.index'));

        $config = ReportCardConfig::where('school_id', $this->school->id)->first();
        $this->assertEquals(['full_term', 'session'], $config->enabled_report_types);
        $this->assertEquals('average_of_terms', $config->session_calculation_method);
        $this->assertTrue($config->show_term_breakdown_in_session);
    }

    public function test_admin_can_save_weighted_session_method(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.grading.report-card.update'), [
                'enabled_report_types' => ['full_term', 'session'],
                'session_calculation_method' => 'weighted_average',
                'midterm_weight' => '30',
                'fullterm_weight' => '70',
                'show_term_breakdown_in_session' => '1',
            ])
            ->assertRedirect(route('admin.grading.index'));

        $config = ReportCardConfig::where('school_id', $this->school->id)->first();
        $this->assertEquals('weighted_average', $config->session_calculation_method);
        $this->assertEquals('30.00', $config->midterm_weight);
        $this->assertEquals('70.00', $config->fullterm_weight);
    }

    public function test_admin_can_enable_best_two_of_three(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.grading.report-card.update'), [
                'enabled_report_types' => ['full_term', 'session'],
                'session_calculation_method' => 'best_two_of_three',
            ])
            ->assertRedirect(route('admin.grading.index'));

        $config = ReportCardConfig::where('school_id', $this->school->id)->first();
        $this->assertEquals('best_two_of_three', $config->session_calculation_method);
    }

    // ── Phase 2: Validation Rules ──

    public function test_at_least_one_report_type_must_be_enabled(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.grading.report-card.update'), [
                'enabled_report_types' => [],
            ])
            ->assertSessionHasErrors('enabled_report_types');
    }

    public function test_invalid_report_type_is_rejected(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.grading.report-card.update'), [
                'enabled_report_types' => ['invalid_type'],
            ])
            ->assertSessionHasErrors('enabled_report_types.0');
    }

    public function test_weighted_method_requires_weights_summing_to_100(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.grading.report-card.update'), [
                'enabled_report_types' => ['full_term', 'session'],
                'session_calculation_method' => 'weighted_average',
                'midterm_weight' => '30',
                'fullterm_weight' => '30',
            ])
            ->assertSessionHasErrors('midterm_weight');
    }

    public function test_session_report_requires_calculation_method(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.grading.report-card.update'), [
                'enabled_report_types' => ['session'],
                'session_calculation_method' => '',
            ])
            ->assertSessionHasErrors('session_calculation_method');
    }

    public function test_midterm_report_requires_at_least_two_midterm_components(): void
    {
        // Ensure no components have include_in_midterm = true
        ScoreComponent::where('school_id', $this->school->id)->delete();
        ScoreComponent::create([
            'school_id' => $this->school->id,
            'name' => 'Exam',
            'short_name' => 'EXM',
            'max_score' => 100,
            'weight' => 100,
            'sort_order' => 0,
            'include_in_midterm' => false,
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.grading.report-card.update'), [
                'enabled_report_types' => ['midterm'],
            ])
            ->assertSessionHasErrors('enabled_report_types');
    }

    public function test_midterm_report_accepted_with_enough_midterm_components(): void
    {
        ScoreComponent::where('school_id', $this->school->id)->delete();
        ScoreComponent::create([
            'school_id' => $this->school->id,
            'name' => 'CA1',
            'short_name' => 'CA1',
            'max_score' => 20,
            'weight' => 40,
            'sort_order' => 0,
            'include_in_midterm' => true,
        ]);
        ScoreComponent::create([
            'school_id' => $this->school->id,
            'name' => 'CA2',
            'short_name' => 'CA2',
            'max_score' => 20,
            'weight' => 60,
            'sort_order' => 1,
            'include_in_midterm' => true,
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.grading.report-card.update'), [
                'enabled_report_types' => ['midterm', 'full_term'],
            ])
            ->assertRedirect(route('admin.grading.index'));

        $config = ReportCardConfig::where('school_id', $this->school->id)->first();
        $this->assertContains('midterm', $config->enabled_report_types);
    }
}
