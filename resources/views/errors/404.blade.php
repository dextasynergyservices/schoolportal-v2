<x-layouts.error :title="__('Page Not Found')">
    <div class="text-6xl font-bold text-zinc-400">404</div>
    <h1 class="text-xl font-semibold text-zinc-800 dark:text-zinc-200">{{ __('Page not found') }}</h1>
    <p class="text-zinc-500 dark:text-zinc-400">{{ __('The page you are looking for does not exist or may have been moved.') }}</p>

    <x-slot:actions>
        <a href="javascript:history.back()" class="inline-flex items-center rounded-lg bg-zinc-900 dark:bg-white px-4 py-2 text-sm font-medium text-white dark:text-zinc-900 hover:opacity-90">{{ __('Go Back') }}</a>
        <a href="/" class="inline-flex items-center rounded-lg border border-zinc-300 dark:border-zinc-600 px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700">{{ __('Home') }}</a>
    </x-slot:actions>
</x-layouts.error>
