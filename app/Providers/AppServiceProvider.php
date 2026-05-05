<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Assignment;
use App\Models\Result;
use App\Models\SchoolClass;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
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
        $this->configureCacheInvalidation();
        $this->configureQueueAlerts();
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

    /**
     * Invalidate per-school analytics and dashboard caches when underlying
     * data changes. Uses a version counter so all cached permutations
     * (different session/term filters) are busted at once — compatible with
     * both file and Redis cache drivers.
     */
    protected function configureCacheInvalidation(): void
    {
        $bumpAnalytics = static function (mixed $model): void {
            /** @var Model $model */
            $schoolId = $model->getAttribute('school_id');
            if (! $schoolId) {
                return;
            }
            Cache::increment("school:{$schoolId}:analytics:version");
            Cache::forget("school:{$schoolId}:dashboard:stats");
        };

        // Results uploaded/approved — affects results progress chart.
        Result::created($bumpAnalytics);
        Result::deleted($bumpAnalytics);
        Result::updated(static function (Result $model) use ($bumpAnalytics): void {
            // Only bust when approval status changes to avoid noisy invalidations.
            if ($model->isDirty('status')) {
                $bumpAnalytics($model);
            }
        });

        // Assignments uploaded/approved — affects assignment coverage chart.
        Assignment::created($bumpAnalytics);
        Assignment::deleted($bumpAnalytics);
        Assignment::updated(static function (Assignment $model) use ($bumpAnalytics): void {
            if ($model->isDirty('status')) {
                $bumpAnalytics($model);
            }
        });

        // Students/teachers added or removed — affects headcounts and level breakdown.
        User::created(static function (User $model) use ($bumpAnalytics): void {
            if (in_array($model->role, ['student', 'teacher'], true)) {
                $bumpAnalytics($model);
            }
        });
        User::deleted(static function (User $model) use ($bumpAnalytics): void {
            if (in_array($model->role, ['student', 'teacher'], true)) {
                $bumpAnalytics($model);
            }
        });
        User::updated(static function (User $model) use ($bumpAnalytics): void {
            // Bust when role, gender, level_id or is_active changes (affects counts).
            if ($model->isDirty(['role', 'gender', 'level_id', 'is_active'])) {
                $bumpAnalytics($model);
            }
        });

        // Class added, removed, or capacity changed — affects class occupancy chart.
        SchoolClass::created($bumpAnalytics);
        SchoolClass::deleted($bumpAnalytics);
        SchoolClass::updated(static function (SchoolClass $model) use ($bumpAnalytics): void {
            if ($model->isDirty(['capacity', 'is_active'])) {
                $bumpAnalytics($model);
            }
        });
    }

    /**
     * Send an alert email when a queued job permanently fails.
     *
     * Uses synchronous Mail::raw() to avoid recursive queueing.
     */
    protected function configureQueueAlerts(): void
    {
        Queue::failing(function (JobFailed $event): void {
            $alertEmail = config('services.platform.alert_email');

            Log::critical('Queue job permanently failed.', [
                'job' => $event->job->resolveName(),
                'connection' => $event->connectionName,
                'exception' => $event->exception->getMessage(),
            ]);

            if (! $alertEmail) {
                return;
            }

            try {
                $jobName = $event->job->resolveName();
                $error = $event->exception->getMessage();
                $time = now()->toDateTimeString();

                Mail::raw(
                    "A queued job permanently failed and has been moved to the failed_jobs table.\n\n"
                    ."Job:  {$jobName}\n"
                    ."Error: {$error}\n"
                    ."Time:  {$time}\n\n"
                    .'Please check the SchoolPortal failed_jobs table for details.',
                    static function (Message $message) use ($alertEmail, $jobName): void {
                        $message
                            ->to($alertEmail)
                            ->subject("[SchoolPortal] Queue Job Failed: {$jobName}");
                    }
                );
            } catch (\Throwable $e) {
                Log::error('Failed to send queue failure alert email.', [
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }
}
