<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Game;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\SchoolClass;
use App\Models\ScoreComponent;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithSchoolContext;
use Tests\TestCase;

class StudentActivityClassIsolationTest extends TestCase
{
    use RefreshDatabase;
    use WithSchoolContext;

    private User $student;

    private StudentProfile $profile;

    private SchoolClass $otherClass;

    private Exam $ownExam;

    private Exam $otherExam;

    private Quiz $ownQuiz;

    private Quiz $otherQuiz;

    private Game $ownGame;

    private Game $otherGame;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpSchoolContext();

        $this->student = $this->createSchoolUser('student');
        $this->profile = StudentProfile::create([
            'user_id' => $this->student->id,
            'school_id' => $this->school->id,
            'class_id' => $this->class->id,
        ]);

        $this->otherClass = SchoolClass::create([
            'school_id' => $this->school->id,
            'level_id' => $this->level->id,
            'name' => 'Other Class',
            'slug' => 'other-class-activity-isolation',
            'is_active' => true,
        ]);

        $session = AcademicSession::withoutGlobalScopes()->where('school_id', $this->school->id)->firstOrFail();
        $term = Term::withoutGlobalScopes()->where('school_id', $this->school->id)->firstOrFail();
        $subject = Subject::create([
            'school_id' => $this->school->id,
            'name' => 'Activity Subject',
            'slug' => 'activity-subject',
            'is_active' => true,
        ]);
        $component = ScoreComponent::create([
            'school_id' => $this->school->id,
            'name' => 'Activity Exam',
            'short_name' => 'AEX',
            'max_score' => 100,
            'weight' => 100,
            'is_active' => true,
        ]);

        $this->ownExam = $this->createExam('My Class CBT', $this->class, $session, $term, $subject, $component);
        $this->otherExam = $this->createExam('Other Class CBT', $this->otherClass, $session, $term, $subject, $component);
        $this->ownQuiz = $this->createQuiz('My Class Quiz', $this->class, $session, $term);
        $this->otherQuiz = $this->createQuiz('Other Class Quiz', $this->otherClass, $session, $term);
        $this->ownGame = $this->createGame('My Class Game', $this->class, $session, $term);
        $this->otherGame = $this->createGame('Other Class Game', $this->otherClass, $session, $term);
    }

    public function test_student_lists_and_dashboard_show_only_current_class_activities(): void
    {
        $this->actingAs($this->student)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('My Class CBT')
            ->assertSee('My Class Quiz')
            ->assertSee('My Class Game')
            ->assertDontSee('Other Class CBT')
            ->assertDontSee('Other Class Quiz')
            ->assertDontSee('Other Class Game');

        $this->actingAs($this->student)
            ->get(route('student.exams.index'))
            ->assertOk()
            ->assertSee('My Class CBT')
            ->assertDontSee('Other Class CBT');

        $this->actingAs($this->student)
            ->get(route('student.quizzes.index'))
            ->assertOk()
            ->assertSee('My Class Quiz')
            ->assertDontSee('Other Class Quiz');

        $this->actingAs($this->student)
            ->get(route('student.games.index'))
            ->assertOk()
            ->assertSee('My Class Game')
            ->assertDontSee('Other Class Game');
    }

    public function test_student_cannot_open_or_start_another_class_activity_by_url(): void
    {
        $this->actingAs($this->student)
            ->get(route('student.exams.show', $this->otherExam))
            ->assertForbidden();

        $this->actingAs($this->student)
            ->post(route('student.quizzes.start', $this->otherQuiz))
            ->assertForbidden();

        $this->actingAs($this->student)
            ->get(route('student.games.play', $this->otherGame))
            ->assertForbidden();

        $this->actingAs($this->student)
            ->get(route('student.games.leaderboard', $this->otherGame))
            ->assertForbidden();
    }

    public function test_active_attempt_urls_are_blocked_after_student_moves_to_another_class(): void
    {
        $examAttempt = ExamAttempt::create([
            'exam_id' => $this->ownExam->id,
            'student_id' => $this->student->id,
            'school_id' => $this->school->id,
            'attempt_number' => 1,
            'started_at' => now(),
            'status' => 'in_progress',
        ]);
        $quizAttempt = QuizAttempt::create([
            'quiz_id' => $this->ownQuiz->id,
            'student_id' => $this->student->id,
            'school_id' => $this->school->id,
            'attempt_number' => 1,
            'started_at' => now(),
            'status' => 'in_progress',
        ]);

        $this->profile->update(['class_id' => $this->otherClass->id]);
        $this->student->unsetRelation('studentProfile');

        $this->actingAs($this->student)
            ->get(route('student.exams.take', $examAttempt))
            ->assertForbidden();

        $this->actingAs($this->student)
            ->get(route('student.quizzes.take', $quizAttempt))
            ->assertForbidden();
    }

    private function createExam(
        string $title,
        SchoolClass $class,
        AcademicSession $session,
        Term $term,
        Subject $subject,
        ScoreComponent $component,
    ): Exam {
        return Exam::create([
            'school_id' => $this->school->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'score_component_id' => $component->id,
            'session_id' => $session->id,
            'term_id' => $term->id,
            'title' => $title,
            'category' => 'exam',
            'source_type' => 'manual',
            'max_score' => 100,
            'passing_score' => 50,
            'max_attempts' => 1,
            'status' => 'approved',
            'is_published' => true,
            'published_at' => now(),
            'created_by' => $this->admin->id,
        ]);
    }

    private function createQuiz(string $title, SchoolClass $class, AcademicSession $session, Term $term): Quiz
    {
        return Quiz::create([
            'school_id' => $this->school->id,
            'class_id' => $class->id,
            'session_id' => $session->id,
            'term_id' => $term->id,
            'title' => $title,
            'source_type' => 'manual',
            'passing_score' => 50,
            'max_attempts' => 1,
            'is_published' => true,
            'published_at' => now(),
            'status' => 'approved',
            'created_by' => $this->admin->id,
        ]);
    }

    private function createGame(string $title, SchoolClass $class, AcademicSession $session, Term $term): Game
    {
        return Game::create([
            'school_id' => $this->school->id,
            'class_id' => $class->id,
            'session_id' => $session->id,
            'term_id' => $term->id,
            'title' => $title,
            'game_type' => 'flashcard',
            'source_type' => 'manual',
            'game_data' => [],
            'difficulty' => 'easy',
            'is_published' => true,
            'published_at' => now(),
            'status' => 'approved',
            'created_by' => $this->admin->id,
        ]);
    }
}
