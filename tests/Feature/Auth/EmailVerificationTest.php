<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Laravel\Fortify\Features;
use Tests\Concerns\WithSchoolContext;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;
    use WithSchoolContext;

    protected function setUp(): void
    {
        parent::setUp();
        $this->skipUnlessFortifyHas(Features::emailVerification());
        $this->setUpSchoolContext();
    }

    public function test_already_verified_user_visiting_notice_is_redirected(): void
    {
        // All users on this platform are auto-verified on creation (see User::booted).
        // Fortify's EmailVerificationPromptController redirects verified users away.
        $user = User::factory()->create([
            'school_id' => $this->school->id,
        ]);

        $this->assertTrue($user->hasVerifiedEmail());

        $response = $this->actingAs($user)->get(route('verification.notice'));

        $response->assertRedirect();
    }

    public function test_already_verified_user_visiting_verification_link_is_redirected(): void
    {
        // Users are auto-verified on creation; markEmailAsVerified() returns false (already
        // verified) so the Verified event is NOT dispatched, but Fortify still redirects.
        $user = User::factory()->create([
            'school_id' => $this->school->id,
        ]);

        $this->assertTrue($user->hasVerifiedEmail());

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)],
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertNotDispatched(Verified::class);

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    }

    public function test_invalid_hash_does_not_affect_already_verified_user(): void
    {
        // Users are auto-verified on creation; an invalid hash cannot un-verify them.
        $user = User::factory()->create([
            'school_id' => $this->school->id,
        ]);

        $this->assertTrue($user->hasVerifiedEmail());

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')],
        );

        $this->actingAs($user)->get($verificationUrl);

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_already_verified_user_visiting_verification_link_is_redirected_without_firing_event_again(): void
    {
        $user = User::factory()->create([
            'school_id' => $this->school->id,
            'email_verified_at' => now(),
        ]);

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)],
        );

        $this->actingAs($user)->get($verificationUrl)
            ->assertRedirect(route('dashboard', absolute: false).'?verified=1');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        Event::assertNotDispatched(Verified::class);
    }
}
