<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ __('Maintenance') }} — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
    <style>
        body { background: #f9fafb; font-family: ui-sans-serif, system-ui, sans-serif; margin: 0; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center bg-zinc-50">
    <div class="w-full max-w-md mx-auto px-6 py-16 text-center">

        {{-- Maintenance icon --}}
        <div class="mx-auto mb-6 w-20 h-20 rounded-full bg-amber-100 flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l5.654-4.654m5.14-5.14-.982.982m5.14-5.14.982-.982M6 6l1.5 1.5m4.5-4.5 1.5 1.5" />
            </svg>
        </div>

        <h1 class="text-2xl font-bold text-zinc-900 mb-2">
            {{ __('Under Maintenance') }}
        </h1>

        <p class="text-zinc-600 leading-relaxed mb-8">
            {{ $message ?: __('We are performing scheduled maintenance and will be back shortly. Thank you for your patience.') }}
        </p>

        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-amber-50 border border-amber-200 text-amber-700 text-sm font-medium">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
            </svg>
            {{ __('Please check back soon') }}
        </div>

        @auth
            @if(auth()->user()->isSuperAdmin())
                <div class="mt-8 pt-8 border-t border-zinc-200">
                    <p class="text-xs text-zinc-400 mb-3">{{ __('You are logged in as super admin') }}</p>
                    <a href="{{ route('super-admin.dashboard') }}"
                       class="inline-flex items-center gap-1.5 text-sm text-indigo-600 hover:text-indigo-700 font-medium transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12 11.204 3.045c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                        </svg>
                        {{ __('Go to Super Admin Dashboard') }}
                    </a>
                </div>
            @endif
        @endauth

        <p class="mt-8 text-xs text-zinc-400">
            {{ config('app.name') }}
        </p>

    </div>
</body>
</html>
