<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Security headers applied to every response.
     *
     * NOTE: Content-Security-Policy is intentionally omitted. Livewire 4,
     * Alpine.js, and Flux UI inject scripts, styles, and blob: URIs dynamically
     * at runtime in ways that are incompatible with CSP — even with 'unsafe-inline'.
     * This is a known limitation of these frameworks.
     *
     * XSS protection is already strong via:
     * - Blade auto-escaping ({{ }}) on all output
     * - Laravel CSRF tokens on all forms
     * - Eloquent parameterized queries (no SQL injection)
     * - Signed Cloudinary URLs for sensitive files
     *
     * The headers below protect against clickjacking, MIME sniffing, protocol
     * downgrade attacks, and information leakage — covering the OWASP
     * recommended security headers minus CSP.
     *
     * CSP can be added at the web server level (Apache/Nginx) in production
     * once a complete allowlist is established from real traffic monitoring.
     *
     * @see https://owasp.org/www-project-secure-headers/
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Prevent clickjacking — page cannot be embedded in iframes on other domains
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Legacy XSS filter (for older browsers that support it)
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Control referrer information sent with requests
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Restrict access to browser features
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // Prevent cross-origin information leakage
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        // HSTS — force HTTPS in production (browsers remember for 1 year)
        if (app()->isProduction() && $request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
