<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\AcademicSession;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\GradingScale;
use App\Models\GradingScaleItem;
use App\Models\ReportCardConfig;
use App\Models\ScoreComponent;
use App\Models\StudentProfile;
use App\Models\StudentSubjectScore;
use App\Models\StudentTermReport;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use App\Services\ScoreAggregationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithSchoolContext;
use Tests\TestCase;

class ScoreAggregationServiceTest extends TestCase
{
    use RefreshDatabase;
    use WithSchoolContext;

    private ScoreAggregationService $service;

    private Subject $subject;

    private ScoreComponent $ca1;

    private ScoreComponent $ca2;

    private ScoreComponent $examComponent;

    private AcademicSession $session;

    private Term $term;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpSchoolContext();
        $this->service = app(ScoreAggregationService::class);

        // Create score components (CA1: 20/30w, CA2: 20/30w, Exam: 60/40w = 100w)
        $this->ca1 = ScoreComponent::create([
            'school_id' => $this->school->id,
            'name' => 'CA 1',
            'short_name' => 'CA1',
            'max_score' => 20,
            'weight' => 30,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->ca2 = ScoreComponent::create([
            'school_id' => $this->school->id,
            'name' => 'CA 2',
            'short_name' => 'CA2',
            'max_score' => 20,
            'weight' => 30,
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $this->examComponent = ScoreComponent::create([
            'school_id' => $this->school->id,
            'name' => 'Exam',
            'short_name' => 'EXM',
            'max_score' => 60,
            'weight' => 40,
            'sort_order' => 3,
            'is_active' => true,
        ]);

        // Create subject
        $this->subject = Subject::create([
            'school_id' => $this->school->id,
            'name' => 'Mathematics',
            'slug' => 'mathematics',
            'short_name' => 'MTH',
            'category' => 'science',
            'is_active' => true,
        ]);

        // Get session and term created by setup
        $this->session = AcademicSession::withoutGlobalScopes()
            ->where('school_id', $this->school->id)->first();
        $this->term = Term::withoutGlobalScopes()
            ->where('school_id', $this->school->id)->first();

        // Create a student with profile
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

    // ── updateScoreFromExam ──

    public function test_update_score_from_exam_normalizes_correctly(): void
    {
        $exam = Exam::create([
            'school_id' => $this->school->id,
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'score_component_id' => $this->examComponent->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'title' => 'Math Exam',
            'category' => 'exam',
            'source_type' => 'manual',
            'max_score' => 100,
            'total_points' => 100,
            'passing_score' => 50,
            'status' => 'approved',
            'is_published' => true,
            'created_by' => $this->admin->id,
        ]);

        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $this->student->id,
            'school_id' => $this->school->id,
            'attempt_number' => 1,
            'score' => 80,
            'total_points' => 100,
            'percentage' => 80.0,
            'passed' => true,
            'status' => 'graded',
            'started_at' => now(),
            'submitted_at' => now(),
        ]);

        $this->service->updateScoreFromExam($attempt);

        // 80/100 * 60 (component max) = 48
        $this->assertDatabaseHas('student_subject_scores', [
            'student_id' => $this->student->id,
            'subject_id' => $this->subject->id,
            'score_component_id' => $this->examComponent->id,
            'score' => 48.00,
            'max_score' => 60,
            'source_type' => 'cbt',
        ]);
    }

    public function test_update_score_from_exam_skips_when_no_component(): void
    {
        $exam = Exam::create([
            'school_id' => $this->school->id,
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'score_component_id' => null,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'title' => 'No Component Exam',
            'category' => 'exam',
            'source_type' => 'manual',
            'max_score' => 100,
            'total_points' => 100,
            'passing_score' => 50,
            'status' => 'approved',
            'is_published' => true,
            'created_by' => $this->admin->id,
        ]);

        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $this->student->id,
            'school_id' => $this->school->id,
            'attempt_number' => 1,
            'score' => 80,
            'total_points' => 100,
            'status' => 'graded',
            'started_at' => now(),
        ]);

        $this->service->updateScoreFromExam($attempt);

        $this->assertDatabaseMissing('student_subject_scores', [
            'student_id' => $this->student->id,
        ]);
    }

    // ── computeSubjectTotal ──

