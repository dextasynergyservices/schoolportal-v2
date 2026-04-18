<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithSchoolContext;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;
    use WithSchoolContext;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpSchoolContext();
    }

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_admin_is_redirected_to_admin_dashboard(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('dashboard'));
        $response->assertRedirect('/portal/admin/dashboard');
    }

    public function test_authenticated_teacher_is_redirected_to_teacher_dashboard(): void
    {
        $teacher = $this->createSchoolUser('teacher');

        $this->actingAs($teacher);

        $response = $this->get(route('dashboard'));
        $response->assertRedirect('/portal/teacher/dashboard');
    }

    public function test_authenticated_student_is_redirected_to_student_dashboard(): void
    {
        $student = $this->createSchoolUser('student');

        $this->actingAs($student);

        $response = $this->get(route('dashboard'));
        $response->assertRedirect('/portal/student/dashboard');
    }

    public function test_authenticated_parent_is_redirected_to_parent_dashboard(): void
    {
        $parent = $this->createSchoolUser('parent');

        $this->actingAs($parent);

        $response = $this->get(route('dashboard'));
        $response->assertRedirect('/portal/parent/dashboard');
    }
}
