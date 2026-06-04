<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ClassSubject;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithSchoolContext;
use Tests\TestCase;

class TeacherSubjectTest extends TestCase
{
    use RefreshDatabase;
    use WithSchoolContext;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpSchoolContext();

        $this->teacher = $this->createSchoolUser('teacher');
        $this->class->update(['teacher_id' => $this->teacher->id]);
    }

    public function test_teacher_can_view_searchable_school_subject_pool(): void
    {
        Subject::create([
            'school_id' => $this->school->id,
            'created_by' => $this->admin->id,
            'name' => 'Mathematics',
            'slug' => 'mathematics',
            'is_active' => true,
        ]);

        $this->actingAs($this->teacher)
            ->get(route('teacher.subjects.create'))
            ->assertOk()
            ->assertSee('Assign Existing Subject')
            ->assertSee('Search subjects')
            ->assertSee('x-model.debounce.100ms="search"', false)
            ->assertViewHas('subjects', fn ($subjects) => $subjects->contains('name', 'Mathematics'));
    }

    public function test_teacher_can_create_subject_for_assigned_classes(): void
    {
        $secondClass = SchoolClass::create([
            'school_id' => $this->school->id,
            'level_id' => $this->level->id,
            'teacher_id' => $this->teacher->id,
            'name' => 'Second Class',
            'slug' => 'second-class',
        ]);

        $this->actingAs($this->teacher)
            ->post(route('teacher.subjects.store'), [
                'name' => 'Agricultural Science',
                'short_name' => 'AGRIC',
                'class_ids' => [$this->class->id, $secondClass->id],
            ])
            ->assertRedirect(route('teacher.subjects.index'));

        $subject = Subject::where('slug', 'agricultural-science')->firstOrFail();

        $this->assertSame($this->teacher->id, $subject->created_by);
        $this->assertDatabaseHas('class_subject', [
            'class_id' => $this->class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $this->teacher->id,
        ]);
        $this->assertDatabaseHas('class_subject', [
            'class_id' => $secondClass->id,
            'subject_id' => $subject->id,
            'teacher_id' => $this->teacher->id,
        ]);
    }

    public function test_teacher_cannot_create_subject_for_unassigned_class(): void
    {
        $otherClass = SchoolClass::create([
            'school_id' => $this->school->id,
            'level_id' => $this->level->id,
            'name' => 'Unassigned Class',
            'slug' => 'unassigned-class',
        ]);

        $this->actingAs($this->teacher)
            ->post(route('teacher.subjects.store'), [
                'name' => 'Unauthorized Subject',
                'class_ids' => [$otherClass->id],
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('subjects', ['slug' => 'unauthorized-subject']);
    }

    public function test_teacher_is_told_to_assign_subject_when_name_already_exists(): void
    {
        Subject::create([
            'school_id' => $this->school->id,
            'created_by' => $this->admin->id,
            'name' => 'Mathematics',
            'slug' => 'mathematics',
            'is_active' => true,
        ]);

        $this->actingAs($this->teacher)
            ->post(route('teacher.subjects.store'), [
                'name' => 'Mathematics',
                'class_ids' => [$this->class->id],
            ])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseMissing('class_subject', [
            'class_id' => $this->class->id,
            'subject_id' => Subject::where('slug', 'mathematics')->value('id'),
        ]);
    }

    public function test_teacher_can_assign_existing_subject_from_pool_to_assigned_class(): void
    {
        $subject = Subject::create([
            'school_id' => $this->school->id,
            'created_by' => $this->admin->id,
            'name' => 'Mathematics',
            'slug' => 'mathematics',
            'is_active' => true,
        ]);

        $this->actingAs($this->teacher)
            ->post(route('teacher.subjects.assign'), [
                'subject_id' => $subject->id,
                'class_ids' => [$this->class->id],
            ])
            ->assertRedirect(route('teacher.subjects.index'));

        $this->assertDatabaseHas('class_subject', [
            'class_id' => $this->class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $this->teacher->id,
        ]);
    }

    public function test_teacher_cannot_assign_existing_subject_to_unassigned_class(): void
    {
        $otherClass = SchoolClass::create([
            'school_id' => $this->school->id,
            'level_id' => $this->level->id,
            'name' => 'Unassigned Pool Class',
            'slug' => 'unassigned-pool-class',
        ]);
        $subject = Subject::create([
            'school_id' => $this->school->id,
            'created_by' => $this->admin->id,
            'name' => 'Mathematics',
            'slug' => 'mathematics',
            'is_active' => true,
        ]);

        $this->actingAs($this->teacher)
            ->post(route('teacher.subjects.assign'), [
                'subject_id' => $subject->id,
                'class_ids' => [$otherClass->id],
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('class_subject', [
            'class_id' => $otherClass->id,
            'subject_id' => $subject->id,
        ]);
    }

    public function test_teacher_can_delete_own_unused_subject_from_own_classes(): void
    {
        $subject = $this->createTeacherSubject();

        $this->actingAs($this->teacher)
            ->delete(route('teacher.subjects.destroy', $subject))
            ->assertRedirect(route('teacher.subjects.index'));

        $this->assertDatabaseMissing('subjects', ['id' => $subject->id]);
    }

    public function test_teacher_cannot_delete_subject_created_by_admin(): void
    {
        $subject = Subject::create([
            'school_id' => $this->school->id,
            'created_by' => $this->admin->id,
            'name' => 'Admin Subject',
            'slug' => 'admin-subject',
            'is_active' => true,
        ]);

        ClassSubject::create([
            'school_id' => $this->school->id,
            'class_id' => $this->class->id,
            'subject_id' => $subject->id,
        ]);

        $this->actingAs($this->teacher)
            ->delete(route('teacher.subjects.destroy', $subject))
            ->assertForbidden();

        $this->assertDatabaseHas('subjects', ['id' => $subject->id]);
    }

    private function createTeacherSubject(): Subject
    {
        $subject = Subject::create([
            'school_id' => $this->school->id,
            'created_by' => $this->teacher->id,
            'name' => 'Teacher Subject',
            'slug' => 'teacher-subject',
            'is_active' => true,
        ]);

        ClassSubject::create([
            'school_id' => $this->school->id,
            'class_id' => $this->class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $this->teacher->id,
        ]);

        return $subject;
    }
}
