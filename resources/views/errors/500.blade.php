<x-layouts.error :title="__('Server Error')">
    <div class="text-6xl font-bold text-red-500">500</div>
    <h1 class="text-xl font-semibold text-zinc-800 dark:text-zinc-200">{{ __('Something went wrong') }}</h1>
    <p class="text-zinc-500 dark:text-zinc-400">{{ __('An unexpected error occurred. Our team has been notified. Please try again or contact your school administrator if the problem persists.') }}</p>

    <x-slot:actions>
        <a href="javascript:history.back()" class="inline-flex items-center rounded-lg bg-zinc-900 dark:bg-white px-4 py-2 text-sm font-medium text-white dark:text-zinc-900 hover:opacity-90">{{ __('Go Back') }}</a>
        <a href="/" class="inline-flex items-center rounded-lg border border-zinc-300 dark:border-zinc-600 px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700">{{ __('Home') }}</a>
    </x-slot:actions>
</x-layouts.error>
