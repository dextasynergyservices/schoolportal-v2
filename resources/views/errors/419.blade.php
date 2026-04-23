<x-layouts.error :title="__('Session Expired')">
    <div class="text-6xl font-bold text-amber-500">419</div>
    <h1 class="text-xl font-semibold text-zinc-800 dark:text-zinc-200">{{ __('Session expired') }}</h1>
    <p class="text-zinc-500 dark:text-zinc-400">{{ __('Your session has expired. Please refresh the page and try again.') }}</p>

    <x-slot:actions>
        <a href="javascript:location.reload()" class="inline-flex items-center rounded-lg bg-zinc-900 dark:bg-white px-4 py-2 text-sm font-medium text-white dark:text-zinc-900 hover:opacity-90">{{ __('Refresh Page') }}</a>
        <a href="/portal/login" class="inline-flex items-center rounded-lg border border-zinc-300 dark:border-zinc-600 px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700">{{ __('Login') }}</a>
    </x-slot:actions>
</x-layouts.error>
