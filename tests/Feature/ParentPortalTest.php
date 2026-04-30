<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ParentStudent;
use App\Models\StudentProfile;
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

    public function test_parent_can_view_notices(): void
    {
        $this->actingAs($this->parent)
            ->get(route('parent.notices.index'))
            ->assertOk();
    }
}
