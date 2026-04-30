<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\StudentProfile;
use App\Models\StudentTermReport;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithSchoolContext;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;
    use WithSchoolContext;

    protected AcademicSession $session;

    protected Term $term;

    protected User $teacher;

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

    private function createReport(string $status = 'published', string $reportType = 'full_term', ?array $snapshot = null): StudentTermReport
    {
        return StudentTermReport::create([
            'student_id' => $this->student->id,
            'school_id' => $this->school->id,
            'class_id' => $this->class->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'report_type' => $reportType,
            'total_weighted_score' => 150,
            'average_weighted_score' => 75.0,
            'subjects_count' => 2,
            'position' => 1,
            'out_of' => 1,
            'teacher_comment' => 'Good student.',
            'principal_comment' => 'Keep it up.',
            'subject_scores_snapshot' => $snapshot ?? [
                [
                    'subject_name' => 'Mathematics',
                    'weighted_total' => 80.0,
                    'grade' => 'A',
                    'position' => 1,
                    'components' => [
                        ['short_name' => 'CA1', 'score' => 18],
                        ['short_name' => 'Exam', 'score' => 62],
                    ],
                ],
                [
                    'subject_name' => 'English',
                    'weighted_total' => 70.0,
                    'grade' => 'B',
                    'position' => 1,
                    'components' => [
                        ['short_name' => 'CA1', 'score' => 15],
                        ['short_name' => 'Exam', 'score' => 55],
                    ],
                ],
            ],
            'status' => $status,
        ]);
    }

    // ─── Admin Export CSV ────────────────────────────────────────

    public function test_admin_can_export_reports_csv(): void
    {
        $this->createReport();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.scores.reports.export-csv', [
                'class_id' => $this->class->id,
                'term_id' => $this->term->id,
            ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('Position', $content);
        $this->assertStringContainsString('Student Name', $content);
        $this->assertStringContainsString('Mathematics - CA1', $content);
        $this->assertStringContainsString('English - Exam', $content);
        $this->assertStringContainsString($this->student->name, $content);
    }

    public function test_admin_export_csv_requires_class_id(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.scores.reports.export-csv'));

        $response->assertRedirect();
    }

    public function test_admin_export_csv_redirects_when_no_reports(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.scores.reports.export-csv', [
                'class_id' => $this->class->id,
                'term_id' => $this->term->id,
            ]));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_admin_export_csv_filters_by_report_type(): void
    {
        $this->createReport('published', 'midterm');

        $response = $this->actingAs($this->admin)
            ->get(route('admin.scores.reports.export-csv', [
                'class_id' => $this->class->id,
                'report_type' => 'full_term',
            ]));

        // No full_term reports exist, only midterm
        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    // ─── Admin Download All PDFs ─────────────────────────────────

    public function test_admin_can_download_all_reports_pdf(): void
    {
        $this->createReport();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.scores.reports.download-all', [
                'class_id' => $this->class->id,
                'term_id' => $this->term->id,
            ]));

        $response->assertOk();
        $disposition = $response->headers->get('content-disposition');
        $this->assertNotNull($disposition);
        $this->assertStringContainsString('All_Reports_', $disposition);
        $this->assertStringContainsString('.pdf', $disposition);
    }

    public function test_admin_download_all_pdf_redirects_when_no_reports(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.scores.reports.download-all', [
                'class_id' => $this->class->id,
                'term_id' => $this->term->id,
            ]));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    // ─── Teacher Download Single PDF ─────────────────────────────

    public function test_teacher_can_download_single_report_pdf(): void
    {
        $report = $this->createReport();

        $response = $this->actingAs($this->teacher)
            ->get(route('teacher.scores.reports.download', $report));

        $response->assertOk();
        $disposition = $response->headers->get('content-disposition');
        $this->assertNotNull($disposition);
        $this->assertStringContainsString('.pdf', $disposition);
    }

    public function test_other_teacher_cannot_download_report(): void
    {
        $otherTeacher = $this->createSchoolUser('teacher');
        $report = $this->createReport();

        $response = $this->actingAs($otherTeacher)
            ->get(route('teacher.scores.reports.download', $report));

        $response->assertNotFound();
    }

    // ─── Teacher Export CSV ──────────────────────────────────────

    public function test_teacher_can_export_reports_csv(): void
    {
        $this->createReport();

        $response = $this->actingAs($this->teacher)
            ->get(route('teacher.scores.reports.export-csv', [
                'class_id' => $this->class->id,
                'term_id' => $this->term->id,
            ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('Mathematics', $content);
        $this->assertStringContainsString($this->student->name, $content);
    }

    public function test_other_teacher_cannot_export_csv_for_class(): void
    {
        $otherTeacher = $this->createSchoolUser('teacher');
        $this->createReport();

        $response = $this->actingAs($otherTeacher)
            ->get(route('teacher.scores.reports.export-csv', [
                'class_id' => $this->class->id,
            ]));

        $response->assertNotFound();
    }

    // ─── Teacher Download All PDFs ───────────────────────────────

    public function test_teacher_can_download_all_reports_pdf(): void
    {
        $this->createReport();

        $response = $this->actingAs($this->teacher)
            ->get(route('teacher.scores.reports.download-all', [
                'class_id' => $this->class->id,
                'term_id' => $this->term->id,
            ]));

        $response->assertOk();
        $disposition = $response->headers->get('content-disposition');
        $this->assertNotNull($disposition);
        $this->assertStringContainsString('All_Reports_', $disposition);
    }

    public function test_other_teacher_cannot_download_all_for_class(): void
    {
        $otherTeacher = $this->createSchoolUser('teacher');
        $this->createReport();

        $response = $this->actingAs($otherTeacher)
            ->get(route('teacher.scores.reports.download-all', [
                'class_id' => $this->class->id,
            ]));

        $response->assertNotFound();
    }

    // ─── Session Report CSV ──────────────────────────────────────

    public function test_admin_can_export_session_report_csv(): void
    {
        $term2 = Term::withoutGlobalScopes()
            ->where('school_id', $this->school->id)
            ->where('id', '!=', $this->term->id)
            ->first();

        $sessionSnapshot = [
            [
                'subject_name' => 'Mathematics',
                'session_total' => 82.5,
                'grade' => 'A',
                'position' => 1,
                'term_scores' => [
                    ['term_id' => $this->term->id, 'term_name' => 'First Term', 'score' => 80.0],
                    ['term_id' => $term2->id, 'term_name' => 'Second Term', 'score' => 85.0],
                ],
            ],
        ];

        $this->createReport('published', 'session', $sessionSnapshot);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.scores.reports.export-csv', [
                'class_id' => $this->class->id,
                'report_type' => 'session',
            ]));

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString('Mathematics - First Term', $content);
        $this->assertStringContainsString('Mathematics - Second Term', $content);
        $this->assertStringContainsString('Mathematics - Session Avg', $content);
    }

    // ─── Role Authorization ──────────────────────────────────────

    public function test_student_cannot_access_admin_export(): void
    {
        $response = $this->actingAs($this->student)
            ->get(route('admin.scores.reports.export-csv', [
                'class_id' => $this->class->id,
            ]));

        $response->assertForbidden();
    }

    public function test_student_cannot_access_teacher_export(): void
    {
        $response = $this->actingAs($this->student)
            ->get(route('teacher.scores.reports.export-csv', [
                'class_id' => $this->class->id,
            ]));

        $response->assertForbidden();
    }
}
