@props([
    'action' => 'submit',
])

@php
    $siteKey = config('services.recaptcha.site_key');
    // Skip reCAPTCHA on school custom domains (client-side JS won't work there)
    $school = app()->bound('current.school') ? app('current.school') : null;
    $platformHost = parse_url(config('app.url'), PHP_URL_HOST);
    $isSchoolDomain = $school && request()->getHost() !== $platformHost;
@endphp

@if ($siteKey && ! $isSchoolDomain)
    <input type="hidden" name="g-recaptcha-response" id="recaptcha-token-{{ $action }}">
    <input type="hidden" name="recaptcha_action" value="{{ $action }}">

    <script src="https://www.google.com/recaptcha/api.js?render={{ $siteKey }}" async defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('recaptcha-token-{{ $action }}')?.closest('form');
            if (!form) return;

            form.addEventListener('submit', function(e) {
                const tokenInput = document.getElementById('recaptcha-token-{{ $action }}');
                // If we already have a token, let the form submit
                if (tokenInput && tokenInput.value) return;

                e.preventDefault();

                if (typeof grecaptcha === 'undefined') {
                    // reCAPTCHA not loaded — submit without it
                    form.submit();
                    return;
                }

                grecaptcha.ready(function() {
                    grecaptcha.execute('{{ $siteKey }}', { action: '{{ $action }}' })
                        .then(function(token) {
                            tokenInput.value = token;
                            form.submit();
                        })
                        .catch(function() {
                            // On error, submit without reCAPTCHA
                            form.submit();
                        });
                });
            });
        });
    </script>

    {{-- reCAPTCHA badge positioning — minimal footprint --}}
    <style>
        .grecaptcha-badge { visibility: hidden; }
    </style>
    <p class="text-xs text-zinc-500 dark:text-zinc-400 text-center mt-2">
        Protected by reCAPTCHA.
        <a href="https://policies.google.com/privacy" target="_blank" rel="noopener noreferrer" class="underline hover:text-zinc-600 dark:hover:text-zinc-300">Privacy</a>
        &middot;
        <a href="https://policies.google.com/terms" target="_blank" rel="noopener noreferrer" class="underline hover:text-zinc-600 dark:hover:text-zinc-300">Terms</a>
    </p>
@endif
