<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ClassSubject;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithSchoolContext;
use Tests\TestCase;

class AdminSubjectTest extends TestCase
{
    use RefreshDatabase;
    use WithSchoolContext;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpSchoolContext();
    }

    public function test_admin_can_view_subjects_index(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.subjects.index'))
            ->assertOk()
            ->assertViewIs('admin.subjects.index');
    }

    public function test_admin_can_create_subject(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.subjects.store'), [
                'name' => 'Mathematics',
                'short_name' => 'MTH',
                'category' => 'science',
            ])
            ->assertRedirect(route('admin.subjects.index'));

        $this->assertDatabaseHas('subjects', [
            'name' => 'Mathematics',
            'slug' => 'mathematics',
            'school_id' => $this->school->id,
        ]);
    }

    public function test_admin_cannot_create_subject_without_name(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.subjects.store'), [
                'short_name' => 'MTH',
            ])
            ->assertSessionHasErrors('name');
    }

    public function test_admin_can_update_subject(): void
    {
        $subject = Subject::create([
            'school_id' => $this->school->id,
            'name' => 'Old Name',
            'slug' => 'old-name',
            'short_name' => 'OLD',
            'category' => 'science',
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.subjects.update', $subject), [
                'name' => 'New Name',
                'short_name' => 'NEW',
                'category' => 'arts',
            ])
            ->assertRedirect(route('admin.subjects.index'));

        $this->assertEquals('New Name', $subject->fresh()->name);
    }

    public function test_admin_can_delete_unassigned_subject(): void
    {
        $subject = Subject::create([
            'school_id' => $this->school->id,
            'name' => 'Temp Subject',
            'slug' => 'temp-subject',
            'short_name' => 'TMP',
            'category' => 'science',
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->delete(route('admin.subjects.destroy', $subject))
            ->assertRedirect(route('admin.subjects.index'));

        $this->assertDatabaseMissing('subjects', ['id' => $subject->id]);
    }

    // ── Subject-Class assignments ──

    public function test_admin_can_view_assignments_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.subjects.assignments'))
            ->assertOk()
            ->assertViewIs('admin.subjects.assignments');
    }

    public function test_admin_can_quick_assign_subjects_to_class(): void
    {
        $subject = Subject::create([
            'school_id' => $this->school->id,
            'name' => 'English',
            'slug' => 'english',
            'short_name' => 'ENG',
            'category' => 'arts',
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.subjects.quick-assign'), [
                'class_id' => $this->class->id,
                'subject_ids' => [$subject->id],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('class_subject', [
            'school_id' => $this->school->id,
            'class_id' => $this->class->id,
            'subject_id' => $subject->id,
        ]);
    }

    public function test_admin_can_remove_subject_assignment(): void
    {
        $subject = Subject::create([
            'school_id' => $this->school->id,
            'name' => 'English',
            'slug' => 'english',
            'short_name' => 'ENG',
            'category' => 'arts',
            'is_active' => true,
        ]);

        ClassSubject::create([
            'school_id' => $this->school->id,
            'class_id' => $this->class->id,
            'subject_id' => $subject->id,
        ]);

        $this->actingAs($this->admin)
            ->delete(route('admin.subjects.remove-assignment'), [
                'class_id' => $this->class->id,
                'subject_id' => $subject->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('class_subject', [
            'class_id' => $this->class->id,
            'subject_id' => $subject->id,
        ]);
    }

    public function test_teacher_cannot_access_subject_management(): void
    {
        $teacher = $this->createSchoolUser('teacher');

        $this->actingAs($teacher)
            ->get(route('admin.subjects.index'))
            ->assertForbidden();
    }
}
