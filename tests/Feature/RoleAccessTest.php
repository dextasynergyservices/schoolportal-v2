<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithSchoolContext;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;
    use WithSchoolContext;

    private User $teacher;

    private User $student;

    private User $parent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpSchoolContext();

        $this->teacher = $this->createSchoolUser('teacher');
        $this->student = $this->createSchoolUser('student');
        $this->parent = $this->createSchoolUser('parent', ['level_id' => null]);
    }

    // ── Unauthenticated Access ──

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $this->get('/portal/admin/dashboard')->assertRedirect('/portal/login');
        $this->get('/portal/teacher/dashboard')->assertRedirect('/portal/login');
        $this->get('/portal/student/dashboard')->assertRedirect('/portal/login');
        $this->get('/portal/parent/dashboard')->assertRedirect('/portal/login');
    }

    // ── Admin Routes ──

    public function test_admin_can_access_admin_dashboard(): void
    {
        $this->actingAs($this->admin)
            ->get('/portal/admin/dashboard')
            ->assertOk();
    }

    public function test_teacher_cannot_access_admin_routes(): void
    {
        $this->actingAs($this->teacher)
            ->get('/portal/admin/dashboard')
            ->assertForbidden();
    }

    public function test_student_cannot_access_admin_routes(): void
    {
        $this->actingAs($this->student)
            ->get('/portal/admin/dashboard')
            ->assertForbidden();
    }

    public function test_parent_cannot_access_admin_routes(): void
    {
        $this->actingAs($this->parent)
            ->get('/portal/admin/dashboard')
            ->assertForbidden();
    }

    // ── Teacher Routes ──

    public function test_teacher_can_access_teacher_dashboard(): void
    {
        $this->actingAs($this->teacher)
            ->get('/portal/teacher/dashboard')
            ->assertOk();
    }

    public function test_student_cannot_access_teacher_routes(): void
    {
        $this->actingAs($this->student)
            ->get('/portal/teacher/dashboard')
            ->assertForbidden();
    }

    // ── Student Routes ──

    public function test_student_can_access_student_dashboard(): void
    {
        $this->actingAs($this->student)
            ->get('/portal/student/dashboard')
            ->assertOk();
    }

    public function test_teacher_cannot_access_student_routes(): void
    {
        $this->actingAs($this->teacher)
            ->get('/portal/student/dashboard')
            ->assertForbidden();
    }

    // ── Parent Routes ──

    public function test_parent_can_access_parent_dashboard(): void
    {
        $this->actingAs($this->parent)
            ->get('/portal/parent/dashboard')
            ->assertOk();
    }

    public function test_admin_cannot_access_parent_routes(): void
    {
        $this->actingAs($this->admin)
            ->get('/portal/parent/dashboard')
            ->assertForbidden();
    }

    // ── Dashboard Redirect ──

    public function test_dashboard_redirects_admin_to_admin_dashboard(): void
    {
        $this->actingAs($this->admin)
            ->get('/portal/dashboard')
            ->assertRedirect('/portal/admin/dashboard');
    }

    public function test_dashboard_redirects_teacher_to_teacher_dashboard(): void
    {
        $this->actingAs($this->teacher)
            ->get('/portal/dashboard')
            ->assertRedirect('/portal/teacher/dashboard');
    }

    public function test_dashboard_redirects_student_to_student_dashboard(): void
    {
        $this->actingAs($this->student)
            ->get('/portal/dashboard')
            ->assertRedirect('/portal/student/dashboard');
    }

    public function test_dashboard_redirects_parent_to_parent_dashboard(): void
    {
        $this->actingAs($this->parent)
            ->get('/portal/dashboard')
            ->assertRedirect('/portal/parent/dashboard');
    }
}
