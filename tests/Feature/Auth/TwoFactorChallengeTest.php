<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Tests\Concerns\WithSchoolContext;
use Tests\TestCase;

class TwoFactorChallengeTest extends TestCase
{
    use RefreshDatabase;
    use WithSchoolContext;

    protected function setUp(): void
    {
        parent::setUp();
        $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());
        $this->setUpSchoolContext();
    }

    public function test_two_factor_challenge_redirects_to_login_when_not_authenticated(): void
    {
        $response = $this->get(route('two-factor.login'));

        $response->assertRedirect(route('login'));
    }

    public function test_two_factor_challenge_can_be_rendered(): void
    {
        $user = User::factory()->withTwoFactor()->create([
            'school_id' => $this->school->id,
        ]);

        $this->withSession(['school_id' => $this->school->id])
            ->post(route('login.store'), [
                'login' => $user->username,
                'password' => 'password',
            ])->assertRedirect(route('two-factor.login'));
    }
}
