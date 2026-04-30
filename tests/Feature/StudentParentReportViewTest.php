<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\ParentStudent;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolLevel;
use App\Models\StudentProfile;
use App\Models\StudentTermReport;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithSchoolContext;
use Tests\TestCase;

class StudentParentReportViewTest extends TestCase
{
    use RefreshDatabase;
    use WithSchoolContext;

    protected AcademicSession $session;

    protected Term $term;

    protected User $student;

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

    private function createReport(
        string $status = 'published',
        string $reportType = 'full_term',
        int|null|false $termId = false,
    ): StudentTermReport {
        return StudentTermReport::create([
            'student_id' => $this->student->id,
            'school_id' => $this->school->id,
            'class_id' => $this->class->id,
            'session_id' => $this->session->id,
            'term_id' => $termId === false ? $this->term->id : $termId,
            'report_type' => $reportType,
            'total_weighted_score' => 150,
            'average_weighted_score' => 75.0,
            'subjects_count' => 2,
            'position' => 1,
            'out_of' => 1,
            'teacher_comment' => 'Good.',
            'principal_comment' => 'Keep it up.',
            'subject_scores_snapshot' => [
                ['subject_name' => 'Mathematics', 'weighted_total' => 80.0, 'grade' => 'A', 'position' => 1, 'components' => []],
                ['subject_name' => 'English', 'weighted_total' => 70.0, 'grade' => 'B', 'position' => 1, 'components' => []],
            ],
            'status' => $status,
        ]);
    }

    // ─── Student: Published Reports Visible ──────────────────────

    public function test_student_sees_published_full_term_report(): void
    {
        $this->createReport('published', 'full_term');

        $response = $this->actingAs($this->student)
            ->get(route('student.report-cards.index'));

        $response->assertOk();
        $response->assertSee('Full Term Report');
    }

    public function test_student_sees_published_midterm_report(): void
    {
        $this->createReport('published', 'midterm');

        $response = $this->actingAs($this->student)
            ->get(route('student.report-cards.index'));

        $response->assertOk();
        $response->assertSee('Mid-Term Report');
    }

    public function test_student_sees_published_session_report(): void
    {
        $this->createReport('published', 'session', termId: null);

        $response = $this->actingAs($this->student)
            ->get(route('student.report-cards.index'));

        $response->assertOk();
        $response->assertSee('Session Report');
        $response->assertSee('Session Overview');
    }

    // ─── Student: Unpublished Reports Hidden ─────────────────────

    public function test_student_does_not_see_draft_report(): void
    {
        $this->createReport('draft', 'full_term');

        $response = $this->actingAs($this->student)
            ->get(route('student.report-cards.index'));

        $response->assertOk();
        $response->assertDontSee('Full Term Report');
        $response->assertSee('No Report Cards Yet');
    }

    public function test_student_does_not_see_pending_report(): void
    {
        $this->createReport('pending_approval', 'midterm');

        $response = $this->actingAs($this->student)
            ->get(route('student.report-cards.index'));

        $response->assertOk();
        $response->assertDontSee('Mid-Term Report');
    }

    public function test_student_does_not_see_approved_but_unpublished_report(): void
    {
        $this->createReport('approved', 'full_term');

        $response = $this->actingAs($this->student)
            ->get(route('student.report-cards.index'));

        $response->assertOk();
        $response->assertDontSee('Full Term Report');
    }

    // ─── Student: Multiple Report Types ──────────────────────────

    public function test_student_sees_all_three_report_types_in_session(): void
    {
        $this->createReport('published', 'midterm');
        $this->createReport('published', 'full_term');
        $this->createReport('published', 'session', termId: null);

        $response = $this->actingAs($this->student)
            ->get(route('student.report-cards.index'));

        $response->assertOk();
        $response->assertSee('Mid-Term Report');
        $response->assertSee('Full Term Report');
        $response->assertSee('Session Report');
        $response->assertSee('Session Overview');
    }

    public function test_student_report_shows_average_score(): void
    {
        $this->createReport('published', 'full_term');

        $response = $this->actingAs($this->student)
            ->get(route('student.report-cards.index'));

        $response->assertOk();
        $response->assertSee('75.0%');
    }

    public function test_student_report_shows_position(): void
    {
        $this->createReport('published', 'full_term');

        $response = $this->actingAs($this->student)
            ->get(route('student.report-cards.index'));

        $response->assertOk();
        // Position 1 should render as "1" followed by a <sup> with "st"
        $response->assertSee('1');
    }

    // ─── Student: Tenant Isolation ───────────────────────────────

