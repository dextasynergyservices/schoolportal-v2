<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\Exam;
use App\Models\ExamAccessReset;
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

    public function test_student_stale_attempt_link_redirects_to_their_exam_start_page(): void
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

        $otherAttempt = ExamAttempt::create([
            'exam_id' => $this->exam->id,
            'student_id' => $otherStudent->id,
            'school_id' => $this->school->id,
            'attempt_number' => 1,
            'started_at' => now(),
            'status' => 'in_progress',
        ]);

        $this->actingAs($this->student)
            ->get(route('student.exams.take', $otherAttempt))
            ->assertRedirect();

        $this->assertDatabaseHas('exam_attempts', [
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'attempt_number' => 1,
            'status' => 'in_progress',
        ]);
    }

    public function test_student_can_start_legacy_exam_with_zero_max_attempts(): void
    {
        $this->exam->update(['max_attempts' => 0]);

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
            ->assertRedirect(route('student.exams.show', $this->exam))
            ->assertSessionHas('error');
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
        $this->assertSame('graded', $attempt->status);
        $this->assertSame(10, $attempt->score);
        $this->assertEquals(100.0, (float) $attempt->percentage);
        $this->assertNotNull($attempt->submitted_at);
    }

    public function test_objective_exam_results_show_score_immediately_after_submit(): void
    {
        $attempt = $this->startAttempt();
        $question = $this->exam->questions->first();

        $response = $this->actingAs($this->student)
            ->post(route('student.exams.submit', $attempt), [
                'answers' => [
                    $question->id => 'Water',
                ],
            ]);

        $response->assertRedirect(route('student.exams.results', $attempt));

        $attempt->refresh();
        $this->assertSame('graded', $attempt->status);

        $this->actingAs($this->student)
            ->get(route('student.exams.results', $attempt))
            ->assertOk()
            ->assertDontSee('Grading in Progress')
            ->assertSee('10/10')
            ->assertSee('100.0%');
    }

    public function test_matching_exam_results_show_score_immediately_after_submit(): void
    {
        $matchingQuestion = ExamQuestion::create([
            'exam_id' => $this->exam->id,
            'school_id' => $this->school->id,
            'type' => 'matching',
            'question_text' => 'Match each country to its capital',
            'options' => [
                ['left' => 'Nigeria', 'right' => 'Abuja'],
                ['left' => 'Ghana', 'right' => 'Accra'],
                ['left' => 'Kenya', 'right' => 'Nairobi'],
                ['left' => 'Egypt', 'right' => 'Cairo'],
            ],
            'correct_answer' => null,
            'points' => 4,
            'sort_order' => 2,
        ]);
        $this->exam->load('questions');

        $attempt = $this->startAttempt();
        $multipleChoiceQuestion = $this->exam->questions->firstWhere('type', 'multiple_choice');

        $response = $this->actingAs($this->student)
            ->post(route('student.exams.submit', $attempt), [
                'answers' => [
                    $multipleChoiceQuestion->id => 'Water',
                    $matchingQuestion->id => json_encode([
                        0 => 'Abuja',
                        1 => 'Accra',
                        2 => 'Nairobi',
                        3 => 'Cairo',
                    ]),
                ],
            ]);

        $response->assertRedirect(route('student.exams.results', $attempt));

        $attempt->refresh();
        $this->assertSame('graded', $attempt->status);
        $this->assertSame(14, $attempt->score);

        $this->actingAs($this->student)
            ->get(route('student.exams.results', $attempt))
            ->assertOk()
            ->assertDontSee('Grading in Progress')
            ->assertSee('14/14')
            ->assertSee('100.0%');
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

    public function test_attempt_pages_use_exam_category_label(): void
    {
        $this->exam->update(['category' => 'assignment', 'title' => 'English Assignment']);
        $attempt = $this->startAttempt();

        $this->actingAs($this->student)
            ->get(route('student.exams.take', $attempt))
            ->assertOk()
            ->assertViewHas('label', 'Assignment');

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
            ->assertViewHas('label', 'Assignment');
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
        $response->assertSee('You missed this exam.');
    }

    public function test_elapsed_in_progress_attempt_is_not_shown_as_resumable(): void
    {
        $this->exam->update([
            'time_limit_minutes' => 10,
            'max_attempts' => 2,
            'available_until' => now()->addHour(),
        ]);

        $attempt = ExamAttempt::create([
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'school_id' => $this->school->id,
            'attempt_number' => 1,
            'status' => 'in_progress',
            'started_at' => now()->subMinutes(20),
        ]);

        $this->actingAs($this->student)
            ->get(route('student.exams.index'))
            ->assertOk()
            ->assertDontSee('Resume')
            ->assertDontSee('Retake')
            ->assertSee('Time Elapsed')
            ->assertSee('View Results');

        $this->assertSame('timed_out', $attempt->fresh()->status);
        $this->assertSame('time_elapsed', $attempt->fresh()->completion_reason);
    }

    public function test_reset_access_allows_student_to_start_again_after_time_elapsed(): void
    {
        $this->exam->update([
            'time_limit_minutes' => 10,
            'max_attempts' => 2,
            'available_until' => now()->addHour(),
        ]);

        ExamAttempt::create([
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'school_id' => $this->school->id,
            'attempt_number' => 1,
            'status' => 'timed_out',
            'completion_reason' => 'time_elapsed',
            'started_at' => now()->subMinutes(20),
            'submitted_at' => now()->subMinutes(10),
        ]);

        ExamAccessReset::create([
            'school_id' => $this->school->id,
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'granted_by' => $this->admin->id,
            'available_from' => now()->subMinute(),
            'available_until' => now()->addHour(),
        ]);

        $this->actingAs($this->student)
            ->get(route('student.exams.index'))
            ->assertOk()
            ->assertSee('Reset access')
            ->assertSee('Start Again')
            ->assertDontSee('Retake');

        $this->assertTrue($this->exam->fresh()->canStudentAttempt($this->student->id));
    }

    public function test_active_reset_allows_a_fresh_attempt_on_a_closed_exam(): void
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
            'status' => 'timed_out',
            'started_at' => now()->subHours(2),
            'submitted_at' => now()->subHour(),
        ]);

        $reset = ExamAccessReset::create([
            'school_id' => $this->school->id,
            'exam_id' => $closedExam->id,
            'student_id' => $this->student->id,
            'granted_by' => $this->admin->id,
            'available_from' => now()->subMinute(),
            'available_until' => now()->addHour(),
        ]);

        $this->actingAs($this->student)
            ->get(route('student.exams.index'))
            ->assertOk()
            ->assertSee('Reset access')
            ->assertSee($closedExam->title);

        $this->actingAs($this->student)
            ->post(route('student.exams.start', $closedExam))
            ->assertRedirect();

        $freshAttempt = ExamAttempt::where('exam_id', $closedExam->id)
            ->where('student_id', $this->student->id)
            ->where('attempt_number', 2)
            ->firstOrFail();

        $this->assertSame('in_progress', $freshAttempt->status);
        $this->assertSame($freshAttempt->id, $reset->fresh()->attempt_id);
        $this->assertNotNull($reset->fresh()->used_at);
    }

    public function test_reset_access_does_not_apply_to_another_student(): void
    {
        $closedExam = $this->createPublishedExamWithWindow(
            availableFrom: now()->subDays(2),
            availableUntil: now()->subHour(),
        );

        ExamAccessReset::create([
            'school_id' => $this->school->id,
            'exam_id' => $closedExam->id,
            'student_id' => $this->student->id,
            'granted_by' => $this->admin->id,
            'available_from' => now()->subMinute(),
            'available_until' => now()->addHour(),
        ]);

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

        $this->actingAs($otherStudent)
            ->post(route('student.exams.start', $closedExam))
            ->assertRedirect(route('student.exams.show', $closedExam))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('exam_attempts', [
            'exam_id' => $closedExam->id,
            'student_id' => $otherStudent->id,
        ]);
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
