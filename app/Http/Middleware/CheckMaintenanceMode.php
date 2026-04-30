<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\PlatformSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    public function handle(Request $request, Closure $next): Response
    {
        // Feature is off → pass straight through
        if (! PlatformSetting::get('maintenance_mode', false)) {
            return $next($request);
        }

        // Super admins always bypass maintenance mode
        if ($request->user()?->isSuperAdmin()) {
            return $next($request);
        }

        // Allow the super-admin login / logout routes so a super admin can
        // still authenticate if they happen to be logged out
        if ($request->routeIs('login', 'logout', 'super-admin.*')) {
            return $next($request);
        }

        $message = (string) PlatformSetting::get(
            'maintenance_message',
            'The platform is currently under maintenance. Please check back later.',
        );

        return response()->view('errors.maintenance', compact('message'), 503);
    }
}
