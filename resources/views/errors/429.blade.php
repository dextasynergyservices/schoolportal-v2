<x-layouts.error :title="__('Too Many Requests')">
    <div class="text-6xl font-bold text-amber-500">429</div>
    <h1 class="text-xl font-semibold text-zinc-800 dark:text-zinc-200">{{ __('Too many requests') }}</h1>
    <p class="text-zinc-500 dark:text-zinc-400">{{ __('You have made too many requests. Please wait a moment and try again.') }}</p>

    <x-slot:actions>
        <a href="javascript:history.back()" class="inline-flex items-center rounded-lg bg-zinc-900 dark:bg-white px-4 py-2 text-sm font-medium text-white dark:text-zinc-900 hover:opacity-90">{{ __('Go Back') }}</a>
    </x-slot:actions>
</x-layouts.error>
