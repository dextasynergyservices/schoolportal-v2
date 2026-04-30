<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\SchoolClass;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithSchoolContext;
use Tests\TestCase;

class AdminClassCrudTest extends TestCase
{
    use RefreshDatabase;
    use WithSchoolContext;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpSchoolContext();
    }

    public function test_admin_can_view_classes_index(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.classes.index'))
            ->assertOk()
            ->assertViewIs('admin.classes.index');
    }

    public function test_admin_can_view_create_form(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.classes.create'))
            ->assertOk()
            ->assertViewIs('admin.classes.create');
    }

    public function test_admin_can_create_class(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.classes.store'), [
                'name' => 'JSS 1',
                'level_id' => $this->level->id,
            ])
            ->assertRedirect(route('admin.classes.index'));

        $this->assertDatabaseHas('classes', [
            'name' => 'JSS 1',
            'slug' => 'jss-1',
            'school_id' => $this->school->id,
        ]);
    }

    public function test_admin_cannot_create_class_without_name(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.classes.store'), [
                'level_id' => $this->level->id,
            ])
            ->assertSessionHasErrors('name');
    }

    public function test_admin_can_update_class(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.classes.update', $this->class), [
                'name' => 'Updated Name',
                'level_id' => $this->level->id,
            ])
            ->assertRedirect(route('admin.classes.index'));

        $this->assertEquals('Updated Name', $this->class->fresh()->name);
    }

    public function test_admin_can_delete_class_without_students(): void
    {
        $emptyClass = SchoolClass::create([
            'school_id' => $this->school->id,
            'level_id' => $this->level->id,
            'name' => 'Empty Class',
            'slug' => 'empty-class',
        ]);

        $this->actingAs($this->admin)
            ->delete(route('admin.classes.destroy', $emptyClass))
            ->assertRedirect(route('admin.classes.index'));

        $this->assertDatabaseMissing('classes', ['id' => $emptyClass->id]);
    }

    public function test_admin_cannot_delete_class_with_students(): void
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

        $this->actingAs($this->admin)
            ->delete(route('admin.classes.destroy', $this->class))
            ->assertRedirect(route('admin.classes.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('classes', ['id' => $this->class->id]);
    }

    public function test_teacher_cannot_access_class_crud(): void
    {
        $teacher = $this->createSchoolUser('teacher');

        $this->actingAs($teacher)
            ->get(route('admin.classes.index'))
            ->assertForbidden();
    }
}
