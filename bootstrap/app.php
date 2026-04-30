<?php

use App\Http\Middleware\CheckMaintenanceMode;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\ResolveTenant;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SessionTimeout;
use App\Http\Middleware\ValidateRecaptcha;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust Cloudflare proxy IPs so request()->ip() returns the real client IP.
        // '*' trusts all proxies — safe because Cloudflare is the only entry point in production.
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_AWS_ELB,
        );

        // Exclude Paystack webhook from CSRF verification
        $middleware->validateCsrfTokens(except: [
            'webhooks/paystack',
        ]);

        // Register middleware aliases for route-level usage
        $middleware->alias([
            'role' => EnsureRole::class,
            'maintenance' => CheckMaintenanceMode::class,
            'tenant' => ResolveTenant::class,
            'session.timeout' => SessionTimeout::class,
            'force.password.change' => ForcePasswordChange::class,
            'recaptcha' => ValidateRecaptcha::class,
        ]);

        // Append security + tenant middleware to the web middleware group
        $middleware->web(append: [
            SecurityHeaders::class,
            ResolveTenant::class,
            SessionTimeout::class,
            ForcePasswordChange::class,
            CheckMaintenanceMode::class,
        ]);

        // Redirect unauthenticated users to /portal/login
        $middleware->redirectGuestsTo('/portal/login');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        Integration::handles($exceptions);
    })->create();
