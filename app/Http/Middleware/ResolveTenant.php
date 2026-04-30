<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\School;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    /**
     * Resolve the current school (tenant) from the request's Host header
     * and bind it into the application container.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // If user is already authenticated, resolve from their school_id
        if ($request->user()) {
            $school = School::withoutGlobalScopes()->find($request->user()->school_id);

            if (! $school || ! $school->is_active) {
                auth()->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                $reason = $school?->deactivation_reason
                    ? __('Your school has been deactivated: :reason. Please contact the platform administrator.', ['reason' => $school->deactivation_reason])
                    : __('Your school account has been deactivated. Please contact the platform administrator.');

                abort(403, $reason);
            }

            $this->bindSchool($school);

            return $next($request);
        }

        // For unauthenticated requests, resolve from Host header (custom domain)
        $host = $request->getHost();

        // In local/testing environments, skip domain resolution (use session or container binding)
        if (app()->environment('local', 'testing') && (in_array($host, ['localhost', '127.0.0.1']) || str_ends_with($host, '.test'))) {
            // If a school is already bound (e.g. in tests), keep it
            if (app()->bound('current.school')) {
                return $next($request);
            }

            // Check if we have a school in the session (set during login)
            $schoolId = $request->session()->get('school_id');

            if ($schoolId) {
                $school = School::withoutGlobalScopes()->where('id', $schoolId)->where('is_active', true)->first();

                if ($school) {
                    $this->bindSchool($school);

                    return $next($request);
                }
            }

            // No school resolved yet — in local dev, auto-resolve the school
            // Exclude the platform meta-school (slug='platform') used only by super_admin
            $nonPlatformSchools = School::withoutGlobalScopes()
                ->where('is_active', true)
                ->where('slug', '!=', 'platform')
                ->get();

            if ($nonPlatformSchools->count() === 1) {
                $school = $nonPlatformSchools->first();
                $this->bindSchool($school);
                $request->session()->put('school_id', $school->id);

                return $next($request);
            }

            // Multiple schools or none — allow access to login page where school will be selected
            return $next($request);
        }

        // Production: resolve school from custom domain
        // Strip www. prefix — schools register bare domains (e.g., portal.double6.store)
        $host = preg_replace('/^www\./', '', $host);

        $school = School::withoutGlobalScopes()
            ->where('custom_domain', $host)
            ->where('is_active', true)
            ->first();

        if ($school) {
            $this->bindSchool($school);

            return $next($request);
        }

        // Check if this is the platform's own domain (from APP_URL)
        $platformHost = parse_url(config('app.url', ''), PHP_URL_HOST);

        if ($host === $platformHost) {
            // Platform domain — auto-resolve if only one non-platform school exists
            $nonPlatformSchools = School::withoutGlobalScopes()
                ->where('is_active', true)
                ->where('slug', '!=', 'platform')
                ->get();

            if ($nonPlatformSchools->count() === 1) {
                $this->bindSchool($nonPlatformSchools->first());
            }

            // Allow through — landing page and login will work
            return $next($request);
        }

        // Unknown domain — not platform, not a registered school
        abort(404, 'School not found. Please check the URL and try again.');
    }

    /**
     * Bind the resolved school into the container and set the app timezone.
     */
    private function bindSchool(School $school): void
    {
        app()->instance('current.school', $school);

        // Set timezone to the school's configured timezone
        $timezone = $school->timezone ?? 'Africa/Lagos';
        config(['app.timezone' => $timezone]);
        date_default_timezone_set($timezone);
    }
}
