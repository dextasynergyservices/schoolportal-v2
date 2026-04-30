<?php

declare(strict_types=1);

namespace Tests\Feature;

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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithSchoolContext;
use Tests\TestCase;

class StudentExamTest extends TestCase
{
    use RefreshDatabase;
    use WithSchoolContext;

    private User $student;

    private Exam $exam;

    private AcademicSession $session;

    private Term $term;

    private Subject $subject;

    private ScoreComponent $component;

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

        $this->subject = Subject::create([
            'school_id' => $this->school->id,
            'name' => 'English',
            'slug' => 'english',
            'short_name' => 'ENG',
            'category' => 'arts',
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

        $this->exam = $this->createPublishedExam();
    }

    public function test_student_can_view_exams_index(): void
    {
        $this->actingAs($this->student)
            ->get(route('student.exams.index'))
            ->assertOk()
            ->assertViewIs('student.exams.index');
    }

    public function test_student_can_view_published_exam(): void
    {
        $this->actingAs($this->student)
            ->get(route('student.exams.show', $this->exam))
            ->assertOk()
            ->assertViewIs('student.exams.show');
    }

    public function test_student_cannot_view_unpublished_exam(): void
    {
        $this->exam->update(['is_published' => false]);

        $this->actingAs($this->student)
            ->get(route('student.exams.show', $this->exam))
            ->assertForbidden();
    }

    public function test_student_can_start_exam(): void
    {
        $this->actingAs($this->student)
            ->post(route('student.exams.start', $this->exam))
            ->assertRedirect();

        $this->assertDatabaseHas('exam_attempts', [
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'attempt_number' => 1,
            'status' => 'in_progress',
        ]);
    }

    public function test_student_cannot_exceed_max_attempts(): void
    {
        // Use up all attempts
        ExamAttempt::create([
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'school_id' => $this->school->id,
            'attempt_number' => 1,
            'status' => 'submitted',
            'started_at' => now(),
            'submitted_at' => now(),
            'score' => 5,
            'total_points' => 10,
            'percentage' => 50.0,
            'passed' => true,
        ]);

        $this->actingAs($this->student)
            ->post(route('student.exams.start', $this->exam))
            ->assertForbidden();
    }

    public function test_student_can_save_answer(): void
    {
        $attempt = $this->startAttempt();
        $question = $this->exam->questions->first();
        $answer = ExamAnswer::where('attempt_id', $attempt->id)
            ->where('question_id', $question->id)
            ->first();

        $this->actingAs($this->student)
            ->postJson(route('student.exams.save-answer', $attempt), [
                'question_id' => $question->id,
                'selected_answer' => 'Water',
            ])
            ->assertOk();

        $this->assertEquals('Water', $answer->fresh()->selected_answer);
    }

    public function test_student_can_submit_exam(): void
    {
        $attempt = $this->startAttempt();

        // Save an answer first
        $question = $this->exam->questions->first();
        ExamAnswer::where('attempt_id', $attempt->id)
            ->where('question_id', $question->id)
            ->update(['selected_answer' => 'Water', 'answered_at' => now()]);

        $this->actingAs($this->student)
            ->post(route('student.exams.submit', $attempt))
            ->assertRedirect();

        $attempt->refresh();
        $this->assertContains($attempt->status, ['submitted', 'grading']);
        $this->assertNotNull($attempt->submitted_at);
    }

    public function test_student_can_view_results(): void
    {
        $attempt = $this->startAttempt();
        $attempt->update([
            'status' => 'submitted',
            'submitted_at' => now(),
            'score' => 8,
            'total_points' => 10,
            'percentage' => 80.0,
            'passed' => true,
        ]);

        $this->actingAs($this->student)
            ->get(route('student.exams.results', $attempt))
            ->assertOk()
            ->assertViewIs('student.exams.results');
    }

    public function test_student_cannot_access_other_students_attempt(): void
    {
        $otherStudent = User::factory()->create([
            'school_id' => $this->school->id,
            'role' => 'student',
            'level_id' => $this->level->id,
        ]);

        StudentProfile::create([
            'user_id' => $otherStudent->id,
            'school_id' => $this->school->id,
            'class_id' => $this->class->id,
        ]);

        $attempt = ExamAttempt::create([
            'exam_id' => $this->exam->id,
            'student_id' => $otherStudent->id,
            'school_id' => $this->school->id,
            'attempt_number' => 1,
            'status' => 'submitted',
            'started_at' => now(),
            'submitted_at' => now(),
        ]);

        $this->actingAs($this->student)
            ->get(route('student.exams.results', $attempt))
            ->assertForbidden();
    }

    public function test_admin_cannot_access_student_exam_routes(): void
    {
        $this->actingAs($this->admin)
            ->get(route('student.exams.index'))
            ->assertForbidden();
    }

    // ── Closed exam scope tests ──

    public function test_scope_closed_returns_exam_past_deadline(): void
    {
        $closedExam = $this->createPublishedExamWithWindow(
            availableFrom: now()->subDays(3),
            availableUntil: now()->subHour(),
        );

        $result = Exam::closed()->where('school_id', $this->school->id)->get();

        $this->assertTrue($result->contains($closedExam));
    }

    public function test_scope_closed_excludes_currently_open_exam(): void
    {
        $openExam = $this->createPublishedExamWithWindow(
            availableFrom: now()->subHour(),
            availableUntil: now()->addHour(),
        );

        $result = Exam::closed()->where('school_id', $this->school->id)->get();

        $this->assertFalse($result->contains($openExam));
    }

    public function test_scope_closed_excludes_exam_with_no_deadline(): void
    {
        // $this->exam has no available_until — should never appear in closed
        $result = Exam::closed()->where('school_id', $this->school->id)->get();

        $this->assertFalse($result->contains($this->exam));
    }

    // ── Closed section feature tests ──

    public function test_student_sees_closed_exam_with_score_in_index(): void
    {
        $closedExam = $this->createPublishedExamWithWindow(
            availableFrom: now()->subDays(2),
            availableUntil: now()->subHour(),
        );

        ExamAttempt::create([
            'exam_id' => $closedExam->id,
            'student_id' => $this->student->id,
            'school_id' => $this->school->id,
            'attempt_number' => 1,
            'status' => 'submitted',
            'started_at' => now()->subDays(2),
            'submitted_at' => now()->subDays(2),
            'score' => 8,
            'total_points' => 10,
            'percentage' => 80.0,
            'passed' => true,
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('student.exams.index'));

        $response->assertOk();
        $response->assertViewHas('closed', fn ($closed) => $closed->contains($closedExam));
        $response->assertSee('80%');
        $response->assertSee('Passed');
        $response->assertSee('View Results');
    }

    public function test_student_sees_missed_badge_for_unattempted_closed_exam(): void
    {
        $closedExam = $this->createPublishedExamWithWindow(
            availableFrom: now()->subDays(2),
            availableUntil: now()->subHour(),
        );

        $response = $this->actingAs($this->student)
            ->get(route('student.exams.index'));

        $response->assertOk();
        $response->assertViewHas('closed', fn ($closed) => $closed->contains($closedExam));
        $response->assertSee('Missed');
        $response->assertSee('You did not attempt this item.');
    }

    public function test_empty_state_shown_only_when_all_four_buckets_are_empty(): void
    {
        $this->exam->update(['is_published' => false]);

        $response = $this->actingAs($this->student)
            ->get(route('student.exams.index'));

        $response->assertOk();
        $response->assertViewHas('exams', fn ($e) => $e->isEmpty());
        $response->assertViewHas('upcoming', fn ($c) => $c->isEmpty());
        $response->assertViewHas('closed', fn ($c) => $c->isEmpty());
        $response->assertSee('No items available');
    }

    public function test_empty_state_not_shown_when_closed_exam_exists(): void
    {
        $this->exam->update(['is_published' => false]);

        $this->createPublishedExamWithWindow(
            availableFrom: now()->subDays(2),
            availableUntil: now()->subHour(),
        );

        $response = $this->actingAs($this->student)
            ->get(route('student.exams.index'));

        $response->assertOk();
        $response->assertDontSee('No items available');
    }

    // ── Helpers ──

    private function createPublishedExam(): Exam
    {
        $exam = Exam::create([
            'school_id' => $this->school->id,
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'score_component_id' => $this->component->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'title' => 'English Exam',
            'category' => 'exam',
            'source_type' => 'manual',
            'max_score' => 100,
            'total_points' => 10,
            'passing_score' => 50,
            'max_attempts' => 1,
            'status' => 'approved',
            'is_published' => true,
            'published_at' => now(),
            'created_by' => $this->admin->id,
            'approved_by' => $this->admin->id,
            'approved_at' => now(),
        ]);

        ExamQuestion::create([
            'exam_id' => $exam->id,
            'school_id' => $this->school->id,
            'type' => 'multiple_choice',
            'question_text' => 'What is H2O?',
            'options' => ['Water', 'Oxygen', 'Carbon', 'Nitrogen'],
            'correct_answer' => 'Water',
            'points' => 10,
            'sort_order' => 1,
        ]);

        $exam->load('questions');

        return $exam;
    }

    private function createPublishedExamWithWindow(
        ?\DateTimeInterface $availableFrom = null,
        ?\DateTimeInterface $availableUntil = null,
        string $title = 'Scheduled Exam',
    ): Exam {
        $exam = Exam::create([
            'school_id' => $this->school->id,
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'score_component_id' => $this->component->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'title' => $title,
            'category' => 'exam',
            'source_type' => 'manual',
            'max_score' => 100,
            'total_points' => 10,
            'passing_score' => 50,
            'max_attempts' => 1,
            'status' => 'approved',
            'is_published' => true,
            'published_at' => now()->subDays(3),
            'available_from' => $availableFrom,
            'available_until' => $availableUntil,
            'created_by' => $this->admin->id,
            'approved_by' => $this->admin->id,
            'approved_at' => now()->subDays(3),
        ]);

        ExamQuestion::create([
            'exam_id' => $exam->id,
            'school_id' => $this->school->id,
            'type' => 'multiple_choice',
            'question_text' => 'What is H2O?',
            'options' => ['Water', 'Oxygen', 'Carbon', 'Nitrogen'],
            'correct_answer' => 'Water',
            'points' => 10,
            'sort_order' => 1,
        ]);

        $exam->load('questions');

        return $exam;
    }

    private function startAttempt(): ExamAttempt
    {
        // Use the controller's start action to create a proper attempt with answer slots
        $response = $this->actingAs($this->student)
            ->post(route('student.exams.start', $this->exam));

        return ExamAttempt::where('exam_id', $this->exam->id)
            ->where('student_id', $this->student->id)
            ->where('status', 'in_progress')
            ->firstOrFail();
    }
}
