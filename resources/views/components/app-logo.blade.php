@props([
    'sidebar' => false,
])

@php
    $school = app()->bound('current.school') ? app('current.school') : null;
    $name = $school?->name ?? config('app.name', 'DX-SchoolPortal');
@endphp

@if($sidebar)
    @if ($school?->logo_url)
        <flux:sidebar.brand :name="$name" {{ $attributes }}>
            <x-slot name="logo">
                <img src="{{ $school->logo_url }}" alt="{{ $name }}" class="object-contain rounded-md size-8">
            </x-slot>
        </flux:sidebar.brand>
    @else
        <flux:sidebar.brand :name="$name" {{ $attributes }}>
            <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md bg-accent-content text-accent-foreground">
                <x-app-logo-icon class="size-5 fill-current text-white dark:text-black" />
            </x-slot>
        </flux:sidebar.brand>
    @endif
@else
    @if ($school?->logo_url)
        <flux:brand :name="$name" {{ $attributes }}>
            <x-slot name="logo">
                <img src="{{ $school->logo_url }}" alt="{{ $name }}" class="object-contain rounded-md size-8">
            </x-slot>
        </flux:brand>
    @else
        <flux:brand :name="$name" {{ $attributes }}>
            <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md bg-accent-content text-accent-foreground">
                <x-app-logo-icon class="size-5 fill-current text-white dark:text-black" />
            </x-slot>
        </flux:brand>
    @endif
@endif
