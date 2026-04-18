<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Features;
use Livewire\Livewire;
use Tests\Concerns\WithSchoolContext;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;
    use WithSchoolContext;

    protected function setUp(): void
    {
        parent::setUp();
        $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]);

        $this->setUpSchoolContext();
    }

    public function test_security_settings_page_can_be_rendered(): void
    {
        $this->actingAs($this->admin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('security.edit'))
            ->assertOk()
            ->assertSee('Two-factor authentication')
            ->assertSee('Enable 2FA');
    }

    public function test_security_settings_page_requires_password_confirmation_when_enabled(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('security.edit'));

        $response->assertRedirect(route('password.confirm'));
    }

    public function test_security_settings_page_renders_without_two_factor_when_feature_is_disabled(): void
    {
        config(['fortify.features' => []]);

        $this->actingAs($this->admin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('security.edit'))
            ->assertOk()
            ->assertSee('Update password')
            ->assertDontSee('Two-factor authentication');
    }

    public function test_two_factor_authentication_disabled_when_confirmation_abandoned_between_requests(): void
    {
        $this->admin->forceFill([
            'two_factor_secret' => encrypt('test-secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
            'two_factor_confirmed_at' => null,
        ])->save();

        $this->actingAs($this->admin);

        $component = Livewire::test('pages::settings.security');

        $component->assertSet('twoFactorEnabled', false);

        $this->assertDatabaseHas('users', [
            'id' => $this->admin->id,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
        ]);
    }

    public function test_password_can_be_updated(): void
    {
        $this->actingAs($this->admin);

        $response = Livewire::test('pages::settings.security')
            ->set('current_password', 'Password1!')
            ->set('password', 'NewPassword1!')
            ->set('password_confirmation', 'NewPassword1!')
            ->call('updatePassword');

        $response->assertHasNoErrors();

        $this->assertTrue(Hash::check('NewPassword1!', $this->admin->refresh()->password));
    }

    public function test_correct_password_must_be_provided_to_update_password(): void
    {
        $this->actingAs($this->admin);

        $response = Livewire::test('pages::settings.security')
            ->set('current_password', 'wrong-password')
            ->set('password', 'NewPassword1!')
            ->set('password_confirmation', 'NewPassword1!')
            ->call('updatePassword');

        $response->assertHasErrors(['current_password']);
    }
}
