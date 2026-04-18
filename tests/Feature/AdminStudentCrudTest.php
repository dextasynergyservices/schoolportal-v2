<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithSchoolContext;
use Tests\TestCase;

class AdminStudentCrudTest extends TestCase
{
    use RefreshDatabase;
    use WithSchoolContext;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpSchoolContext();
    }

    public function test_admin_can_view_students_index(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.students.index'))
            ->assertOk()
            ->assertViewIs('admin.students.index');
    }

    public function test_admin_can_view_create_student_form(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.students.create'))
            ->assertOk()
            ->assertViewIs('admin.students.create');
    }

    public function test_admin_can_create_student(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.students.store'), [
                'name' => 'John Doe',
                'username' => 'john_doe',
                'password' => 'StudentPass1!',
                'gender' => 'male',
                'level_id' => $this->level->id,
                'class_id' => $this->class->id,
            ])
            ->assertRedirect(route('admin.students.index'));

        $this->assertDatabaseHas('users', [
            'username' => 'john_doe',
            'role' => 'student',
            'school_id' => $this->school->id,
        ]);
    }

    public function test_admin_cannot_create_student_with_invalid_data(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.students.store'), [
                'name' => '',
                'username' => '',
            ])
            ->assertSessionHasErrors(['name', 'username', 'gender', 'level_id', 'class_id']);
    }

    public function test_admin_can_view_student_details(): void
    {
        $student = User::factory()->create([
            'school_id' => $this->school->id,
            'role' => 'student',
            'level_id' => $this->level->id,
        ]);

        $student->studentProfile()->create([
            'school_id' => $this->school->id,
            'class_id' => $this->class->id,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.students.show', $student))
            ->assertOk()
            ->assertViewIs('admin.students.show');
    }

    public function test_admin_can_delete_student(): void
    {
        $student = User::factory()->create([
            'school_id' => $this->school->id,
            'role' => 'student',
            'level_id' => $this->level->id,
        ]);

        $this->actingAs($this->admin)
            ->delete(route('admin.students.destroy', $student))
            ->assertRedirect(route('admin.students.index'));

        $this->assertDatabaseMissing('users', ['id' => $student->id]);
    }

    public function test_duplicate_username_is_rejected(): void
    {
        User::factory()->create([
            'school_id' => $this->school->id,
            'username' => 'existing_user',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.students.store'), [
                'name' => 'Duplicate User',
                'username' => 'existing_user',
                'password' => 'StudentPass1!',
                'gender' => 'male',
                'level_id' => $this->level->id,
                'class_id' => $this->class->id,
            ])
            ->assertSessionHasErrors('username');
    }
}
