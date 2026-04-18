<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithSchoolContext;
use Tests\TestCase;

class ForcePasswordChangeTest extends TestCase
{
    use RefreshDatabase;
    use WithSchoolContext;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpSchoolContext();
    }

    public function test_user_with_must_change_password_is_redirected(): void
    {
        $user = $this->createSchoolUser('teacher', [
            'must_change_password' => true,
        ]);

        $this->actingAs($user)
            ->get('/portal/teacher/dashboard')
            ->assertRedirect(route('password.change'));
    }

    public function test_user_without_must_change_password_can_access_dashboard(): void
    {
        $user = $this->createSchoolUser('teacher');

        $this->actingAs($user)
            ->get('/portal/teacher/dashboard')
            ->assertOk();
    }

    public function test_user_with_must_change_password_can_access_change_page(): void
    {
        $user = $this->createSchoolUser('teacher', [
            'must_change_password' => true,
        ]);

        $this->actingAs($user)
            ->get(route('password.change'))
            ->assertOk();
    }

    public function test_user_can_change_password_and_access_dashboard(): void
    {
        $user = $this->createSchoolUser('teacher', [
            'must_change_password' => true,
            'password' => bcrypt('OldPassword1!'),
        ]);

        $this->actingAs($user)
            ->post(route('password.change.update'), [
                'current_password' => 'OldPassword1!',
                'password' => 'NewPassword1!',
                'password_confirmation' => 'NewPassword1!',
            ])
            ->assertRedirect();

        $user->refresh();
        $this->assertFalse($user->must_change_password);
    }
}
