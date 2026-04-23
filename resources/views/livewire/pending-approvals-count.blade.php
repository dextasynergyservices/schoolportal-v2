<div wire:poll.60s="loadCount">
    <a href="{{ route('admin.approvals.index') }}" wire:navigate
       class="relative inline-flex items-center justify-center rounded-lg p-1.5 text-zinc-500 transition hover:bg-zinc-800/5 hover:text-zinc-800 dark:text-white/80 dark:hover:bg-white/10 dark:hover:text-white"
       title="{{ __('Pending Approvals') }}"
    >
        <flux:icon.bell class="size-5" />

        @if ($count > 0)
            <span class="absolute -top-0.5 -end-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white">
                {{ $count > 99 ? '99+' : $count }}
            </span>
        @endif
    </a>
</div>
