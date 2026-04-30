<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\ScoreComponent;
use App\Models\Subject;
use App\Models\Term;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithSchoolContext;
use Tests\TestCase;

class AdminExamTest extends TestCase
{
    use RefreshDatabase;
    use WithSchoolContext;

    private Subject $subject;

    private ScoreComponent $component;

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
    }

    public function test_admin_can_view_exams_index(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.exams.index'))
            ->assertOk()
            ->assertViewIs('admin.exams.index');
    }

    public function test_admin_can_view_create_form(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.exams.create'))
            ->assertOk()
            ->assertViewIs('admin.exams.create');
    }

    public function test_admin_can_store_exam_with_questions(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.exams.store'), [
                'title' => 'Mid-Term Science Exam',
                'class_id' => $this->class->id,
                'subject_id' => $this->subject->id,
                'score_component_id' => $this->component->id,
                'source_type' => 'manual',
                'max_score' => 100,
                'passing_score' => 50,
                'max_attempts' => 1,
                'questions' => [
                    [
                        'type' => 'multiple_choice',
                        'question_text' => 'What is H2O?',
                        'options' => ['Water', 'Oxygen', 'Carbon', 'Nitrogen'],
                        'correct_answer' => 'Water',
                        'points' => 5,
                    ],
                    [
                        'type' => 'true_false',
                        'question_text' => 'The sun is a star.',
                        'options' => ['True', 'False'],
                        'correct_answer' => 'True',
                        'points' => 5,
                    ],
                ],
            ])
            ->assertRedirect(route('admin.exams.index'));

        $this->assertDatabaseHas('exams', [
            'title' => 'Mid-Term Science Exam',
            'school_id' => $this->school->id,
            'status' => 'approved', // admin-created → auto-approved
        ]);

        $exam = Exam::withoutGlobalScopes()
            ->where('title', 'Mid-Term Science Exam')->first();

        $this->assertCount(2, $exam->questions);
    }

    public function test_admin_can_view_exam_details(): void
    {
        $exam = $this->createExam();

        $this->actingAs($this->admin)
            ->get(route('admin.exams.show', $exam))
            ->assertOk()
            ->assertViewIs('admin.exams.show');
    }

    public function test_admin_can_publish_exam(): void
    {
        $exam = $this->createExam();

        $this->actingAs($this->admin)
            ->post(route('admin.exams.publish', $exam))
            ->assertRedirect(route('admin.exams.index'));

        $this->assertTrue($exam->fresh()->is_published);
    }

    public function test_admin_can_unpublish_exam(): void
    {
        $exam = $this->createExam(['is_published' => true, 'published_at' => now()]);

        $this->actingAs($this->admin)
            ->post(route('admin.exams.unpublish', $exam))
            ->assertRedirect(route('admin.exams.index'));

        $this->assertFalse($exam->fresh()->is_published);
    }

    public function test_store_requires_questions(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.exams.store'), [
                'title' => 'Empty Exam',
                'class_id' => $this->class->id,
                'subject_id' => $this->subject->id,
                'source_type' => 'manual',
                'max_score' => 100,
                'passing_score' => 50,
                'max_attempts' => 1,
            ]);

        // Validation failure re-renders the form view with errors (instead of redirect, to preserve data)
        $response->assertStatus(200);
        $response->assertViewHas('errors');
        $this->assertTrue($response->viewData('errors')->has('questions'));
    }

    public function test_teacher_cannot_access_admin_exam_routes(): void
    {
        $teacher = $this->createSchoolUser('teacher');

        $this->actingAs($teacher)
            ->get(route('admin.exams.index'))
            ->assertForbidden();
    }

    // ── Helpers ──

    private function createExam(array $overrides = []): Exam
    {
        $exam = Exam::create(array_merge([
            'school_id' => $this->school->id,
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'score_component_id' => $this->component->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'title' => 'Test Exam',
            'category' => 'exam',
            'source_type' => 'manual',
            'max_score' => 100,
            'total_points' => 10,
            'passing_score' => 50,
            'status' => 'approved',
            'created_by' => $this->admin->id,
            'approved_by' => $this->admin->id,
            'approved_at' => now(),
        ], $overrides));

        ExamQuestion::create([
            'exam_id' => $exam->id,
            'school_id' => $this->school->id,
            'type' => 'multiple_choice',
            'question_text' => 'Sample question?',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answer' => 'A',
            'points' => 10,
            'sort_order' => 1,
        ]);

        return $exam;
    }
}
