@php
    $school = app()->bound('current.school') ? app('current.school') : null;
    $appName = $school?->name ?? config('app.name', 'DX-SchoolPortal');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>{{ $title ?? 'Error' }} - {{ $appName }}</title>
        <link rel="icon" href="{{ $school?->setting('branding.favicon_url') ?? '/favicon.ico' }}" sizes="any">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=montserrat:400,500,600,700" rel="stylesheet" />
        @vite(['resources/css/app.css'])
        @fluxAppearance
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800 flex items-center justify-center p-6">
        <div class="text-center space-y-4 max-w-md">
            {{ $slot }}
            <div class="flex justify-center gap-3 pt-2">
                {{ $actions ?? '' }}
            </div>
            <p class="text-xs text-zinc-400 pt-4">{{ $appName }}</p>
        </div>
    </body>
</html>
