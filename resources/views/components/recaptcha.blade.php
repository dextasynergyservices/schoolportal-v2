@props([
    'action' => 'submit',
])

@php
    $siteKey = config('services.recaptcha.site_key');
@endphp

@if ($siteKey)
    <input type="hidden" name="g-recaptcha-response" id="recaptcha-token-{{ $action }}">

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
    <p class="text-xs text-zinc-400 dark:text-zinc-500 text-center mt-2">
        Protected by reCAPTCHA.
        <a href="https://policies.google.com/privacy" target="_blank" rel="noopener noreferrer" class="underline hover:text-zinc-600 dark:hover:text-zinc-300">Privacy</a>
        &middot;
        <a href="https://policies.google.com/terms" target="_blank" rel="noopener noreferrer" class="underline hover:text-zinc-600 dark:hover:text-zinc-300">Terms</a>
    </p>
@endif
