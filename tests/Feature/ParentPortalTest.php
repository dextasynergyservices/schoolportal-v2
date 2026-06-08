<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\GradingScale;
use App\Models\GradingScaleItem;
use App\Models\ParentStudent;
use App\Models\SchoolClass;
use App\Models\SchoolLevel;
use App\Models\ScoreComponent;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithSchoolContext;
use Tests\TestCase;

class ParentPortalTest extends TestCase
{
    use RefreshDatabase;
    use WithSchoolContext;

    protected User $parent;

    protected User $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpSchoolContext();

        $this->parent = User::factory()->create([
            'school_id' => $this->school->id,
            'role' => 'parent',
            'must_change_password' => false,
            'email_verified_at' => now(),
        ]);

        $this->student = User::factory()->create([
            'school_id' => $this->school->id,
            'role' => 'student',
            'level_id' => $this->level->id,
            'must_change_password' => false,
        ]);

        StudentProfile::create([
            'user_id' => $this->student->id,
            'school_id' => $this->school->id,
            'class_id' => $this->class->id,
        ]);

        ParentStudent::create([
            'parent_id' => $this->parent->id,
            'student_id' => $this->student->id,
            'school_id' => $this->school->id,
        ]);
    }

    public function test_parent_can_access_dashboard(): void
    {
        $this->actingAs($this->parent)
            ->get(route('parent.dashboard'))
            ->assertOk();
    }

    public function test_teacher_cannot_access_parent_dashboard(): void
    {
        $teacher = $this->createSchoolUser('teacher');

        $this->actingAs($teacher)
            ->get(route('parent.dashboard'))
            ->assertForbidden();
    }

    public function test_student_cannot_access_parent_dashboard(): void
    {
        $this->actingAs($this->student)
            ->get(route('parent.dashboard'))
            ->assertForbidden();
    }

    public function test_parent_can_view_linked_child_profile(): void
    {
        $this->actingAs($this->parent)
            ->get(route('parent.children.show', $this->student))
            ->assertOk();
    }

    public function test_parent_cannot_view_unlinked_child(): void
    {
        $unlinked = User::factory()->create([
            'school_id' => $this->school->id,
            'role' => 'student',
            'level_id' => $this->level->id,
            'must_change_password' => false,
        ]);

        $this->actingAs($this->parent)
            ->get(route('parent.children.show', $unlinked))
            ->assertForbidden();
    }

    public function test_parent_can_view_child_results(): void
    {
        $this->actingAs($this->parent)
            ->get(route('parent.children.results', $this->student))
            ->assertOk();
    }

    public function test_parent_can_view_child_assignments(): void
    {
        $this->actingAs($this->parent)
            ->get(route('parent.children.assignments', $this->student))
            ->assertOk();
    }

    public function test_parent_can_view_child_quiz_results(): void
    {
        $this->actingAs($this->parent)
            ->get(route('parent.children.quizzes', $this->student))
            ->assertOk();
    }

    public function test_parent_can_view_child_game_stats(): void
    {
        $this->actingAs($this->parent)
            ->get(route('parent.children.games', $this->student))
            ->assertOk();
    }

    public function test_parent_can_view_child_cbt_results(): void
    {
        $this->actingAs($this->parent)
            ->get(route('parent.children.cbt-results', $this->student))
            ->assertOk();
    }

    public function test_parent_child_cbt_results_use_exam_class_level_grading_scale(): void
    {
        $secondaryLevel = SchoolLevel::create([
            'school_id' => $this->school->id,
            'name' => 'Secondary',
            'slug' => 'secondary',
            'sort_order' => 2,
            'is_active' => true,
        ]);
        $secondaryClass = SchoolClass::create([
            'school_id' => $this->school->id,
            'level_id' => $secondaryLevel->id,
            'name' => 'Secondary 1',
            'slug' => 'secondary-1',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $primaryScale = $this->createGradingScale('Primary Scale', 'Primary Excellent', $this->level->id);
        $this->createGradingScale('Secondary Scale', 'Secondary A1', $secondaryLevel->id);

        StudentProfile::where('user_id', $this->student->id)->update([
            'class_id' => $secondaryClass->id,
        ]);
        $this->student->update(['level_id' => $secondaryLevel->id]);

        $session = AcademicSession::withoutGlobalScopes()->where('school_id', $this->school->id)->firstOrFail();
        $term = Term::withoutGlobalScopes()->where('school_id', $this->school->id)->firstOrFail();
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
            'weight' => 40,
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $exam = Exam::create([
            'school_id' => $this->school->id,
            'class_id' => $this->class->id,
            'subject_id' => $subject->id,
            'score_component_id' => $component->id,
            'session_id' => $session->id,
            'term_id' => $term->id,
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
        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $this->student->id,
            'school_id' => $this->school->id,
            'attempt_number' => 1,
            'status' => 'graded',
            'started_at' => now()->subMinutes(5),
            'submitted_at' => now(),
            'score' => 8,
            'total_points' => 10,
            'percentage' => 82.0,
            'passed' => true,
        ]);

        $this->actingAs($this->parent)
            ->get(route('parent.children.cbt-results', $this->student))
            ->assertOk()
            ->assertViewHas('grades', fn ($grades): bool => ($grades[$attempt->id]['label'] ?? null) === 'Primary Excellent'
                && ($grades[$attempt->id]['grading_scale_id'] ?? null) === $primaryScale->id);
    }

    public function test_parent_can_view_notices(): void
    {
        $this->actingAs($this->parent)
            ->get(route('parent.notices.index'))
            ->assertOk();
    }

    private function createGradingScale(string $name, string $excellentLabel, int $levelId): GradingScale
    {
        $scale = GradingScale::create([
            'school_id' => $this->school->id,
            'name' => $name,
            'is_default' => false,
            'is_active' => true,
        ]);

        foreach ([
            ['grade' => 'A', 'label' => $excellentLabel, 'min_score' => 80, 'max_score' => 100],
            ['grade' => 'B', 'label' => 'Good', 'min_score' => 50, 'max_score' => 79],
            ['grade' => 'F', 'label' => 'Needs Improvement', 'min_score' => 0, 'max_score' => 49],
        ] as $index => $item) {
            GradingScaleItem::create([
                'grading_scale_id' => $scale->id,
                'school_id' => $this->school->id,
                'grade' => $item['grade'],
                'label' => $item['label'],
                'min_score' => $item['min_score'],
                'max_score' => $item['max_score'],
                'sort_order' => $index,
            ]);
        }

        $scale->levels()->attach($levelId, ['school_id' => $this->school->id]);

        return $scale;
    }
}