    public function test_compute_subject_total_sums_weighted_scores(): void
    {
        // CA1: 15/20 * 30 = 22.5
        StudentSubjectScore::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'score_component_id' => $this->ca1->id,
            'score' => 15,
            'max_score' => 20,
            'source_type' => 'manual',
        ]);

        // CA2: 18/20 * 30 = 27
        StudentSubjectScore::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'score_component_id' => $this->ca2->id,
            'score' => 18,
            'max_score' => 20,
            'source_type' => 'manual',
        ]);

        // Exam: 48/60 * 40 = 32
        StudentSubjectScore::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'score_component_id' => $this->examComponent->id,
            'score' => 48,
            'max_score' => 60,
            'source_type' => 'cbt',
        ]);

        $result = $this->service->computeSubjectTotal(
            $this->student->id,
            $this->subject->id,
            $this->term->id,
        );

        // 22.5 + 27 + 32 = 81.5
        $this->assertEquals(81.5, $result['weighted_total']);
        $this->assertCount(3, $result['components']);
    }

    public function test_compute_subject_total_returns_zero_when_no_scores(): void
    {
        $result = $this->service->computeSubjectTotal(
            $this->student->id,
            $this->subject->id,
            $this->term->id,
        );

        $this->assertEquals(0.0, $result['weighted_total']);
        $this->assertEmpty($result['components']);
    }

    // ── getGrade ──

    public function test_get_grade_returns_correct_grade(): void
    {
        $scale = GradingScale::create([
            'school_id' => $this->school->id,
            'name' => 'Standard',
            'is_default' => true,
            'is_active' => true,
        ]);

        GradingScaleItem::create([
            'grading_scale_id' => $scale->id,
            'school_id' => $this->school->id,
            'grade' => 'A',
            'label' => 'Excellent',
            'min_score' => 70,
            'max_score' => 100,
            'sort_order' => 1,
        ]);

        GradingScaleItem::create([
            'grading_scale_id' => $scale->id,
            'school_id' => $this->school->id,
            'grade' => 'B',
            'label' => 'Very Good',
            'min_score' => 60,
            'max_score' => 69,
            'sort_order' => 2,
        ]);

        GradingScaleItem::create([
            'grading_scale_id' => $scale->id,
            'school_id' => $this->school->id,
            'grade' => 'F',
            'label' => 'Fail',
            'min_score' => 0,
            'max_score' => 39,
            'sort_order' => 5,
        ]);

        $gradeA = $this->service->getGrade($this->school->id, 85.0);
        $this->assertEquals('A', $gradeA['grade']);
        $this->assertEquals('Excellent', $gradeA['label']);

        $gradeB = $this->service->getGrade($this->school->id, 65.0);
        $this->assertEquals('B', $gradeB['grade']);

        $gradeF = $this->service->getGrade($this->school->id, 20.0);
        $this->assertEquals('F', $gradeF['grade']);
    }

    public function test_get_grade_returns_null_when_no_default_scale(): void
    {
        $result = $this->service->getGrade($this->school->id, 80.0);
        $this->assertNull($result);
    }

    // ── computeSubjectPositions ──

    public function test_compute_subject_positions_ranks_students(): void
    {
        $student2 = $this->createStudentWithProfile();
        $student3 = $this->createStudentWithProfile();

        // Student 1: CA1 = 15/20 → weighted = 22.5
        $this->createScore($this->student->id, $this->ca1->id, 15);
        // Student 2: CA1 = 20/20 → weighted = 30
        $this->createScore($student2->id, $this->ca1->id, 20);
        // Student 3: CA1 = 10/20 → weighted = 15
        $this->createScore($student3->id, $this->ca1->id, 10);

        $positions = $this->service->computeSubjectPositions(
            $this->class->id,
            $this->subject->id,
            $this->term->id,
        );

        $this->assertEquals(1, $positions[$student2->id]); // 30 = 1st
        $this->assertEquals(2, $positions[$this->student->id]); // 22.5 = 2nd
        $this->assertEquals(3, $positions[$student3->id]); // 15 = 3rd
    }

    public function test_compute_subject_positions_handles_ties(): void
    {
        $student2 = $this->createStudentWithProfile();

        // Both students score 15/20 → same weighted score
        $this->createScore($this->student->id, $this->ca1->id, 15);
        $this->createScore($student2->id, $this->ca1->id, 15);

        $positions = $this->service->computeSubjectPositions(
            $this->class->id,
            $this->subject->id,
            $this->term->id,
        );

        // Both should be rank 1 (tied)
        $this->assertEquals(1, $positions[$this->student->id]);
        $this->assertEquals(1, $positions[$student2->id]);
    }

    // ── computeOverallPositions ──

    public function test_compute_overall_positions_uses_average_across_subjects(): void
    {
        $subject2 = Subject::create([
            'school_id' => $this->school->id,
            'name' => 'English',
            'slug' => 'english',
            'short_name' => 'ENG',
            'category' => 'arts',
            'is_active' => true,
        ]);

        $student2 = $this->createStudentWithProfile();

        // Student 1: Math CA1=20/20(30w), English CA1=10/20(15w) → avg = (30+15)/2 = 22.5
        $this->createScore($this->student->id, $this->ca1->id, 20);
        $this->createScore($this->student->id, $this->ca1->id, 10, $subject2->id);

        // Student 2: Math CA1=10/20(15w), English CA1=20/20(30w) → avg = (15+30)/2 = 22.5
        $this->createScore($student2->id, $this->ca1->id, 10);
        $this->createScore($student2->id, $this->ca1->id, 20, $subject2->id);

        $positions = $this->service->computeOverallPositions(
            $this->class->id,
            $this->term->id,
            $this->school->id,
        );

        // Both tied at 22.5 average, both should be position 1
        $this->assertEquals(1, $positions[$this->student->id]['position']);
        $this->assertEquals(1, $positions[$student2->id]['position']);
        $this->assertEquals(2, $positions[$this->student->id]['subjects_count']);
    }

    // ── generateTermReport ──

    public function test_generate_term_report_creates_report(): void
    {
        $this->setUpGradingScale();
        $this->createScore($this->student->id, $this->ca1->id, 15);
        $this->createScore($this->student->id, $this->examComponent->id, 48);

        $report = $this->service->generateTermReport(
            $this->student->id,
            $this->class->id,
            $this->session->id,
            $this->term->id,
            $this->school->id,
        );

        $this->assertDatabaseHas('student_term_reports', [
            'student_id' => $this->student->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
        ]);
        $this->assertNotEmpty($report->subject_scores_snapshot);
        $this->assertEquals(1, $report->position);
    }

    // ── generateClassReports ──

    public function test_generate_class_reports_creates_for_all_students(): void
    {
        $student2 = $this->createStudentWithProfile();

        $this->createScore($this->student->id, $this->ca1->id, 15);
        $this->createScore($student2->id, $this->ca1->id, 10);

        $count = $this->service->generateClassReports(
            $this->class->id,
            $this->session->id,
            $this->term->id,
            $this->school->id,
        );

        $this->assertEquals(2, $count);
        $this->assertEquals(2, StudentTermReport::withoutGlobalScopes()->count());
    }

    // ── Helpers ──

    private function createStudentWithProfile(): User
    {
        $student = User::factory()->create([
            'school_id' => $this->school->id,
            'role' => 'student',
            'level_id' => $this->level->id,
        ]);

        StudentProfile::create([
            'user_id' => $student->id,
            'school_id' => $this->school->id,
            'class_id' => $this->class->id,
        ]);

        return $student;
    }

    private function createScore(int $studentId, int $componentId, float $score, ?int $subjectId = null): StudentSubjectScore
    {
        $component = ScoreComponent::find($componentId);

        return StudentSubjectScore::create([
            'school_id' => $this->school->id,
            'student_id' => $studentId,
            'class_id' => $this->class->id,
            'subject_id' => $subjectId ?? $this->subject->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'score_component_id' => $componentId,
            'score' => $score,
            'max_score' => $component->max_score,
            'source_type' => 'manual',
        ]);
    }

    private function setUpGradingScale(): void
    {
        $scale = GradingScale::create([
            'school_id' => $this->school->id,
            'name' => 'Standard',
            'is_default' => true,
            'is_active' => true,
        ]);

        $grades = [
            ['grade' => 'A', 'label' => 'Excellent', 'min_score' => 70, 'max_score' => 100],
            ['grade' => 'B', 'label' => 'Very Good', 'min_score' => 60, 'max_score' => 69],
            ['grade' => 'C', 'label' => 'Good', 'min_score' => 50, 'max_score' => 59],
            ['grade' => 'D', 'label' => 'Fair', 'min_score' => 40, 'max_score' => 49],
            ['grade' => 'F', 'label' => 'Fail', 'min_score' => 0, 'max_score' => 39],
        ];

        foreach ($grades as $i => $g) {
            GradingScaleItem::create([
                'grading_scale_id' => $scale->id,
                'school_id' => $this->school->id,
                'grade' => $g['grade'],
                'label' => $g['label'],
                'min_score' => $g['min_score'],
                'max_score' => $g['max_score'],
                'sort_order' => $i + 1,
            ]);
        }
    }

    // ── Phase 3: Mid-Term Re-Normalization ──

    public function test_midterm_filtered_total_renormalizes_to_100(): void
    {
        // Setup: CA1 and CA2 are midterm, Exam is not
        $this->ca1->update(['include_in_midterm' => true]);
        $this->ca2->update(['include_in_midterm' => true]);
        $this->examComponent->update(['include_in_midterm' => false]);

        // CA1: 8/20 * 30 = 12.0 raw weighted
        $this->createScore($this->student->id, $this->ca1->id, 8);
        // CA2: 7/20 * 30 = 10.5 raw weighted
        $this->createScore($this->student->id, $this->ca2->id, 7);
        // Exam: 50/60 — should be excluded for midterm
        $this->createScore($this->student->id, $this->examComponent->id, 50);

        $result = $this->service->computeSubjectTotalFiltered(
            $this->student->id, $this->subject->id, $this->term->id, midtermOnly: true
        );

        // Sum of included weights = 30 + 30 = 60
        // Raw total = 12.0 + 10.5 = 22.5
        // Re-normalized = 22.5 * (100 / 60) = 37.5
        $this->assertEquals(37.5, $result['weighted_total']);
        $this->assertCount(2, $result['components']);
        $this->assertEquals(22.5, $result['raw_weighted_total']);
        $this->assertEquals(60, $result['sum_of_included_weights']);
    }

    public function test_midterm_filtered_total_falls_back_to_full_when_not_midterm(): void
    {
        $this->createScore($this->student->id, $this->ca1->id, 15);
        $this->createScore($this->student->id, $this->ca2->id, 18);
        $this->createScore($this->student->id, $this->examComponent->id, 48);

        $filtered = $this->service->computeSubjectTotalFiltered(
            $this->student->id, $this->subject->id, $this->term->id, midtermOnly: false
        );

        $full = $this->service->computeSubjectTotal(
            $this->student->id, $this->subject->id, $this->term->id
        );

        $this->assertEquals($full['weighted_total'], $filtered['weighted_total']);
    }

    public function test_midterm_example_from_spec(): void
    {
        // From the spec: CA1(10%,8/10), CA2(10%,7/10), Mid(20%,15/20), Exam(60%,50/60)
        // Create spec-matching components
        $specCa1 = ScoreComponent::create([
            'school_id' => $this->school->id,
            'name' => 'Spec CA1', 'short_name' => 'SCA1',
            'max_score' => 10, 'weight' => 10, 'sort_order' => 10,
            'is_active' => true, 'include_in_midterm' => true,
        ]);
        $specCa2 = ScoreComponent::create([
            'school_id' => $this->school->id,
            'name' => 'Spec CA2', 'short_name' => 'SCA2',
            'max_score' => 10, 'weight' => 10, 'sort_order' => 11,
            'is_active' => true, 'include_in_midterm' => true,
        ]);
        $specMid = ScoreComponent::create([
            'school_id' => $this->school->id,
            'name' => 'Mid-Term', 'short_name' => 'MID',
            'max_score' => 20, 'weight' => 20, 'sort_order' => 12,
            'is_active' => true, 'include_in_midterm' => true,
        ]);
        $specExam = ScoreComponent::create([
            'school_id' => $this->school->id,
            'name' => 'Spec Exam', 'short_name' => 'SEXM',
            'max_score' => 60, 'weight' => 60, 'sort_order' => 13,
            'is_active' => true, 'include_in_midterm' => false,
        ]);

        $subject2 = Subject::create([
            'school_id' => $this->school->id,
            'name' => 'Science', 'slug' => 'science',
            'short_name' => 'SCI', 'category' => 'science', 'is_active' => true,
        ]);

        $this->createScoreWithComponent($this->student->id, $specCa1->id, 8, $subject2->id);
        $this->createScoreWithComponent($this->student->id, $specCa2->id, 7, $subject2->id);
        $this->createScoreWithComponent($this->student->id, $specMid->id, 15, $subject2->id);
        $this->createScoreWithComponent($this->student->id, $specExam->id, 50, $subject2->id);

        $result = $this->service->computeSubjectTotalFiltered(
            $this->student->id, $subject2->id, $this->term->id, midtermOnly: true
        );

        // Included weights: 10 + 10 + 20 = 40
        // Raw: (8/10)*10 + (7/10)*10 + (15/20)*20 = 8 + 7 + 15 = 30
        // Re-normalized: 30 * (100/40) = 75.0
        $this->assertEquals(75.0, $result['weighted_total']);
        $this->assertCount(3, $result['components']);
    }

    public function test_build_snapshot_midterm_only_filters_and_renormalizes(): void
    {
        $this->setUpGradingScale();
        $this->ca1->update(['include_in_midterm' => true]);
        $this->ca2->update(['include_in_midterm' => true]);
        $this->examComponent->update(['include_in_midterm' => false]);

        $this->createScore($this->student->id, $this->ca1->id, 15); // 15/20*30 = 22.5
        $this->createScore($this->student->id, $this->ca2->id, 18); // 18/20*30 = 27
        $this->createScore($this->student->id, $this->examComponent->id, 48);

        $snapshot = $this->service->buildSubjectScoresSnapshot(
            $this->student->id, $this->class->id, $this->term->id, $this->school->id, midtermOnly: true
        );

        $this->assertCount(1, $snapshot);
        $math = $snapshot[0];

        // Only 2 midterm components should be in snapshot
        $this->assertCount(2, $math['components']);

        // Raw: 22.5 + 27 = 49.5, sum weights = 60
        // Re-normalized: 49.5 * (100/60) = 82.5
        $this->assertEquals(82.5, $math['weighted_total']);
        $this->assertEquals('A', $math['grade']); // 82.5 => Excellent
    }

    public function test_generate_term_report_with_midterm_type(): void
    {
        $this->setUpGradingScale();
        $this->ca1->update(['include_in_midterm' => true]);
        $this->ca2->update(['include_in_midterm' => true]);
        $this->examComponent->update(['include_in_midterm' => false]);

        $this->createScore($this->student->id, $this->ca1->id, 15);
        $this->createScore($this->student->id, $this->ca2->id, 18);
        $this->createScore($this->student->id, $this->examComponent->id, 48);

        $report = $this->service->generateTermReport(
            $this->student->id, $this->class->id, $this->session->id,
            $this->term->id, $this->school->id, reportType: 'midterm'
        );

        $this->assertEquals('midterm', $report->report_type);
        $this->assertEquals(1, $report->position);
        $this->assertNotEmpty($report->subject_scores_snapshot);

        // Mid-term snapshot should only have midterm components
        $math = $report->subject_scores_snapshot[0];
        $this->assertCount(2, $math['components']);
        $this->assertEquals(82.5, $math['weighted_total']);
    }

    public function test_generate_term_report_full_term_includes_all_components(): void
    {
        $this->setUpGradingScale();
        $this->ca1->update(['include_in_midterm' => true]);
        $this->ca2->update(['include_in_midterm' => true]);
        $this->examComponent->update(['include_in_midterm' => false]);

        $this->createScore($this->student->id, $this->ca1->id, 15);
        $this->createScore($this->student->id, $this->ca2->id, 18);
        $this->createScore($this->student->id, $this->examComponent->id, 48);

        $report = $this->service->generateTermReport(
            $this->student->id, $this->class->id, $this->session->id,
            $this->term->id, $this->school->id, reportType: 'full_term'
        );

        $this->assertEquals('full_term', $report->report_type);
        $math = $report->subject_scores_snapshot[0];
        $this->assertCount(3, $math['components']);
        // 22.5 + 27 + 32 = 81.5
        $this->assertEquals(81.5, $math['weighted_total']);
    }

    public function test_midterm_and_fullterm_reports_coexist(): void
    {
        $this->setUpGradingScale();
        $this->ca1->update(['include_in_midterm' => true]);
        $this->ca2->update(['include_in_midterm' => true]);
        $this->examComponent->update(['include_in_midterm' => false]);

        $this->createScore($this->student->id, $this->ca1->id, 15);
        $this->createScore($this->student->id, $this->ca2->id, 18);
        $this->createScore($this->student->id, $this->examComponent->id, 48);

        $midterm = $this->service->generateTermReport(
            $this->student->id, $this->class->id, $this->session->id,
            $this->term->id, $this->school->id, reportType: 'midterm'
        );

        $fullterm = $this->service->generateTermReport(
            $this->student->id, $this->class->id, $this->session->id,
            $this->term->id, $this->school->id, reportType: 'full_term'
        );

        $this->assertNotEquals($midterm->id, $fullterm->id);
        $this->assertEquals('midterm', $midterm->report_type);
        $this->assertEquals('full_term', $fullterm->report_type);
        $this->assertEquals(2, StudentTermReport::withoutGlobalScopes()->count());
    }

    // ── Phase 3: Session Reports ──

    public function test_session_average_of_terms(): void
    {
        $this->setUpGradingScale();
        [$term1, $term2, $term3] = $this->createThreeTerms();

        // Create full_term reports with known snapshots: 70, 80, 90
        $this->createFullTermReportWithScore($term1, 70.0);
        $this->createFullTermReportWithScore($term2, 80.0);
        $this->createFullTermReportWithScore($term3, 90.0);

        $this->createReportCardConfig('average_of_terms');

        $report = $this->service->generateSessionReport(
            $this->student->id, $this->class->id, $this->session->id, $this->school->id
        );

        $this->assertEquals('session', $report->report_type);
        $this->assertNull($report->term_id);

        $subjectEntry = $report->subject_scores_snapshot[0];
        // (70 + 80 + 90) / 3 = 80
        $this->assertEquals(80.0, $subjectEntry['session_total']);
        $this->assertEquals('A', $subjectEntry['grade']); // 80 => A
    }

    public function test_session_weighted_average(): void
    {
        $this->setUpGradingScale();
        [$term1, $term2, $term3] = $this->createThreeTerms();

        $this->createFullTermReportWithScore($term1, 70.0);
        $this->createFullTermReportWithScore($term2, 80.0);
        $this->createFullTermReportWithScore($term3, 90.0);

        // Weights: T1=30, T3=40, T2=100-30-40=30
        $this->createReportCardConfig('weighted_average', 30, 40);

        $report = $this->service->generateSessionReport(
            $this->student->id, $this->class->id, $this->session->id, $this->school->id
        );

        $subjectEntry = $report->subject_scores_snapshot[0];
        // (70*30 + 80*30 + 90*40) / 100 = (2100 + 2400 + 3600) / 100 = 81
        $this->assertEquals(81.0, $subjectEntry['session_total']);
    }

    public function test_session_best_two_of_three(): void
    {
        $this->setUpGradingScale();
        [$term1, $term2, $term3] = $this->createThreeTerms();

        $this->createFullTermReportWithScore($term1, 70.0);
        $this->createFullTermReportWithScore($term2, 80.0);
        $this->createFullTermReportWithScore($term3, 90.0);

        $this->createReportCardConfig('best_two_of_three');

        $report = $this->service->generateSessionReport(
            $this->student->id, $this->class->id, $this->session->id, $this->school->id
        );

        $subjectEntry = $report->subject_scores_snapshot[0];
        // Best two: 90, 80 → (90+80)/2 = 85
        $this->assertEquals(85.0, $subjectEntry['session_total']);
    }

    public function test_session_with_missing_term(): void
    {
        $this->setUpGradingScale();
        [$term1, $term2, $term3] = $this->createThreeTerms();

        // Only 2 terms have data
        $this->createFullTermReportWithScore($term1, 70.0);
        $this->createFullTermReportWithScore($term2, 90.0);

        $this->createReportCardConfig('average_of_terms');

        $report = $this->service->generateSessionReport(
            $this->student->id, $this->class->id, $this->session->id, $this->school->id
        );

        $subjectEntry = $report->subject_scores_snapshot[0];
        // (70 + 90) / 2 = 80
        $this->assertEquals(80.0, $subjectEntry['session_total']);
        $this->assertCount(2, $subjectEntry['term_scores']);
    }

    public function test_session_report_has_correct_overall_stats(): void
    {
        $this->setUpGradingScale();
        [$term1, $term2, $term3] = $this->createThreeTerms();

        // Create a second subject too
        $subject2 = Subject::create([
            'school_id' => $this->school->id,
            'name' => 'English', 'slug' => 'english',
            'short_name' => 'ENG', 'category' => 'arts', 'is_active' => true,
        ]);

        // Term 1: Math=70, English=60
        $this->createFullTermReportWithScores($term1, [
            $this->subject->id => 70.0,
            $subject2->id => 60.0,
        ]);
        // Term 2: Math=80, English=70
        $this->createFullTermReportWithScores($term2, [
            $this->subject->id => 80.0,
            $subject2->id => 70.0,
        ]);

        $this->createReportCardConfig('average_of_terms');

        $report = $this->service->generateSessionReport(
            $this->student->id, $this->class->id, $this->session->id, $this->school->id
        );

        // Math session = (70+80)/2 = 75, English session = (60+70)/2 = 65
        $this->assertEquals(2, $report->subjects_count);
        // Total = 75 + 65 = 140, Average = 70
        $this->assertEquals(140.0, (float) $report->total_weighted_score);
        $this->assertEquals(70.0, (float) $report->average_weighted_score);
    }

    public function test_generate_class_session_reports_computes_positions(): void
    {
        $this->setUpGradingScale();
        $student2 = $this->createStudentWithProfile();

        [$term1, $term2] = $this->createThreeTerms();

        // Student 1: Math=70 T1, Math=80 T2 → avg=75
        $this->createFullTermReportWithScore($term1, 70.0);
        $this->createFullTermReportWithScore($term2, 80.0);

        // Student 2: Math=90 T1, Math=85 T2 → avg=87.5
        $this->createFullTermReportWithScore($term1, 90.0, $student2->id);
        $this->createFullTermReportWithScore($term2, 85.0, $student2->id);

        $this->createReportCardConfig('average_of_terms');

        $count = $this->service->generateClassSessionReports(
            $this->class->id, $this->session->id, $this->school->id
        );

        $this->assertEquals(2, $count);

        $report1 = StudentTermReport::withoutGlobalScopes()
            ->where('student_id', $this->student->id)
            ->where('report_type', 'session')->first();
        $report2 = StudentTermReport::withoutGlobalScopes()
            ->where('student_id', $student2->id)
            ->where('report_type', 'session')->first();

        // Student 2 has higher average (87.5 vs 75), so position 1
        $this->assertEquals(2, $report1->position);
        $this->assertEquals(1, $report2->position);
        $this->assertEquals(2, $report1->out_of);
        $this->assertEquals(2, $report2->out_of);
    }

    public function test_session_report_subject_positions_and_averages(): void
    {
        $this->setUpGradingScale();
        $student2 = $this->createStudentWithProfile();

        [$term1] = $this->createThreeTerms();

        // Student 1: Math=70
        $this->createFullTermReportWithScore($term1, 70.0);
        // Student 2: Math=90
        $this->createFullTermReportWithScore($term1, 90.0, $student2->id);

        $this->createReportCardConfig('average_of_terms');

        $this->service->generateClassSessionReports(
            $this->class->id, $this->session->id, $this->school->id
        );

        $report1 = StudentTermReport::withoutGlobalScopes()
            ->where('student_id', $this->student->id)
            ->where('report_type', 'session')->first();

        $mathEntry = $report1->subject_scores_snapshot[0];
        $this->assertEquals(2, $mathEntry['position']); // student1 is 2nd in Math
        $this->assertEquals(80.0, $mathEntry['class_average']); // (70+90)/2
    }

    public function test_existing_full_term_tests_still_pass_with_default(): void
    {
        $this->setUpGradingScale();
        $this->createScore($this->student->id, $this->ca1->id, 15);
        $this->createScore($this->student->id, $this->examComponent->id, 48);

        // Default report type should be full_term
        $report = $this->service->generateTermReport(
            $this->student->id,
            $this->class->id,
            $this->session->id,
            $this->term->id,
            $this->school->id,
        );

        $this->assertEquals('full_term', $report->report_type);
        $this->assertDatabaseHas('student_term_reports', [
            'student_id' => $this->student->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'report_type' => 'full_term',
        ]);
    }

    // ── Phase 8: Comprehensive Edge-Case Tests ──

    public function test_midterm_positions_use_only_midterm_scores(): void
    {
        $this->setUpGradingScale();
        $this->ca1->update(['include_in_midterm' => true]);
        $this->ca2->update(['include_in_midterm' => true]);
        $this->examComponent->update(['include_in_midterm' => false]);

        $studentB = $this->createStudentWithProfile();

        // Student A: low midterm CAs but high exam (which shouldn't count for midterm)
        $this->createScore($this->student->id, $this->ca1->id, 5);
        $this->createScore($this->student->id, $this->ca2->id, 5);
        $this->createScore($this->student->id, $this->examComponent->id, 60); // ignored for midterm

        // Student B: high midterm CAs but low exam
        $this->createScore($studentB->id, $this->ca1->id, 18);
        $this->createScore($studentB->id, $this->ca2->id, 19);
        $this->createScore($studentB->id, $this->examComponent->id, 10); // ignored for midterm

        $positions = $this->service->computeOverallPositions(
            $this->class->id, $this->term->id, $this->school->id, midtermOnly: true
        );

        // Student B has higher midterm scores, should be 1st
        $this->assertEquals(1, $positions[$studentB->id]['position']);
        $this->assertEquals(2, $positions[$this->student->id]['position']);
    }

    public function test_all_zero_scores_generate_report_with_zero_and_f_grade(): void
    {
        $this->setUpGradingScale();

        // Create scores that are all zero
        $this->createScore($this->student->id, $this->ca1->id, 0);
        $this->createScore($this->student->id, $this->ca2->id, 0);
        $this->createScore($this->student->id, $this->examComponent->id, 0);

        $report = $this->service->generateTermReport(
            $this->student->id, $this->class->id, $this->session->id,
            $this->term->id, $this->school->id
        );

        $this->assertEquals(0.0, $report->total_weighted_score);
        $this->assertEquals(0.0, $report->average_weighted_score);
        $this->assertEquals(1, $report->subjects_count);

        $snapshot = $report->subject_scores_snapshot;
        $this->assertCount(1, $snapshot);
        $this->assertEquals(0.0, $snapshot[0]['weighted_total']);
        $this->assertEquals('F', $snapshot[0]['grade']);
        $this->assertEquals('Fail', $snapshot[0]['grade_label']);
    }

    public function test_class_with_no_scores_generates_zero_reports(): void
    {
        $this->setUpGradingScale();

        // No scores created — class has a student but no score entries
        $count = $this->service->generateClassReports(
            $this->class->id, $this->session->id,
            $this->term->id, $this->school->id
        );

        // Should still generate a report per student (with empty snapshot)
        $this->assertEquals(1, $count);

        $report = StudentTermReport::withoutGlobalScopes()
            ->where('student_id', $this->student->id)
            ->where('term_id', $this->term->id)
            ->first();

        $this->assertNotNull($report);
        $this->assertEquals(0.0, $report->total_weighted_score);
        $this->assertEquals(0.0, $report->average_weighted_score);
        $this->assertEquals(0, $report->subjects_count);
        $this->assertEmpty($report->subject_scores_snapshot);
    }

    // ── Helpers (Phase 3 additions) ──

    private function createScoreWithComponent(int $studentId, int $componentId, float $score, int $subjectId): StudentSubjectScore
    {
        $component = ScoreComponent::find($componentId);

        return StudentSubjectScore::create([
            'school_id' => $this->school->id,
            'student_id' => $studentId,
            'class_id' => $this->class->id,
            'subject_id' => $subjectId,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'score_component_id' => $componentId,
            'score' => $score,
            'max_score' => $component->max_score,
            'source_type' => 'manual',
        ]);
    }

    /**
     * Create 3 terms for the current session. Returns [term1, term2, term3].
     */
    private function createThreeTerms(): array
    {
        // All 3 terms already exist from SchoolSetupService — just fetch them
        $terms = Term::withoutGlobalScopes()
            ->where('school_id', $this->school->id)
            ->where('session_id', $this->session->id)
            ->orderBy('term_number')
            ->get();

        // Mark all as completed for session report tests
        foreach ($terms as $term) {
            $term->update(['status' => 'completed', 'is_current' => false]);
        }

        return [$terms[0], $terms[1], $terms[2]];
    }

    private function createReportCardConfig(string $method, float $midtermWeight = 0, float $fulltermWeight = 0): ReportCardConfig
    {
        return ReportCardConfig::updateOrCreate(
            ['school_id' => $this->school->id],
            [
                'session_calculation_method' => $method,
                'midterm_weight' => $midtermWeight,
                'fullterm_weight' => $fulltermWeight,
                'enabled_report_types' => ['full_term', 'session'],
                'show_term_breakdown_in_session' => true,
            ]
        );
    }

    /**
     * Create a full_term StudentTermReport with a known weighted_total for a single subject.
     */
    private function createFullTermReportWithScore(Term $term, float $score, ?int $studentId = null): StudentTermReport
    {
        $studentId = $studentId ?? $this->student->id;

        return StudentTermReport::create([
            'school_id' => $this->school->id,
            'student_id' => $studentId,
            'class_id' => $this->class->id,
            'session_id' => $this->session->id,
            'term_id' => $term->id,
            'report_type' => 'full_term',
            'subject_scores_snapshot' => [
                [
                    'subject_id' => $this->subject->id,
                    'subject_name' => $this->subject->name,
                    'components' => [],
                    'weighted_total' => $score,
                    'grade' => null,
                    'grade_label' => null,
                    'position' => null,
                    'class_average' => null,
                ],
            ],
            'total_weighted_score' => $score,
            'average_weighted_score' => $score,
            'subjects_count' => 1,
            'position' => 1,
            'out_of' => 1,
            'status' => 'draft',
        ]);
    }

    /**
     * Create a full_term report with multiple subjects.
     */
    private function createFullTermReportWithScores(Term $term, array $subjectScores, ?int $studentId = null): StudentTermReport
    {
        $studentId = $studentId ?? $this->student->id;
        $snapshot = [];
        $total = 0.0;

        foreach ($subjectScores as $subjectId => $score) {
            $subject = Subject::find($subjectId);
            $snapshot[] = [
                'subject_id' => $subjectId,
                'subject_name' => $subject->name,
                'components' => [],
                'weighted_total' => $score,
                'grade' => null,
                'grade_label' => null,
                'position' => null,
                'class_average' => null,
            ];
            $total += $score;
        }

        $count = count($subjectScores);

        return StudentTermReport::create([
            'school_id' => $this->school->id,
            'student_id' => $studentId,
            'class_id' => $this->class->id,
            'session_id' => $this->session->id,
            'term_id' => $term->id,
            'report_type' => 'full_term',
            'subject_scores_snapshot' => $snapshot,
            'total_weighted_score' => $total,
            'average_weighted_score' => $count > 0 ? round($total / $count, 2) : 0,
            'subjects_count' => $count,
            'position' => 1,
            'out_of' => 1,
            'status' => 'draft',
        ]);
    }
}