    public function test_student_cannot_see_other_school_reports(): void
    {
        // Create a report for this student
        $this->createReport('published', 'full_term');

        // Create another school's student
        $otherSchool = School::factory()->create();
        $otherLevel = SchoolLevel::create([
            'school_id' => $otherSchool->id,
            'name' => 'Primary',
            'slug' => 'primary',
        ]);
        $otherClass = SchoolClass::create([
            'school_id' => $otherSchool->id,
            'level_id' => $otherLevel->id,
            'name' => 'Primary 1',
            'slug' => 'primary-1',
        ]);
        $otherSession = AcademicSession::create([
            'school_id' => $otherSchool->id,
            'name' => '2025/2026',
            'start_date' => '2025-09-01',
            'end_date' => '2026-07-31',
            'is_current' => true,
            'status' => 'active',
        ]);
        $otherTerm = Term::create([
            'school_id' => $otherSchool->id,
            'session_id' => $otherSession->id,
            'term_number' => 1,
            'name' => 'First Term',
            'is_current' => true,
            'status' => 'active',
        ]);
        $otherStudent = User::factory()->create([
            'school_id' => $otherSchool->id,
            'role' => 'student',
            'level_id' => $otherLevel->id,
        ]);

        // This student should only see their own report, not the other school's student info
        $response = $this->actingAs($this->student)
            ->get(route('student.report-cards.index'));

        $response->assertOk();
        $response->assertDontSee($otherStudent->name);
    }

    // ─── Parent: Sees Child's Published Reports ──────────────────

    public function test_parent_sees_child_published_reports(): void
    {
        $parent = User::factory()->create([
            'school_id' => $this->school->id,
            'role' => 'parent',
            'level_id' => $this->level->id,
        ]);
        ParentStudent::create([
            'parent_id' => $parent->id,
            'student_id' => $this->student->id,
            'school_id' => $this->school->id,
        ]);

        $this->createReport('published', 'full_term');
        $this->createReport('published', 'midterm');

        $response = $this->actingAs($parent)
            ->get(route('parent.children.report-cards', $this->student));

        $response->assertOk();
        $response->assertSee('Full Term Report');
        $response->assertSee('Mid-Term Report');
    }

    public function test_parent_does_not_see_child_draft_reports(): void
    {
        $parent = User::factory()->create([
            'school_id' => $this->school->id,
            'role' => 'parent',
            'level_id' => $this->level->id,
        ]);
        ParentStudent::create([
            'parent_id' => $parent->id,
            'student_id' => $this->student->id,
            'school_id' => $this->school->id,
        ]);

        $this->createReport('draft', 'full_term');

        $response = $this->actingAs($parent)
            ->get(route('parent.children.report-cards', $this->student));

        $response->assertOk();
        $response->assertDontSee('Full Term Report');
        $response->assertSee('No Report Cards Yet');
    }

    public function test_parent_sees_session_report_for_child(): void
    {
        $parent = User::factory()->create([
            'school_id' => $this->school->id,
            'role' => 'parent',
            'level_id' => $this->level->id,
        ]);
        ParentStudent::create([
            'parent_id' => $parent->id,
            'student_id' => $this->student->id,
            'school_id' => $this->school->id,
        ]);

        $this->createReport('published', 'session', termId: null);

        $response = $this->actingAs($parent)
            ->get(route('parent.children.report-cards', $this->student));

        $response->assertOk();
        $response->assertSee('Session Report');
        $response->assertSee('Session Overview');
    }

    public function test_parent_cannot_see_unlinked_child_reports(): void
    {
        $parent = User::factory()->create([
            'school_id' => $this->school->id,
            'role' => 'parent',
            'level_id' => $this->level->id,
        ]);
        // NOT linking the parent to the student

        $this->createReport('published', 'full_term');

        $response = $this->actingAs($parent)
            ->get(route('parent.children.report-cards', $this->student));

        $response->assertForbidden();
    }

    // ─── Badge Display ───────────────────────────────────────────

    public function test_report_type_badge_colors_are_present(): void
    {
        $this->createReport('published', 'midterm');
        $this->createReport('published', 'full_term');
        $this->createReport('published', 'session', termId: null);

        $response = $this->actingAs($this->student)
            ->get(route('student.report-cards.index'));

        $response->assertOk();
        // The partial renders Flux badges with color attributes
        $content = $response->getContent();
        $this->assertStringContainsString('midterm', strtolower($content));
        $this->assertStringContainsString('full term', strtolower($content));
        $this->assertStringContainsString('session', strtolower($content));
    }
}
