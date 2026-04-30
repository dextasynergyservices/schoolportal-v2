<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\GradingScale;
use App\Models\GradingScaleItem;
use App\Models\ReportCardConfig;
use App\Models\ScoreComponent;
use App\Models\StudentProfile;
use App\Models\StudentTermReport;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithSchoolContext;
use Tests\TestCase;

class ReportPdfTemplateTest extends TestCase
{
    use RefreshDatabase;
    use WithSchoolContext;

    private AcademicSession $session;

    private Term $term;

    private Subject $subject;

    private ScoreComponent $component;

    private User $student;

    private GradingScale $gradingScale;

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
            'include_in_midterm' => true,
        ]);

        $this->student = User::factory()->create([
            'school_id' => $this->school->id,
            'role' => 'student',
            'name' => 'John Doe',
            'level_id' => $this->level->id,
        ]);

        StudentProfile::create([
            'user_id' => $this->student->id,
            'school_id' => $this->school->id,
            'class_id' => $this->class->id,
            'admission_number' => 'ADM001',
        ]);

        $this->gradingScale = GradingScale::create([
            'school_id' => $this->school->id,
            'name' => 'Default',
            'is_default' => true,
            'is_active' => true,
        ]);

        GradingScaleItem::create([
            'grading_scale_id' => $this->gradingScale->id,
            'grade' => 'A',
            'label' => 'Excellent',
            'min_score' => 70,
            'max_score' => 100,
        ]);

        GradingScaleItem::create([
            'grading_scale_id' => $this->gradingScale->id,
            'grade' => 'B',
            'label' => 'Good',
            'min_score' => 50,
            'max_score' => 69,
        ]);

        $this->config = ReportCardConfig::create([
            'school_id' => $this->school->id,
            'psychomotor_traits' => ['Handwriting', 'Creativity'],
            'affective_traits' => ['Punctuality', 'Neatness'],
            'trait_rating_scale' => ['Excellent', 'Good', 'Fair', 'Poor'],
            'show_position' => true,
            'show_class_average' => true,
            'show_term_breakdown_in_session' => true,
            'session_calculation_method' => 'average_of_terms',
            'enabled_report_types' => ['full_term', 'midterm', 'session'],
        ]);
    }

    // ── Full-Term PDF ──

    public function test_full_term_pdf_contains_correct_title(): void
    {
        $report = $this->createTermReport('full_term');

        $response = $this->actingAs($this->admin)
            ->get(route('admin.scores.reports.download', $report));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_full_term_pdf_renders_component_columns(): void
    {
        $report = $this->createTermReport('full_term');

        $html = $this->renderPdfView($report);

        $this->assertStringContainsString('Student Report Card', $html);
        $this->assertStringContainsString('CA1', $html);
        $this->assertStringContainsString('Total (%)', $html);
        $this->assertStringNotContainsString('Mid-Term Total (%)', $html);
        $this->assertStringNotContainsString('Mid-Term Progress Report', $html);
        $this->assertStringNotContainsString('Session Report Card', $html);
    }

    public function test_full_term_pdf_shows_psychomotor_and_affective(): void
    {
        $report = $this->createTermReport('full_term');
        $report->update([
            'psychomotor_ratings' => ['Handwriting' => 'Excellent', 'Creativity' => 'Good'],
            'affective_ratings' => ['Punctuality' => 'Good', 'Neatness' => 'Excellent'],
        ]);

        $html = $this->renderPdfView($report->refresh());

        $this->assertStringContainsString('Psychomotor Skills', $html);
        $this->assertStringContainsString('Handwriting', $html);
        $this->assertStringContainsString('Affective Domain', $html);
        $this->assertStringContainsString('Punctuality', $html);
    }

    public function test_full_term_pdf_shows_comments(): void
    {
        $report = $this->createTermReport('full_term');
        $report->update([
            'teacher_comment' => 'Great improvement this term.',
            'principal_comment' => 'Keep up the good work.',
        ]);

        $html = $this->renderPdfView($report->refresh());

        $this->assertStringContainsString("Class Teacher's Comment", $html);
        $this->assertStringContainsString('Great improvement this term.', $html);
        $this->assertStringContainsString('Keep up the good work.', $html);
    }

    // ── Mid-Term PDF ──

    public function test_midterm_pdf_contains_correct_title(): void
    {
        $report = $this->createTermReport('midterm');

        $html = $this->renderPdfView($report);

        $this->assertStringContainsString('Mid-Term Progress Report', $html);
        $this->assertStringNotContainsString('Student Report Card', $html);
    }

    public function test_midterm_pdf_shows_midterm_total_column(): void
    {
        $report = $this->createTermReport('midterm');

        $html = $this->renderPdfView($report);

        $this->assertStringContainsString('Mid-Term Total (%)', $html);
        $this->assertStringNotContainsString('>Total (%)<', $html);
    }

    public function test_midterm_pdf_shows_only_midterm_components(): void
    {
        // Add a non-midterm component
        $examComp = ScoreComponent::create([
            'school_id' => $this->school->id,
            'name' => 'Exam',
            'short_name' => 'EXM',
            'max_score' => 60,
            'weight' => 70,
            'sort_order' => 2,
            'is_active' => true,
            'include_in_midterm' => false,
        ]);

        // Full-term snapshot has both components
        $fullTermSnapshot = [
            [
                'subject_id' => $this->subject->id,
                'subject_name' => 'Mathematics',
                'components' => [
                    ['component_id' => $this->component->id, 'name' => 'CA 1', 'short_name' => 'CA1', 'score' => 15.0, 'max_score' => 20, 'weight' => 30],
                    ['component_id' => $examComp->id, 'name' => 'Exam', 'short_name' => 'EXM', 'score' => 45.0, 'max_score' => 60, 'weight' => 70],
                ],
                'weighted_total' => 74.3,
                'grade' => 'A',
                'grade_label' => 'Excellent',
                'position' => 1,
                'class_average' => 65.2,
            ],
        ];

        // Midterm snapshot has only midterm components
        $midtermSnapshot = [
            [
                'subject_id' => $this->subject->id,
                'subject_name' => 'Mathematics',
                'components' => [
                    ['component_id' => $this->component->id, 'name' => 'CA 1', 'short_name' => 'CA1', 'score' => 15.0, 'max_score' => 20, 'weight' => 30],
                ],
                'weighted_total' => 75.0,
                'grade' => 'A',
                'grade_label' => 'Excellent',
                'position' => 1,
                'class_average' => 62.0,
            ],
        ];

        $midtermReport = $this->createTermReport('midterm', $midtermSnapshot);
        $html = $this->renderPdfView($midtermReport);

        // Should have CA1 but NOT Exam column
        $this->assertStringContainsString('CA1', $html);
        $this->assertStringNotContainsString('EXM', $html);
    }

    public function test_midterm_pdf_shows_disclaimer_note(): void
    {
        $report = $this->createTermReport('midterm');

        $html = $this->renderPdfView($report);

        $this->assertStringContainsString('mid-term progress report', $html);
        $this->assertStringContainsString('Final term grades may differ', $html);
    }

    public function test_midterm_pdf_shows_psychomotor_and_comments(): void
    {
        $report = $this->createTermReport('midterm');
        $report->update([
            'psychomotor_ratings' => ['Handwriting' => 'Good'],
            'teacher_comment' => 'Good mid-term performance.',
        ]);

        $html = $this->renderPdfView($report->refresh());

        $this->assertStringContainsString('Psychomotor Skills', $html);
        $this->assertStringContainsString("Class Teacher's Comment", $html);
        $this->assertStringContainsString('Good mid-term performance.', $html);
    }

    // ── Session PDF ──

    public function test_session_pdf_contains_correct_title(): void
    {
        $report = $this->createSessionReport();

        $html = $this->renderPdfView($report);

        $this->assertStringContainsString('Session Report Card', $html);
        $this->assertStringNotContainsString('Mid-Term Progress Report', $html);
    }

    public function test_session_pdf_shows_term_breakdown_columns(): void
    {
        $report = $this->createSessionReport();

        $html = $this->renderPdfView($report);

        $this->assertStringContainsString('First Term (%)', $html);
        $this->assertStringContainsString('Second Term (%)', $html);
        $this->assertStringContainsString('Session Avg (%)', $html);
    }

    public function test_session_pdf_hides_term_breakdown_when_disabled(): void
    {
        $this->config->update(['show_term_breakdown_in_session' => false]);

        $report = $this->createSessionReport();

        $html = $this->renderPdfView($report);

        $this->assertStringNotContainsString('First Term (%)', $html);
        $this->assertStringNotContainsString('Second Term (%)', $html);
        $this->assertStringContainsString('Session Avg (%)', $html);
    }

    public function test_session_pdf_shows_calculation_method(): void
    {
        $report = $this->createSessionReport();

        $html = $this->renderPdfView($report);

        $this->assertStringContainsString('Calculation Method', $html);
        $this->assertStringContainsString('Average of all term scores', $html);
    }

    public function test_session_pdf_does_not_show_psychomotor_or_comments(): void
    {
        $report = $this->createSessionReport();

        $html = $this->renderPdfView($report);

        $this->assertStringNotContainsString('Psychomotor Skills', $html);
        $this->assertStringNotContainsString('Affective Domain', $html);
        $this->assertStringNotContainsString("Class Teacher's Comment", $html);
    }

    public function test_session_pdf_shows_session_name_as_period(): void
    {
        $report = $this->createSessionReport();

        $html = $this->renderPdfView($report);

        $this->assertStringContainsString('Academic Session', $html);
        $this->assertStringContainsString($this->session->name, $html);
    }

    // ── Download Filename Tests ──

    public function test_full_term_download_has_correct_filename(): void
    {
        $report = $this->createTermReport('full_term');

        $response = $this->actingAs($this->admin)
            ->get(route('admin.scores.reports.download', $report));

        $disposition = $response->headers->get('content-disposition');
        $this->assertStringContainsString('Report_Card_John_Doe', $disposition);
    }

    public function test_midterm_download_has_correct_filename(): void
    {
        $report = $this->createTermReport('midterm');

        $response = $this->actingAs($this->admin)
            ->get(route('admin.scores.reports.download', $report));

        $disposition = $response->headers->get('content-disposition');
        $this->assertStringContainsString('MidTerm_Report_John_Doe', $disposition);
    }

    public function test_session_download_has_correct_filename(): void
    {
        $report = $this->createSessionReport();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.scores.reports.download', $report));

        $response->assertOk();
        $disposition = $response->headers->get('content-disposition');
        $this->assertNotNull($disposition);
        $this->assertStringContainsString('Session_Report_John_Doe', $disposition);
    }

    // ── Grading Key ──

    public function test_all_report_types_show_grading_key(): void
    {
        foreach (['full_term', 'midterm'] as $type) {
            $report = $this->createTermReport($type);
            $html = $this->renderPdfView($report);
            $this->assertStringContainsString('Grading Key', $html, "Grading key missing for {$type}");
            $this->assertStringContainsString('Excellent', $html);
        }

        $sessionReport = $this->createSessionReport();
        $html = $this->renderPdfView($sessionReport);
        $this->assertStringContainsString('Grading Key', $html, 'Grading key missing for session');
    }

    // ── Phase 8: Edge-Case Tests ──

    public function test_pdf_hides_psychomotor_section_when_no_traits_configured(): void
    {
        // Override config with empty traits
        $this->config->update([
            'psychomotor_traits' => [],
            'affective_traits' => [],
        ]);

        $report = $this->createTermReport('full_term');
        $html = $this->renderPdfView($report);

        $this->assertStringNotContainsString('Psychomotor Skills', $html);
        $this->assertStringNotContainsString('Affective Domain', $html);
    }

    public function test_pdf_renders_correctly_with_zero_score_student(): void
    {
        $report = $this->createTermReport('full_term', [
            [
                'subject_id' => $this->subject->id,
                'subject_name' => 'Mathematics',
                'components' => [
                    [
                        'component_id' => $this->component->id,
                        'name' => 'CA 1',
                        'short_name' => 'CA1',
                        'score' => 0.0,
                        'max_score' => 20,
                        'weight' => 30,
                    ],
                ],
                'weighted_total' => 0.0,
                'grade' => 'F',
                'grade_label' => 'Fail',
                'position' => 1,
                'class_average' => 0.0,
            ],
        ]);

        $html = $this->renderPdfView($report);

        $this->assertStringContainsString('Mathematics', $html);
        $this->assertStringContainsString('F', $html);
        $this->assertStringContainsString('0', $html);
    }

    // ── Helpers ──

    private function createTermReport(string $reportType, ?array $snapshot = null): StudentTermReport
    {
        $snapshot ??= [
            [
                'subject_id' => $this->subject->id,
                'subject_name' => 'Mathematics',
                'components' => [
                    [
                        'component_id' => $this->component->id,
                        'name' => 'CA 1',
                        'short_name' => 'CA1',
                        'score' => 15.0,
                        'max_score' => 20,
                        'weight' => 30,
                    ],
                ],
                'weighted_total' => 75.0,
                'grade' => 'A',
                'grade_label' => 'Excellent',
                'position' => 1,
                'class_average' => 65.0,
            ],
        ];

        return StudentTermReport::create([
            'student_id' => $this->student->id,
            'school_id' => $this->school->id,
            'class_id' => $this->class->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'report_type' => $reportType,
            'total_weighted_score' => 75,
            'average_weighted_score' => 75,
            'subjects_count' => 1,
            'position' => 1,
            'out_of' => 5,
            'subject_scores_snapshot' => $snapshot,
            'status' => 'published',
        ]);
    }

    private function createSessionReport(): StudentTermReport
    {
        // Use explicit IDs in the snapshot — template reads from snapshot data only
        $snapshot = [
            [
                'subject_id' => $this->subject->id,
                'subject_name' => 'Mathematics',
                'term_scores' => [
                    ['term_id' => 90001, 'term_name' => 'First Term', 'score' => 72.0],
                    ['term_id' => 90002, 'term_name' => 'Second Term', 'score' => 80.0],
                ],
                'session_total' => 76.0,
                'grade' => 'A',
                'grade_label' => 'Excellent',
                'position' => 1,
                'class_average' => 68.0,
            ],
        ];

        return StudentTermReport::create([
            'student_id' => $this->student->id,
            'school_id' => $this->school->id,
            'class_id' => $this->class->id,
            'session_id' => $this->session->id,
            'term_id' => null,
            'report_type' => 'session',
            'total_weighted_score' => 76,
            'average_weighted_score' => 76,
            'subjects_count' => 1,
            'position' => 1,
            'out_of' => 5,
            'subject_scores_snapshot' => $snapshot,
            'status' => 'published',
        ]);
    }

    private function renderPdfView(StudentTermReport $report): string
    {
        $report->load(['student.studentProfile', 'class', 'session', 'term']);
        $school = $this->school;
        $config = $this->config;
        $gradingScale = $this->gradingScale->load('items');

        return view('admin.scores.report-pdf', compact(
            'report', 'school', 'config', 'gradingScale'
        ))->render();
    }
}
