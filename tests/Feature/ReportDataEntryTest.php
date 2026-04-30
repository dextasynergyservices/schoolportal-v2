<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\ReportCardConfig;
use App\Models\SchoolClass;
use App\Models\StudentProfile;
use App\Models\StudentTermReport;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithSchoolContext;
use Tests\TestCase;

class ReportDataEntryTest extends TestCase
{
    use RefreshDatabase;
    use WithSchoolContext;

    private User $teacher;

    private User $student;

    private AcademicSession $session;

    private Term $term;

    private ReportCardConfig $config;

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

        $this->teacher = $this->createSchoolUser('teacher');
        $this->class->update(['teacher_id' => $this->teacher->id]);

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

        $this->config = ReportCardConfig::create([
            'school_id' => $this->school->id,
            'psychomotor_traits' => ['Handwriting', 'Fluency', 'Creativity'],
            'affective_traits' => ['Honesty', 'Neatness', 'Politeness'],
            'trait_rating_scale' => [
                ['value' => 5, 'label' => 'Excellent'],
                ['value' => 4, 'label' => 'Very Good'],
                ['value' => 3, 'label' => 'Good'],
                ['value' => 2, 'label' => 'Fair'],
                ['value' => 1, 'label' => 'Poor'],
            ],
            'comment_presets' => [
                'excellent' => ['Outstanding performance this term.'],
                'good' => ['Good effort, keep it up.'],
            ],
        ]);
    }

    private function createDraftReport(): StudentTermReport
    {
        return StudentTermReport::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'class_id' => $this->class->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'report_type' => 'full_term',
            'status' => 'draft',
        ]);
    }

    // ─── Teacher: Single Student ─────────────────────────────

    public function test_teacher_can_view_edit_report_data_form(): void
    {
        $report = $this->createDraftReport();

        $response = $this->actingAs($this->teacher)
            ->get(route('teacher.scores.reports.edit-data', $report));

        $response->assertOk();
        $response->assertSee('Report Data Entry');
        $response->assertSee($this->student->name);
        $response->assertSee('Handwriting');
        $response->assertSee('Honesty');
    }

    public function test_teacher_can_save_report_data(): void
    {
        $report = $this->createDraftReport();

        $response = $this->actingAs($this->teacher)
            ->post(route('teacher.scores.reports.save-data', $report), [
                'attendance_present' => 55,
                'attendance_absent' => 5,
                'attendance_total' => 60,
                'psychomotor' => ['Handwriting' => 4, 'Fluency' => 3, 'Creativity' => 5],
                'affective' => ['Honesty' => 5, 'Neatness' => 4, 'Politeness' => 3],
                'teacher_comment' => 'Good student overall.',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $report->refresh();
        $this->assertEquals(55, $report->attendance_present);
        $this->assertEquals(5, $report->attendance_absent);
        $this->assertEquals(60, $report->attendance_total);
        $this->assertEquals(['Handwriting' => 4, 'Fluency' => 3, 'Creativity' => 5], $report->psychomotor_ratings);
        $this->assertEquals(['Honesty' => 5, 'Neatness' => 4, 'Politeness' => 3], $report->affective_ratings);
        $this->assertEquals('Good student overall.', $report->teacher_comment);
    }

    public function test_teacher_cannot_edit_approved_report_data(): void
    {
        $report = $this->createDraftReport();
        $report->update(['status' => 'approved']);

        $response = $this->actingAs($this->teacher)
            ->get(route('teacher.scores.reports.edit-data', $report));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_teacher_cannot_save_to_published_report(): void
    {
        $report = $this->createDraftReport();
        $report->update(['status' => 'published']);

        $response = $this->actingAs($this->teacher)
            ->post(route('teacher.scores.reports.save-data', $report), [
                'attendance_present' => 50,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_teacher_cannot_edit_other_class_report(): void
    {
        // Create a class not assigned to this teacher
        $otherClass = SchoolClass::create([
            'school_id' => $this->school->id,
            'level_id' => $this->level->id,
            'name' => 'Other Class',
            'slug' => 'other-class',
            'teacher_id' => null,
        ]);

        $report = StudentTermReport::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'class_id' => $otherClass->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'report_type' => 'full_term',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->teacher)
            ->get(route('teacher.scores.reports.edit-data', $report));

        $response->assertNotFound();
    }

    public function test_teacher_rating_validation_rejects_over_max(): void
    {
        $report = $this->createDraftReport();

        $response = $this->actingAs($this->teacher)
            ->post(route('teacher.scores.reports.save-data', $report), [
                'psychomotor' => ['Handwriting' => 10],
            ]);

        $response->assertSessionHasErrors('psychomotor.Handwriting');
    }

    // ─── Teacher: Bulk ───────────────────────────────────────

    public function test_teacher_can_view_bulk_edit_form(): void
    {
        $this->createDraftReport();

        $response = $this->actingAs($this->teacher)
            ->get(route('teacher.scores.reports.bulk-edit-data', [
                'class_id' => $this->class->id,
                'term_id' => $this->term->id,
            ]));

        $response->assertOk();
        $response->assertSee('Bulk Report Data Entry');
        $response->assertSee($this->student->name);
    }

    public function test_teacher_can_bulk_save_report_data(): void
    {
        $report = $this->createDraftReport();

        $response = $this->actingAs($this->teacher)
            ->post(route('teacher.scores.reports.bulk-save-data'), [
                'class_id' => $this->class->id,
                'term_id' => $this->term->id,
                'reports' => [
                    $report->id => [
                        'attendance_present' => 50,
                        'attendance_absent' => 10,
                        'attendance_total' => 60,
                        'psychomotor' => ['Handwriting' => 4],
                        'affective' => ['Honesty' => 5],
                        'teacher_comment' => 'Bulk comment.',
                    ],
                ],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $report->refresh();
        $this->assertEquals(50, $report->attendance_present);
        $this->assertEquals('Bulk comment.', $report->teacher_comment);
        $this->assertEquals(4, $report->psychomotor_ratings['Handwriting']);
    }

    public function test_teacher_bulk_save_skips_approved_reports(): void
    {
        $report = $this->createDraftReport();
        $report->update(['status' => 'approved']);

        $response = $this->actingAs($this->teacher)
            ->post(route('teacher.scores.reports.bulk-save-data'), [
                'class_id' => $this->class->id,
                'term_id' => $this->term->id,
                'reports' => [
                    $report->id => [
                        'teacher_comment' => 'Should not save.',
                    ],
                ],
            ]);

        $response->assertRedirect();
        $report->refresh();
        $this->assertNull($report->teacher_comment);
    }

    // ─── Admin: Single Student ───────────────────────────────

    public function test_admin_can_view_edit_report_data_form(): void
    {
        $report = $this->createDraftReport();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.scores.reports.edit-data', $report));

        $response->assertOk();
        $response->assertSee('Report Data Entry');
    }

    public function test_admin_can_save_report_data(): void
    {
        $report = $this->createDraftReport();

        $response = $this->actingAs($this->admin)
            ->post(route('admin.scores.reports.save-data', $report), [
                'attendance_present' => 58,
                'attendance_absent' => 2,
                'attendance_total' => 60,
                'psychomotor' => ['Handwriting' => 5, 'Fluency' => 5, 'Creativity' => 4],
                'affective' => ['Honesty' => 5, 'Neatness' => 5, 'Politeness' => 5],
                'teacher_comment' => 'Admin comment for student.',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $report->refresh();
        $this->assertEquals(58, $report->attendance_present);
        $this->assertEquals('Admin comment for student.', $report->teacher_comment);
    }

    public function test_admin_cannot_edit_published_report(): void
    {
        $report = $this->createDraftReport();
        $report->update(['status' => 'published']);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.scores.reports.edit-data', $report));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_admin_can_edit_any_class_report(): void
    {
        // Admin should be able to access reports in classes not assigned to any teacher
        $otherClass = SchoolClass::create([
            'school_id' => $this->school->id,
            'level_id' => $this->level->id,
            'name' => 'Another Class',
            'slug' => 'another-class',
            'teacher_id' => null,
        ]);

        $report = StudentTermReport::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'class_id' => $otherClass->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'report_type' => 'full_term',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.scores.reports.edit-data', $report));

        $response->assertOk();
    }

    // ─── Admin: Bulk ─────────────────────────────────────────

    public function test_admin_can_view_bulk_edit_form(): void
    {
        $this->createDraftReport();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.scores.reports.bulk-edit-data', [
                'class_id' => $this->class->id,
                'term_id' => $this->term->id,
            ]));

        $response->assertOk();
        $response->assertSee('Bulk Report Data Entry');
    }

    public function test_admin_can_bulk_save_report_data(): void
    {
        $report = $this->createDraftReport();

        $response = $this->actingAs($this->admin)
            ->post(route('admin.scores.reports.bulk-save-data'), [
                'class_id' => $this->class->id,
                'term_id' => $this->term->id,
                'reports' => [
                    $report->id => [
                        'attendance_present' => 59,
                        'attendance_absent' => 1,
                        'attendance_total' => 60,
                        'psychomotor' => ['Handwriting' => 5, 'Fluency' => 4],
                        'affective' => ['Honesty' => 5],
                        'teacher_comment' => 'Admin bulk comment.',
                    ],
                ],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $report->refresh();
        $this->assertEquals(59, $report->attendance_present);
        $this->assertEquals('Admin bulk comment.', $report->teacher_comment);
    }

    // ─── Rating caps in bulk save ────────────────────────────

    public function test_bulk_save_caps_ratings_at_max_scale(): void
    {
        $report = $this->createDraftReport();

        $this->actingAs($this->admin)
            ->post(route('admin.scores.reports.bulk-save-data'), [
                'class_id' => $this->class->id,
                'term_id' => $this->term->id,
                'reports' => [
                    $report->id => [
                        'psychomotor' => ['Handwriting' => 99],
                        'affective' => ['Honesty' => 99],
                    ],
                ],
            ]);

        $report->refresh();
        // Ratings should be capped at 5 (max in our scale)
        $this->assertEquals(5, $report->psychomotor_ratings['Handwriting']);
        $this->assertEquals(5, $report->affective_ratings['Honesty']);
    }

    // ─── No config: graceful handling ────────────────────────

    public function test_works_without_report_card_config(): void
    {
        ReportCardConfig::where('school_id', $this->school->id)->delete();

        $report = $this->createDraftReport();

        $response = $this->actingAs($this->teacher)
            ->post(route('teacher.scores.reports.save-data', $report), [
                'attendance_present' => 40,
                'attendance_absent' => 20,
                'attendance_total' => 60,
                'teacher_comment' => 'Comment without config.',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $report->refresh();
        $this->assertEquals(40, $report->attendance_present);
        $this->assertEquals('Comment without config.', $report->teacher_comment);
    }
}
