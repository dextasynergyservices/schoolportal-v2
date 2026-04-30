{{-- Shared sub-navigation for all super-admin AI Credits pages --}}
<nav class="flex gap-1 p-1 bg-zinc-100 dark:bg-zinc-800/60 rounded-xl w-fit" aria-label="{{ __('AI Credits navigation') }}">
    <a href="{{ route('super-admin.credits.index') }}"
       wire:navigate
       class="px-4 py-2 text-sm font-medium rounded-lg transition-colors whitespace-nowrap {{ request()->routeIs('super-admin.credits.index') ? 'bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white shadow-sm' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-800 dark:hover:text-zinc-200' }}">
        {{ __('Balances') }}
    </a>
    <a href="{{ route('super-admin.credits.analytics') }}"
       wire:navigate
       class="px-4 py-2 text-sm font-medium rounded-lg transition-colors whitespace-nowrap {{ request()->routeIs('super-admin.credits.analytics') ? 'bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white shadow-sm' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-800 dark:hover:text-zinc-200' }}">
        {{ __('AI Analytics') }}
    </a>
    <a href="{{ route('super-admin.credits.history') }}"
       wire:navigate
       class="px-4 py-2 text-sm font-medium rounded-lg transition-colors whitespace-nowrap {{ request()->routeIs('super-admin.credits.history') ? 'bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white shadow-sm' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-800 dark:hover:text-zinc-200' }}">
        {{ __('History') }}
    </a>
</nav>
