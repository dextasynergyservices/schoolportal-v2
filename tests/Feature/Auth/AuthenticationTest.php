<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Tests\Concerns\WithSchoolContext;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;
    use WithSchoolContext;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpSchoolContext();
    }

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $response = $this->withSession(['school_id' => $this->school->id])
            ->post(route('login.store'), [
                'login' => $this->admin->username,
                'password' => 'Password1!',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/portal/admin/dashboard');

        $this->assertAuthenticated();
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $response = $this->withSession(['school_id' => $this->school->id])
            ->post(route('login.store'), [
                'login' => $this->admin->username,
                'password' => 'wrong-password',
            ]);

        $this->assertGuest();
    }

    public function test_users_with_two_factor_enabled_are_redirected_to_two_factor_challenge(): void
    {
        $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

        $user = User::factory()->withTwoFactor()->create([
            'school_id' => $this->school->id,
        ]);

        $response = $this->withSession(['school_id' => $this->school->id])
            ->post(route('login.store'), [
                'login' => $user->username,
                'password' => 'password',
            ]);

        $response->assertRedirect(route('two-factor.login'));
        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $response = $this->actingAs($this->admin)->post(route('logout'));

        $response->assertRedirect(route('home'));

        $this->assertGuest();
    }

    public function test_deactivated_user_cannot_login_and_sees_reason(): void
    {
        $user = User::factory()->create([
            'school_id' => $this->school->id,
            'role' => 'student',
            'is_active' => false,
            'deactivation_reason' => 'You have been suspended for misconduct.',
            'deactivated_at' => now(),
        ]);

        $response = $this->withSession(['school_id' => $this->school->id])
            ->post(route('login.store'), [
                'login' => $user->username,
                'password' => 'password',
            ]);

        $response->assertSessionHasErrors('login');
        $this->assertGuest();
        $this->assertStringContainsString(
            'You have been suspended for misconduct.',
            session('errors')->get('login')[0],
        );
    }

    public function test_deactivated_school_blocks_login_and_shows_reason(): void
    {
        $this->school->update([
            'is_active' => false,
            'deactivation_reason' => 'Subscription expired.',
            'deactivated_at' => now(),
        ]);

        $response = $this->withSession(['school_id' => $this->school->id])
            ->post(route('login.store'), [
                'login' => $this->admin->username,
                'password' => 'Password1!',
            ]);

        $response->assertSessionHasErrors('login');
        $this->assertGuest();
        $this->assertStringContainsString(
            'Subscription expired.',
            session('errors')->get('login')[0],
        );
    }
}
