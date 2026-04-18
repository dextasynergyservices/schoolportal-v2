<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    /**
     * Redirect users who must change their password before accessing the portal.
     *
     * Users with must_change_password=true are only allowed to access
     * the password change page and the logout route.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->must_change_password) {
            return $next($request);
        }

        // Allow access to password change and logout routes
        $allowedRoutes = ['password.change', 'password.change.update', 'logout'];

        if (in_array($request->route()?->getName(), $allowedRoutes, true)) {
            return $next($request);
        }

        return redirect()->route('password.change')
            ->with('status', 'You must change your password before continuing.');
    }
}
