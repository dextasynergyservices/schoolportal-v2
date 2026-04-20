<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Rules\Recaptcha;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class ValidateRecaptcha
{
    public function handle(Request $request, Closure $next, ?string $action = null): Response
    {
        // Only validate on POST/PUT/PATCH requests
        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH'], true)) {
            return $next($request);
        }

        // Skip if keys not configured
        if (empty(config('services.recaptcha.secret_key'))) {
            return $next($request);
        }

        // Skip for routes that don't need reCAPTCHA (already behind auth)
        if ($request->routeIs('password.confirm.store', 'password.confirmation')) {
            return $next($request);
        }

        // Auto-detect action from the form's hidden input if not explicitly passed
        $recaptchaAction = $action ?? $request->input('recaptcha_action');

        $validator = Validator::make($request->only('g-recaptcha-response'), [
            'g-recaptcha-response' => [new Recaptcha($recaptchaAction)],
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages([
                'login' => $validator->errors()->first('g-recaptcha-response'),
            ]);
        }

        return $next($request);
    }
}
