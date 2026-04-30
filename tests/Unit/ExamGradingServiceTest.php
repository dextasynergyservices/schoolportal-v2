<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\AcademicSession;
use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Models\ExamQuestion;
use App\Models\ScoreComponent;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use App\Services\ExamGradingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithSchoolContext;
use Tests\TestCase;

class ExamGradingServiceTest extends TestCase
{
    use RefreshDatabase;
    use WithSchoolContext;

    private ExamGradingService $service;

    private Exam $exam;

    private User $student;

    private AcademicSession $session;

    private Term $term;

    private Subject $subject;

    private ScoreComponent $component;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpSchoolContext();
        $this->service = app(ExamGradingService::class);

        $this->session = AcademicSession::withoutGlobalScopes()
            ->where('school_id', $this->school->id)->first();
        $this->term = Term::withoutGlobalScopes()
            ->where('school_id', $this->school->id)->first();

        $this->subject = Subject::create([
            'school_id' => $this->school->id,
            'name' => 'Science',
            'slug' => 'science',
            'short_name' => 'SCI',
            'category' => 'science',
            'is_active' => true,
        ]);

        $this->component = ScoreComponent::create([
            'school_id' => $this->school->id,
            'name' => 'Exam',
            'short_name' => 'EXM',
            'max_score' => 60,
            'weight' => 40,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->exam = Exam::create([
            'school_id' => $this->school->id,
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'score_component_id' => $this->component->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'title' => 'Science Exam',
            'category' => 'exam',
            'source_type' => 'manual',
            'max_score' => 100,
            'total_points' => 20,
            'passing_score' => 50,
            'status' => 'approved',
            'is_published' => true,
            'created_by' => $this->admin->id,
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

    // ── gradeAttempt — all objective ──

    public function test_grade_attempt_scores_multiple_choice_correctly(): void
    {
        $q1 = $this->createQuestion('multiple_choice', 'What is H2O?', 'Water', 10);
        $q2 = $this->createQuestion('multiple_choice', 'What is CO2?', 'Carbon dioxide', 10);

        $attempt = $this->createAttempt();

        // Answer Q1 correctly, Q2 incorrectly
        ExamAnswer::create([
            'attempt_id' => $attempt->id,
            'question_id' => $q1->id,
            'school_id' => $this->school->id,
            'selected_answer' => 'Water',
            'answered_at' => now(),
        ]);

        ExamAnswer::create([
            'attempt_id' => $attempt->id,
            'question_id' => $q2->id,
            'school_id' => $this->school->id,
            'selected_answer' => 'Nitrogen',
            'answered_at' => now(),
        ]);

        $graded = $this->service->gradeAttempt($attempt);

        $this->assertEquals(10, $graded->score);
        $this->assertEquals(50.0, $graded->percentage);
        $this->assertTrue($graded->passed);
        // gradeAttempt doesn't change status — controller handles submission status
        $this->assertNotEquals('grading', $graded->status);
    }

    public function test_grade_attempt_is_case_insensitive(): void
    {
        $q = $this->createQuestion('multiple_choice', 'Capital of Nigeria?', 'Abuja', 10);

        $attempt = $this->createAttempt();

        ExamAnswer::create([
            'attempt_id' => $attempt->id,
            'question_id' => $q->id,
            'school_id' => $this->school->id,
            'selected_answer' => 'abuja',
            'answered_at' => now(),
        ]);

        $graded = $this->service->gradeAttempt($attempt);

        $this->assertEquals(10, $graded->score);
    }

    public function test_grade_attempt_handles_true_false(): void
    {
        $q = $this->createQuestion('true_false', 'Water boils at 100C?', 'True', 5);

        $attempt = $this->createAttempt();

        ExamAnswer::create([
            'attempt_id' => $attempt->id,
            'question_id' => $q->id,
            'school_id' => $this->school->id,
            'selected_answer' => 'True',
            'answered_at' => now(),
        ]);

        $graded = $this->service->gradeAttempt($attempt);

        $this->assertEquals(5, $graded->score);
    }

    public function test_grade_attempt_handles_fill_blank_with_variants(): void
    {
        $q = ExamQuestion::create([
            'exam_id' => $this->exam->id,
            'school_id' => $this->school->id,
            'type' => 'fill_blank',
            'question_text' => 'The capital of France is ___',
            'options' => [],
            'correct_answer' => 'Paris|paris',
            'points' => 10,
            'sort_order' => 1,
        ]);

        $attempt = $this->createAttempt();

        ExamAnswer::create([
            'attempt_id' => $attempt->id,
            'question_id' => $q->id,
            'school_id' => $this->school->id,
            'selected_answer' => 'paris',
            'answered_at' => now(),
        ]);

        $graded = $this->service->gradeAttempt($attempt);

        $this->assertEquals(10, $graded->score);
    }

    // ── gradeAttempt — with theory ──

    public function test_grade_attempt_sets_grading_status_for_theory(): void
    {
        $q1 = $this->createQuestion('multiple_choice', 'What is H2O?', 'Water', 10);
        $q2 = ExamQuestion::create([
            'exam_id' => $this->exam->id,
            'school_id' => $this->school->id,
            'type' => 'theory',
            'question_text' => 'Explain photosynthesis',
            'options' => [],
            'correct_answer' => '',
            'points' => 10,
            'sort_order' => 2,
        ]);

        $attempt = $this->createAttempt();

        ExamAnswer::create([
            'attempt_id' => $attempt->id,
            'question_id' => $q1->id,
            'school_id' => $this->school->id,
            'selected_answer' => 'Water',
            'answered_at' => now(),
        ]);

        ExamAnswer::create([
            'attempt_id' => $attempt->id,
            'question_id' => $q2->id,
            'school_id' => $this->school->id,
            'theory_answer' => 'Plants use sunlight to make food.',
            'answered_at' => now(),
        ]);

        $graded = $this->service->gradeAttempt($attempt);

        $this->assertEquals('grading', $graded->status);
        $this->assertNull($graded->percentage);
        $this->assertEquals(10, $graded->score); // Only objective graded
    }

    // ── recalculateAttemptScore ──

    public function test_recalculate_after_manual_grading_finalizes(): void
    {
        $q1 = $this->createQuestion('multiple_choice', 'What is H2O?', 'Water', 10);
        $q2 = ExamQuestion::create([
            'exam_id' => $this->exam->id,
            'school_id' => $this->school->id,
            'type' => 'theory',
            'question_text' => 'Explain photosynthesis',
            'options' => [],
            'correct_answer' => '',
            'points' => 10,
            'sort_order' => 2,
        ]);

        $attempt = $this->createAttempt();

        // Objective answer — already graded
        ExamAnswer::create([
            'attempt_id' => $attempt->id,
            'question_id' => $q1->id,
            'school_id' => $this->school->id,
            'selected_answer' => 'Water',
            'is_correct' => true,
            'points_earned' => 10,
            'answered_at' => now(),
        ]);

        // Theory answer — manually graded
        ExamAnswer::create([
            'attempt_id' => $attempt->id,
            'question_id' => $q2->id,
            'school_id' => $this->school->id,
            'theory_answer' => 'Plants use sunlight to make food.',
            'is_correct' => true,
            'points_earned' => 8,
            'answered_at' => now(),
            'graded_by' => $this->admin->id,
            'graded_at' => now(),
        ]);

        $attempt->update(['status' => 'grading', 'score' => 10]);

        $result = $this->service->recalculateAttemptScore($attempt);

        $this->assertEquals('graded', $result->status);
        $this->assertEquals(18, $result->score);
        $this->assertEquals(90.0, $result->percentage); // 18/20 * 100
        $this->assertTrue($result->passed);
    }

    // ── Auto-update subject score ──

    public function test_grade_attempt_auto_updates_subject_score(): void
    {
        $q = $this->createQuestion('multiple_choice', 'What is H2O?', 'Water', 20);

        $attempt = $this->createAttempt();

        ExamAnswer::create([
            'attempt_id' => $attempt->id,
            'question_id' => $q->id,
            'school_id' => $this->school->id,
            'selected_answer' => 'Water',
            'answered_at' => now(),
        ]);

        $this->service->gradeAttempt($attempt);

        // 20/20 total_points * 60 (component max) = 60
        $this->assertDatabaseHas('student_subject_scores', [
            'student_id' => $this->student->id,
            'subject_id' => $this->subject->id,
            'score_component_id' => $this->component->id,
            'score' => 60.00,
            'source_type' => 'cbt',
        ]);
    }

    // ── Helpers ──

    private function createQuestion(string $type, string $text, string $answer, int $points): ExamQuestion
    {
        return ExamQuestion::create([
            'exam_id' => $this->exam->id,
            'school_id' => $this->school->id,
            'type' => $type,
            'question_text' => $text,
            'options' => $type === 'multiple_choice' ? ['Water', 'Nitrogen', 'Oxygen', 'Hydrogen'] : ($type === 'true_false' ? ['True', 'False'] : []),
            'correct_answer' => $answer,
            'points' => $points,
            'sort_order' => ExamQuestion::where('exam_id', $this->exam->id)->count() + 1,
        ]);
    }

    private function createAttempt(): ExamAttempt
    {
        return ExamAttempt::create([
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'school_id' => $this->school->id,
            'attempt_number' => 1,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
    }
}
