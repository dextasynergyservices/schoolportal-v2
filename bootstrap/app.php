<?php

use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\ResolveTenant;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SessionTimeout;
use App\Http\Middleware\ValidateRecaptcha;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Exclude Paystack webhook from CSRF verification
        $middleware->validateCsrfTokens(except: [
            'webhooks/paystack',
        ]);

        // Register middleware aliases for route-level usage
        $middleware->alias([
            'role' => EnsureRole::class,
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
        ]);

        // Redirect unauthenticated users to /portal/login
        $middleware->redirectGuestsTo('/portal/login');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        Integration::handles($exceptions);
    })->create();
