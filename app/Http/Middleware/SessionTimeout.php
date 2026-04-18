<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SessionTimeout
{
    /**
     * Check if the user's session has exceeded the school's configured timeout.
     *
     * Falls back to the SESSION_LIFETIME .env value if no school setting exists.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return $next($request);
        }

        $lastActivity = $request->session()->get('last_activity');

        if ($lastActivity) {
            $school = app()->bound('current.school') ? app('current.school') : null;
            $timeoutMinutes = $school?->setting('portal.session_timeout_minutes') ?? (int) config('session.lifetime');
            $timeoutSeconds = $timeoutMinutes * 60;

            if (time() - $lastActivity > $timeoutSeconds) {
                auth()->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->with('status', 'Your session has expired. Please log in again.');
            }
        }

        // Update last activity timestamp
        $request->session()->put('last_activity', time());

        return $next($request);
    }
}
