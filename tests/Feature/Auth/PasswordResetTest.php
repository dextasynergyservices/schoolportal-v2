<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;
use Tests\Concerns\WithSchoolContext;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;
    use WithSchoolContext;

    protected function setUp(): void
    {
        parent::setUp();
        $this->skipUnlessFortifyHas(Features::resetPasswords());
        $this->setUpSchoolContext();
    }

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get(route('password.request'));

        $response->assertOk();
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $this->post(route('password.request'), ['email' => $this->admin->email]);

        Notification::assertSentTo($this->admin, ResetPassword::class);
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $this->post(route('password.request'), ['email' => $this->admin->email]);

        Notification::assertSentTo($this->admin, ResetPassword::class, function ($notification) {
            $response = $this->get(route('password.reset', $notification->token));

            $response->assertOk();

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $this->post(route('password.request'), ['email' => $this->admin->email]);

        Notification::assertSentTo($this->admin, ResetPassword::class, function ($notification) {
            $response = $this->post(route('password.update'), [
                'token' => $notification->token,
                'email' => $this->admin->email,
                'password' => 'NewPassword1!',
                'password_confirmation' => 'NewPassword1!',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login', absolute: false));

            return true;
        });
    }
}
