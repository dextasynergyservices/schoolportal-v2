<?php

declare(strict_types=1);

namespace App\Providers;

use App\Actions\Fortify\ResetUserPassword;
use App\Http\Responses\LoginResponse;
use App\Models\School;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
    }

    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureAuthentication();
        $this->configureRateLimiting();
    }

    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        // Custom password confirmation: Fortify::username() is 'login' (a form field,
        // not a real column), so the default ConfirmPassword action fails with
        // MissingAttributeException. We confirm via Hash::check directly.
        Fortify::confirmPasswordsUsing(function (User $user, ?string $password): bool {
            return Hash::check((string) $password, $user->password);
        });
    }

    private function configureViews(): void
    {
        Fortify::loginView(fn () => view('pages::auth.login'));
        Fortify::requestPasswordResetLinkView(fn () => view('pages::auth.forgot-password'));
        Fortify::resetPasswordView(fn () => view('pages::auth.reset-password'));
        Fortify::verifyEmailView(fn () => view('pages::auth.verify-email'));
        Fortify::twoFactorChallengeView(fn () => view('pages::auth.two-factor-challenge'));
        Fortify::confirmPasswordView(fn () => view('pages::auth.confirm-password'));
    }

    /**
     * Customize authentication: support username OR email login,
     * scoped to the current school (tenant).
     */
    private function configureAuthentication(): void
    {
        Fortify::authenticateUsing(function (Request $request): ?User {
            $login = $request->input('login');
            $password = $request->input('password');

            // Resolve the current school
            $school = app()->bound('current.school') ? app('current.school') : null;

            // In local dev without a resolved school, try to find from the request
            if (! $school) {
                $schoolId = $request->input('school_id') ?? $request->session()->get('school_id');

                if ($schoolId) {
                    $school = School::withoutGlobalScopes()->where('id', $schoolId)->where('is_active', true)->first();
                }
            }

            if (! $school) {
                throw ValidationException::withMessages([
                    'login' => ['Unable to determine your school. Please check the URL.'],
                ]);
            }

            // Check if the school itself is deactivated
            if (! $school->is_active) {
                $reason = $school->deactivation_reason
                    ? __('Your school has been deactivated: :reason. Please contact the platform administrator.', ['reason' => $school->deactivation_reason])
                    : __('Your school has been deactivated. Please contact the platform administrator.');

                throw ValidationException::withMessages([
                    'login' => [$reason],
                ]);
            }

            // Store school_id in session for subsequent requests
            $request->session()->put('school_id', $school->id);

            $isEmail = filter_var($login, FILTER_VALIDATE_EMAIL);

            // Build user query scoped to the school (WITHOUT is_active filter — we check it separately)
            $query = User::withoutGlobalScopes()
                ->where('school_id', $school->id);

            if ($isEmail) {
                $query->where('email', $login);
            } else {
                $query->where('username', $login);
            }

            $user = $query->first();

            // If no user found in current school, check if they're a super_admin
            // (super_admin lives in the platform school but can log in from any domain)
            if (! $user) {
                $user = User::withoutGlobalScopes()
                    ->where('role', 'super_admin')
                    ->when($isEmail, fn ($q) => $q->where('email', $login))
                    ->when(! $isEmail, fn ($q) => $q->where('username', $login))
                    ->first();
            }

            if (! $user) {
                return null;
            }

            // Check if the user's account is deactivated — show the reason
            if (! $user->is_active) {
                $reason = $user->deactivation_reason
                    ? __('Your account has been deactivated: :reason. Please contact your school administration.', ['reason' => $user->deactivation_reason])
                    : __('Your account has been deactivated. Please contact your school administration.');

                throw ValidationException::withMessages([
                    'login' => [$reason],
                ]);
            }

            // Non-admin roles: username only, reject email login
            if ($isEmail && ! in_array($user->role, ['super_admin', 'school_admin'], true)) {
                throw ValidationException::withMessages([
                    'login' => ['Please use your username to log in.'],
                ]);
            }

            if (! Hash::check($password, $user->password)) {
                return null;
            }

            // Update last login info
            $user->update([
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
            ]);

            return $user;
        });

    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(
                Str::lower($request->input(Fortify::username())).'|'.$request->ip()
            );

            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}
