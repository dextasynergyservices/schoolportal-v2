<?php

declare(strict_types=1);

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureModels();
        $this->configureRateLimiting();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->environment('testing')
            ? null
            : Password::min(8)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols(),
        );
    }

    /**
     * Configure Eloquent model strictness and performance guards.
     */
    protected function configureModels(): void
    {
        // Prevent lazy loading in non-production to catch N+1 queries early
        Model::preventLazyLoading(! app()->isProduction());

        // Prevent silently discarding fills on non-fillable attributes
        Model::preventSilentlyDiscardingAttributes(! app()->isProduction());

        // Prevent accessing missing attributes (catches typos)
        Model::preventAccessingMissingAttributes(! app()->isProduction());
    }

    /**
     * Configure application-wide rate limiters.
     *
     * Login/2FA limiting is handled in FortifyServiceProvider.
     * These cover other sensitive operations.
     */
    protected function configureRateLimiting(): void
    {
        // AI generation: 10 per hour per user (quiz/game generation is credit-bounded too)
        RateLimiter::for('ai-generation', function (Request $request) {
            return Limit::perHour(10)->by($request->user()?->id ?: $request->ip());
        });

        // File uploads: 30 per minute per user
        RateLimiter::for('file-upload', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });

        // Credit purchases: 5 per minute per school (financial operations)
        RateLimiter::for('credit-purchase', function (Request $request) {
            return Limit::perMinute(5)->by('school:'.$request->user()?->school_id);
        });

        // Sensitive actions (deletes, bulk operations): 10 per minute per user
        RateLimiter::for('sensitive-action', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        // Password changes: 5 per minute per user
        RateLimiter::for('password-change', function (Request $request) {
            return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
        });
    }
}
