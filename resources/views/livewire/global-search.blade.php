<div
    x-data="{
        open: false,
        dropStyle: {},
        reposition() {
            const rect = this.$refs.wrap.getBoundingClientRect();
            const vw  = window.innerWidth;
            const vh  = window.innerHeight;
            const top = rect.bottom + 4;
            const maxH = Math.min(400, vh - top - 16);
            if (vw < 640) {
                this.dropStyle = { top: top + 'px', left: '1rem', right: '1rem', maxHeight: maxH + 'px' };
            } else {
                this.dropStyle = { top: top + 'px', left: rect.left + 'px', width: rect.width + 'px', maxHeight: maxH + 'px' };
            }
        },
    }"
    @click.outside="open = false"
    @keydown.escape.window="open = false"
    @keydown.ctrl.k.window.prevent="$refs.searchInput?.focus(); open = true; reposition()"
    @keydown.meta.k.window.prevent="$refs.searchInput?.focus(); open = true; reposition()"
    @resize.window.debounce.100ms="if (open) reposition()"
    class="px-3 pt-1 pb-2"
>
    {{-- Search input --}}
    <div x-ref="wrap" class="relative">
        <flux:icon.magnifying-glass class="pointer-events-none absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-zinc-400" />
        <input
            x-ref="searchInput"
            wire:model.live.debounce.300ms="query"
            @focus="open = true; reposition()"
            type="search"
            placeholder="{{ __('Search…') }}"
            autocomplete="off"
            aria-label="{{ __('Global search') }}"
            class="w-full rounded-lg border border-zinc-200 bg-zinc-100/60 py-1.5 pl-8 pr-10 text-sm text-zinc-800 placeholder-zinc-400 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 dark:border-zinc-700 dark:bg-zinc-700/50 dark:text-white dark:placeholder-zinc-500 dark:focus:border-indigo-500 dark:focus:bg-zinc-800"
        />
        {{-- Kbd shortcut hint --}}
        <span class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2 hidden md:flex items-center gap-0.5">
            <kbd class="rounded border border-zinc-300 bg-zinc-100 px-1 py-0.5 text-[10px] text-zinc-400 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-500">Ctrl</kbd>
            <kbd class="rounded border border-zinc-300 bg-zinc-100 px-1 py-0.5 text-[10px] text-zinc-400 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-500">K</kbd>
        </span>
    </div>

    {{-- Results panel (fixed position to escape sidebar overflow clipping) --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        x-cloak
        :style="dropStyle"
        class="fixed z-[60] overflow-y-auto rounded-xl border border-zinc-200 bg-white shadow-xl ring-1 ring-black/5 dark:border-zinc-700 dark:bg-zinc-800 dark:ring-white/5"
        role="listbox"
        aria-label="{{ __('Search results') }}"
    >
        {{-- Active query results --}}
        @if ($query && strlen(trim($query)) >= 2)
            @if (! empty($results))
                @foreach ($results as $group => $items)
                    <div class="py-1">
                        <p class="px-3 py-1.5 text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">
                            {{ match ($group) {
                                'students' => __('Students'),
                                'teachers' => __('Teachers'),
                                'classes'  => __('Classes'),
                                default    => ucfirst($group),
                            } }}
                        </p>

                        @foreach ($items as $item)
                            <a
                                href="{{ $item['url'] }}"
                                wire:navigate
                                class="flex items-center gap-3 px-3 py-2 text-sm transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-700/50"
                                @click="open = false; $wire.saveRecentSearch('{{ addslashes($item['label']) }}', '{{ $item['url'] }}')"  
                                role="option"
                            >
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400">
                                    @if ($item['icon'] === 'academic-cap')
                                        <flux:icon.academic-cap class="size-3.5" />
                                    @elseif ($item['icon'] === 'user')
                                        <flux:icon.user class="size-3.5" />
                                    @else
                                        <flux:icon.squares-2x2 class="size-3.5" />
                                    @endif
                                </span>

                                <span class="min-w-0 flex-1">
                                    <span class="block truncate font-medium text-zinc-900 dark:text-white">{{ $item['label'] }}</span>
                                    @if ($item['sub'])
                                        <span class="block truncate text-xs text-zinc-400">{{ $item['sub'] }}</span>
                                    @endif
                                </span>
                            </a>
                        @endforeach
                    </div>

                    @if (! $loop->last)
                        <div class="border-t border-zinc-100 dark:border-zinc-700"></div>
                    @endif
                @endforeach
            @else
                <div class="px-4 py-6 text-center text-sm text-zinc-400 dark:text-zinc-500">
                    {{ __('No results for ":q"', ['q' => $query]) }}
                </div>
            @endif

        {{-- Recent searches (shown when input is empty or less than 2 chars) --}}
        @elseif (! empty($recentSearches))
            <div class="py-1">
                <div class="flex items-center justify-between px-3 py-1.5">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">{{ __('Recent') }}</p>
                    <button
                        wire:click="clearRecentSearches"
                        class="text-[10px] text-zinc-400 hover:text-zinc-600 dark:text-zinc-500 dark:hover:text-zinc-300"
                        type="button"
                    >{{ __('Clear') }}</button>
                </div>

                @foreach ($recentSearches as $recent)
                    <a
                        href="{{ $recent['url'] }}"
                        wire:navigate
                        class="flex items-center gap-3 px-3 py-2 text-sm transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-700/50"
                        @click="open = false"
                        role="option"
                    >
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-zinc-100 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400">
                            <flux:icon.clock class="size-3.5" />
                        </span>
                        <span class="min-w-0 flex-1 truncate font-medium text-zinc-900 dark:text-white">{{ $recent['label'] }}</span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>

