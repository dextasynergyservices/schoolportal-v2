<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\ReportCardConfig;
use App\Models\ScoreComponent;
use App\Models\StudentProfile;
use App\Models\StudentSubjectScore;
use App\Models\StudentTermReport;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithSchoolContext;
use Tests\TestCase;

class AdminScoreReportTest extends TestCase
{
    use RefreshDatabase;
    use WithSchoolContext;

    private AcademicSession $session;

    private Term $term;

    private Subject $subject;

    private ScoreComponent $component;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpSchoolContext();

        $this->session = AcademicSession::withoutGlobalScopes()
            ->where('school_id', $this->school->id)->first();
        $this->session->update(['is_current' => true, 'status' => 'active']);

        $this->term = Term::withoutGlobalScopes()
            ->where('school_id', $this->school->id)->first();
        $this->term->update(['is_current' => true, 'status' => 'active']);

        $this->subject = Subject::create([
            'school_id' => $this->school->id,
            'name' => 'Mathematics',
            'slug' => 'mathematics',
            'short_name' => 'MTH',
            'category' => 'science',
            'is_active' => true,
        ]);

        $this->component = ScoreComponent::create([
            'school_id' => $this->school->id,
            'name' => 'CA 1',
            'short_name' => 'CA1',
            'max_score' => 20,
            'weight' => 30,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->student = User::factory()->create([
            'school_id' => $this->school->id,
            'role' => 'student',
            'level_id' => $this->level->id,
        ]);

        StudentProfile::create([
            'user_id' => $this->student->id,
            'school_id' => $this->school->id,
            'class_id' => $this->class->id,
        ]);
    }

    // ── Score Management ──

    public function test_admin_can_view_scores_index(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.scores.index'))
            ->assertOk()
            ->assertViewIs('admin.scores.index');
    }

    public function test_admin_can_view_scores_with_class_filter(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.scores.index', [
                'class_id' => $this->class->id,
                'term_id' => $this->term->id,
            ]))
            ->assertOk();
    }

    public function test_admin_can_save_scores_manually(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.scores.save'), [
                'class_id' => $this->class->id,
                'term_id' => $this->term->id,
                'scores' => [
                    $this->student->id => [
                        $this->subject->id => [
                            $this->component->id => 15,
                        ],
                    ],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('student_subject_scores', [
            'student_id' => $this->student->id,
            'subject_id' => $this->subject->id,
            'score_component_id' => $this->component->id,
            'score' => 15.00,
            'source_type' => 'manual',
            'entered_by' => $this->admin->id,
        ]);
    }

    public function test_admin_can_lock_scores(): void
    {
        StudentSubjectScore::create([
            'student_id' => $this->student->id,
            'school_id' => $this->school->id,
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'score_component_id' => $this->component->id,
            'score' => 18,
            'max_score' => 20,
            'source_type' => 'manual',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.scores.lock'), [
                'class_id' => $this->class->id,
                'term_id' => $this->term->id,
            ])
            ->assertRedirect();

        $score = StudentSubjectScore::where('student_id', $this->student->id)
            ->where('subject_id', $this->subject->id)
            ->where('term_id', $this->term->id)
            ->first();

        $this->assertTrue((bool) $score->is_locked);
    }

    public function test_locked_scores_cannot_be_updated(): void
    {
        StudentSubjectScore::create([
            'student_id' => $this->student->id,
            'school_id' => $this->school->id,
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'score_component_id' => $this->component->id,
            'score' => 18,
            'max_score' => 20,
            'source_type' => 'manual',
            'is_locked' => true,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.scores.save'), [
                'class_id' => $this->class->id,
                'term_id' => $this->term->id,
                'scores' => [
                    $this->student->id => [
                        $this->subject->id => [
                            $this->component->id => 10,
                        ],
                    ],
                ],
            ])
            ->assertRedirect();

        // Score should remain unchanged
        $score = StudentSubjectScore::where('student_id', $this->student->id)
            ->where('subject_id', $this->subject->id)
            ->where('term_id', $this->term->id)
            ->first();

        $this->assertEquals(18, (int) $score->score);
    }

    // ── Report Generation & Workflow ──

    public function test_admin_can_generate_reports(): void
    {
        // Add a score so report has data
        StudentSubjectScore::create([
            'student_id' => $this->student->id,
            'school_id' => $this->school->id,
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'score_component_id' => $this->component->id,
            'score' => 15,
            'max_score' => 20,
            'source_type' => 'manual',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.scores.generate-reports'), [
                'class_id' => $this->class->id,
                'term_id' => $this->term->id,
                'report_type' => 'full_term',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('student_term_reports', [
            'student_id' => $this->student->id,
            'class_id' => $this->class->id,
            'term_id' => $this->term->id,
            'report_type' => 'full_term',
        ]);
    }

    public function test_admin_can_generate_midterm_reports(): void
    {
        StudentSubjectScore::create([
            'student_id' => $this->student->id,
            'school_id' => $this->school->id,
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'score_component_id' => $this->component->id,
            'score' => 15,
            'max_score' => 20,
            'source_type' => 'manual',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.scores.generate-reports'), [
                'class_id' => $this->class->id,
                'term_id' => $this->term->id,
                'report_type' => 'midterm',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('student_term_reports', [
            'student_id' => $this->student->id,
            'class_id' => $this->class->id,
            'term_id' => $this->term->id,
            'report_type' => 'midterm',
        ]);
    }

    public function test_admin_can_generate_session_reports(): void
    {
        StudentSubjectScore::create([
            'student_id' => $this->student->id,
            'school_id' => $this->school->id,
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'score_component_id' => $this->component->id,
            'score' => 15,
            'max_score' => 20,
            'source_type' => 'manual',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.scores.generate-reports'), [
                'class_id' => $this->class->id,
                'session_id' => $this->session->id,
                'report_type' => 'session',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('student_term_reports', [
            'student_id' => $this->student->id,
            'class_id' => $this->class->id,
            'session_id' => $this->session->id,
            'report_type' => 'session',
        ]);
    }

    public function test_generate_reports_requires_report_type(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.scores.generate-reports'), [
                'class_id' => $this->class->id,
                'term_id' => $this->term->id,
            ])
            ->assertSessionHasErrors('report_type');
    }

    public function test_report_type_filter_works_on_reports_list(): void
    {
        // Create reports of different types
        $this->createReport('draft', 'full_term');
        $this->createReport('draft', 'midterm');

        $this->actingAs($this->admin)
            ->get(route('admin.scores.reports', [
                'class_id' => $this->class->id,
                'term_id' => $this->term->id,
                'report_type' => 'midterm',
            ]))
            ->assertOk()
            ->assertViewHas('reports', function ($reports) {
                return $reports->count() === 1
                    && $reports->first()->report_type === 'midterm';
            });
    }

    public function test_enabled_report_types_passed_to_reports_view(): void
    {
        ReportCardConfig::create([
            'school_id' => $this->school->id,
            'enabled_report_types' => ['full_term', 'midterm', 'session'],
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.scores.reports', ['class_id' => $this->class->id]))
            ->assertOk()
            ->assertViewHas('enabledReportTypes', ['full_term', 'midterm', 'session']);
    }

    public function test_enabled_report_types_passed_to_scores_index(): void
    {
        ReportCardConfig::create([
            'school_id' => $this->school->id,
            'enabled_report_types' => ['full_term', 'session'],
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.scores.index'))
            ->assertOk()
            ->assertViewHas('enabledReportTypes', ['full_term', 'session']);
    }

    public function test_admin_can_view_reports_list(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.scores.reports', [
                'class_id' => $this->class->id,
                'term_id' => $this->term->id,
            ]))
            ->assertOk()
            ->assertViewIs('admin.scores.reports');
    }

    public function test_admin_can_approve_pending_report(): void
    {
        $report = $this->createReport('pending_approval');

        $this->actingAs($this->admin)
            ->post(route('admin.scores.reports.approve', $report), [
                'principal_comment' => 'Well done!',
            ])
            ->assertRedirect();

        $report->refresh();
        $this->assertEquals('approved', $report->status);
        $this->assertEquals('Well done!', $report->principal_comment);
    }

    public function test_admin_can_bulk_approve_reports(): void
    {
        $this->createReport('pending_approval');

        $this->actingAs($this->admin)
            ->post(route('admin.scores.reports.bulk-approve'), [
                'class_id' => $this->class->id,
                'term_id' => $this->term->id,
                'principal_comment' => 'All approved',
            ])
            ->assertRedirect();

        $this->assertEquals(0, StudentTermReport::where('status', 'pending_approval')
            ->where('class_id', $this->class->id)->count());
    }

    public function test_admin_can_publish_reports(): void
    {
        $this->createReport('approved');

        $this->actingAs($this->admin)
            ->post(route('admin.scores.reports.publish'), [
                'class_id' => $this->class->id,
                'term_id' => $this->term->id,
            ])
            ->assertRedirect();

        $this->assertEquals(1, StudentTermReport::where('status', 'published')
            ->where('class_id', $this->class->id)->count());
    }

    public function test_teacher_cannot_access_admin_score_routes(): void
    {
        $teacher = $this->createSchoolUser('teacher');

        $this->actingAs($teacher)
            ->get(route('admin.scores.index'))
            ->assertForbidden();
    }

    // ── Phase 8: Comprehensive Edge-Case Tests ──

    public function test_disabled_report_type_cannot_be_generated(): void
    {
        ReportCardConfig::create([
            'school_id' => $this->school->id,
            'enabled_report_types' => ['full_term'], // midterm NOT enabled
        ]);

        StudentSubjectScore::create([
            'student_id' => $this->student->id,
            'school_id' => $this->school->id,
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'score_component_id' => $this->component->id,
            'score' => 15,
            'max_score' => 20,
            'source_type' => 'manual',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.scores.generate-reports'), [
                'class_id' => $this->class->id,
                'term_id' => $this->term->id,
                'report_type' => 'midterm', // disabled
            ])
            ->assertSessionHasErrors('report_type');

        $this->assertDatabaseMissing('student_term_reports', [
            'student_id' => $this->student->id,
            'report_type' => 'midterm',
        ]);
    }

    public function test_generate_reports_for_class_with_no_scores(): void
    {
        // Student enrolled but no scores entered — should still produce a report
        $this->actingAs($this->admin)
            ->post(route('admin.scores.generate-reports'), [
                'class_id' => $this->class->id,
                'term_id' => $this->term->id,
                'report_type' => 'full_term',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $report = StudentTermReport::withoutGlobalScopes()
            ->where('student_id', $this->student->id)
            ->where('term_id', $this->term->id)
            ->first();

        $this->assertNotNull($report);
        $this->assertEquals(0.0, $report->total_weighted_score);
        $this->assertEquals(0.0, $report->average_weighted_score);
    }

    // ── Helper ──

    private function createReport(string $status, string $reportType = 'full_term'): StudentTermReport
    {
        return StudentTermReport::create([
            'student_id' => $this->student->id,
            'school_id' => $this->school->id,
            'class_id' => $this->class->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'report_type' => $reportType,
            'total_weighted_score' => 150,
            'average_weighted_score' => 75,
            'subjects_count' => 2,
            'position' => 1,
            'out_of' => 1,
            'subject_scores_snapshot' => [],
            'status' => $status,
        ]);
    }
}
