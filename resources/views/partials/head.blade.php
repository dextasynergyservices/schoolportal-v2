<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="csrf-token" content="{{ csrf_token() }}" />

@php
    $school = app()->bound('current.school') ? app('current.school') : null;
    $appName = $school?->name ?? config('app.name', 'DX-SchoolPortal');
@endphp

<meta name="description" content="{{ $school?->name ? $school->name . ' - School Portal' : 'DX-SchoolPortal - Multi-Tenant School Management Platform' }}" />
<meta name="theme-color" content="{{ $school?->setting('branding.primary_color') ?? '#000c99' }}" />

<title>
    {{ filled($title ?? null) ? $title.' - '.$appName : $appName }}
</title>

<link rel="icon" href="{{ $school?->setting('branding.favicon_url') ?? '/favicon.ico' }}" sizes="any">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

{{-- PWA manifest (dynamic — per-school name/color, student portal only) --}}
@auth
    @if (auth()->user()?->role === 'student')
        <link rel="manifest" href="{{ route('pwa.manifest') }}">
    @endif
@endauth

<link rel="preconnect" href="https://fonts.bunny.net">
<link rel="dns-prefetch" href="https://res.cloudinary.com">
<link href="https://fonts.bunny.net/css?family=montserrat:300,400,500,600,700,800,900" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance

{{-- Per-school branding colors --}}
@if ($school)
<style>
    :root {
        --color-primary: {{ $school->setting('branding.primary_color') ?? '#4F46E5' }};
        --color-secondary: {{ $school->setting('branding.secondary_color') ?? '#F59E0B' }};
        --color-accent: {{ $school->setting('branding.accent_color') ?? '#10B981' }};
    }
</style>
@endif

@stack('styles')
