<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\ReportCardConfig;
use App\Models\SchoolClass;
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

class TeacherScoreTest extends TestCase
{
    use RefreshDatabase;
    use WithSchoolContext;

    private User $teacher;

    private User $student;

    private AcademicSession $session;

    private Term $term;

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
    }

    public function test_teacher_can_view_scores_index(): void
    {
        $this->actingAs($this->teacher)
            ->get(route('teacher.scores.index'))
            ->assertOk()
            ->assertViewIs('teacher.scores.index');
    }

    public function test_teacher_can_view_scores_with_class_filter(): void
    {
        $this->actingAs($this->teacher)
            ->get(route('teacher.scores.index', [
                'class_id' => $this->class->id,
                'term_id' => $this->term->id,
            ]))
            ->assertOk();
    }

    public function test_teacher_can_view_reports(): void
    {
        $this->actingAs($this->teacher)
            ->get(route('teacher.scores.reports', [
                'class_id' => $this->class->id,
                'term_id' => $this->term->id,
            ]))
            ->assertOk()
            ->assertViewIs('teacher.scores.reports');
    }

    public function test_teacher_can_save_comment_on_draft_report(): void
    {
        $report = $this->createReport('draft');

        $this->actingAs($this->teacher)
            ->post(route('teacher.scores.reports.comment', $report), [
                'teacher_comment' => 'Great improvement this term!',
            ])
            ->assertRedirect();

        $report->refresh();
        $this->assertEquals('Great improvement this term!', $report->teacher_comment);
        $this->assertEquals('pending_approval', $report->status);
        $this->assertEquals($this->teacher->id, $report->teacher_id);
    }

    public function test_teacher_cannot_comment_on_non_draft_report(): void
    {
        $report = $this->createReport('pending_approval');

        $this->actingAs($this->teacher)
            ->post(route('teacher.scores.reports.comment', $report), [
                'teacher_comment' => 'Too late!',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertNull($report->fresh()->teacher_comment);
    }

    public function test_teacher_can_bulk_submit_reports(): void
    {
        $report = $this->createReport('draft');

        $this->actingAs($this->teacher)
            ->post(route('teacher.scores.reports.bulk-submit'), [
                'class_id' => $this->class->id,
                'term_id' => $this->term->id,
                'comments' => [
                    $this->student->id => 'Good term overall.',
                ],
            ])
            ->assertRedirect();

        $report->refresh();
        $this->assertEquals('pending_approval', $report->status);
        $this->assertEquals('Good term overall.', $report->teacher_comment);
    }

    public function test_teacher_can_view_single_report(): void
    {
        $report = $this->createReport('draft');

        $this->actingAs($this->teacher)
            ->get(route('teacher.scores.reports.show', $report))
            ->assertOk()
            ->assertViewIs('teacher.scores.show-report');
    }

    public function test_teacher_cannot_view_other_class_scores(): void
    {
        $otherTeacher = $this->createSchoolUser('teacher');
        // Teacher not assigned to $this->class

        $this->actingAs($otherTeacher)
            ->get(route('teacher.scores.index', [
                'class_id' => $this->class->id,
                'term_id' => $this->term->id,
            ]))
            ->assertNotFound();
    }

    public function test_student_cannot_access_teacher_score_routes(): void
    {
        $this->actingAs($this->student)
            ->get(route('teacher.scores.index'))
            ->assertForbidden();
    }

    public function test_teacher_can_view_reports_with_report_type_filter(): void
    {
        $this->createReport('draft', 'full_term');
        $this->createReport('draft', 'midterm');

        $this->actingAs($this->teacher)
            ->get(route('teacher.scores.reports', [
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

    public function test_teacher_reports_view_has_enabled_report_types(): void
    {
        ReportCardConfig::create([
            'school_id' => $this->school->id,
            'enabled_report_types' => ['full_term', 'midterm'],
        ]);

        $this->actingAs($this->teacher)
            ->get(route('teacher.scores.reports', ['class_id' => $this->class->id]))
            ->assertOk()
            ->assertViewHas('enabledReportTypes', ['full_term', 'midterm']);
    }

    // ── Score Entry Tests ──

    public function test_teacher_can_save_scores_manually(): void
    {
        $subject = Subject::create([
            'school_id' => $this->school->id,
            'name' => 'Mathematics',
            'slug' => 'mathematics',
            'short_name' => 'MTH',
            'category' => 'science',
            'is_active' => true,
        ]);

        $component = ScoreComponent::create([
            'school_id' => $this->school->id,
            'name' => 'CA 1',
            'short_name' => 'CA1',
            'max_score' => 20,
            'weight' => 30,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->actingAs($this->teacher)
            ->post(route('teacher.scores.save'), [
                'class_id' => $this->class->id,
                'term_id' => $this->term->id,
                'scores' => [
                    $this->student->id => [
                        $subject->id => [
                            $component->id => 15,
                        ],
                    ],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('student_subject_scores', [
            'student_id' => $this->student->id,
            'subject_id' => $subject->id,
            'score_component_id' => $component->id,
            'score' => 15.00,
            'source_type' => 'manual',
            'entered_by' => $this->teacher->id,
        ]);
    }

    public function test_teacher_cannot_save_scores_for_other_class(): void
    {
        $otherClass = SchoolClass::create([
            'school_id' => $this->school->id,
            'level_id' => $this->level->id,
            'name' => 'Other Class',
            'slug' => 'other-class',
            'is_active' => true,
        ]);

        $this->actingAs($this->teacher)
            ->post(route('teacher.scores.save'), [
                'class_id' => $otherClass->id,
                'term_id' => $this->term->id,
                'scores' => [
                    $this->student->id => [
                        1 => [1 => 10],
                    ],
                ],
            ])
            ->assertNotFound();
    }

    public function test_teacher_cannot_edit_locked_scores(): void
    {
        $subject = Subject::create([
            'school_id' => $this->school->id,
            'name' => 'English',
            'slug' => 'english',
            'short_name' => 'ENG',
            'category' => 'arts',
            'is_active' => true,
        ]);

        $component = ScoreComponent::create([
            'school_id' => $this->school->id,
            'name' => 'Exam',
            'short_name' => 'EXM',
            'max_score' => 60,
            'weight' => 70,
            'sort_order' => 2,
            'is_active' => true,
        ]);

        StudentSubjectScore::create([
            'student_id' => $this->student->id,
            'school_id' => $this->school->id,
            'class_id' => $this->class->id,
            'subject_id' => $subject->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'score_component_id' => $component->id,
            'score' => 50,
            'max_score' => 60,
            'source_type' => 'manual',
            'is_locked' => true,
        ]);

        $this->actingAs($this->teacher)
            ->post(route('teacher.scores.save'), [
                'class_id' => $this->class->id,
                'term_id' => $this->term->id,
                'scores' => [
                    $this->student->id => [
                        $subject->id => [
                            $component->id => 30,
                        ],
                    ],
                ],
            ])
            ->assertRedirect();

        // Score should remain unchanged (50, not 30) because it's locked
        $this->assertDatabaseHas('student_subject_scores', [
            'student_id' => $this->student->id,
            'subject_id' => $subject->id,
            'score_component_id' => $component->id,
            'score' => 50.00,
            'is_locked' => true,
        ]);
    }

    public function test_teacher_score_capped_at_max(): void
    {
        $subject = Subject::create([
            'school_id' => $this->school->id,
            'name' => 'Science',
            'slug' => 'science',
            'short_name' => 'SCI',
            'category' => 'science',
            'is_active' => true,
        ]);

        $component = ScoreComponent::create([
            'school_id' => $this->school->id,
            'name' => 'CA 2',
            'short_name' => 'CA2',
            'max_score' => 20,
            'weight' => 30,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->actingAs($this->teacher)
            ->post(route('teacher.scores.save'), [
                'class_id' => $this->class->id,
                'term_id' => $this->term->id,
                'scores' => [
                    $this->student->id => [
                        $subject->id => [
                            $component->id => 99, // exceeds max of 20
                        ],
                    ],
                ],
            ])
            ->assertRedirect();

        // Score should be capped at max_score (20)
        $this->assertDatabaseHas('student_subject_scores', [
            'student_id' => $this->student->id,
            'subject_id' => $subject->id,
            'score_component_id' => $component->id,
            'score' => 20.00,
        ]);
    }

    public function test_student_cannot_access_teacher_score_save(): void
    {
        $this->actingAs($this->student)
            ->post(route('teacher.scores.save'), [
                'class_id' => $this->class->id,
                'term_id' => $this->term->id,
                'scores' => [],
            ])
            ->assertForbidden();
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
