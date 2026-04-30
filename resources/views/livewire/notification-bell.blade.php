<div wire:poll.60s="loadNotifications"
    x-data="{
        open: false,
        dropStyle: {},
        reposition() {
            const rect = this.$refs.bell.getBoundingClientRect();
            const vw = window.innerWidth;
            const vh = window.innerHeight;
            const top = rect.bottom + 8;
            const maxH = Math.min(480, vh - top - 16);
            if (vw < 640) {
                // Mobile: pin to 1rem from each side (matches w-[calc(100vw-2rem)] width class)
                this.dropStyle = { top: top + 'px', left: '1rem', right: '1rem', maxHeight: maxH + 'px' };
            } else if (rect.left < vw / 2) {
                this.dropStyle = { top: top + 'px', left: Math.max(8, rect.left) + 'px', right: 'auto', maxHeight: maxH + 'px' };
            } else {
                this.dropStyle = { top: top + 'px', left: 'auto', right: Math.max(8, vw - rect.right) + 'px', maxHeight: maxH + 'px' };
            }
        },
        toggle() {
            this.open = !this.open;
            if (this.open) {
                this.reposition();
                this.$wire.loadNotifications();
            }
        }
    }"
    @keydown.escape.window="open = false"
    @resize.window.debounce.100ms="if (open) reposition()"
>
    {{-- Bell Button --}}
    <button
        x-ref="bell"
        @click="toggle()"
        class="relative inline-flex items-center justify-center rounded-lg p-1.5 text-zinc-500 transition hover:bg-zinc-800/5 hover:text-zinc-800 dark:text-white/80 dark:hover:bg-white/10 dark:hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
        title="{{ __('Notifications') }}"
        aria-label="{{ __('Notifications') }} {{ $unreadCount > 0 ? $unreadCount . ' ' . __('unread') : '' }}"
        aria-haspopup="true"
        :aria-expanded="open.toString()"
    >
        <flux:icon.bell class="size-5" />

        @if ($unreadCount > 0)
            <span class="absolute -top-0.5 -end-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white ring-2 ring-white dark:ring-zinc-900 animate-pulse">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @endif
    </button>

    {{-- Dropdown (fixed position to escape sidebar/header overflow clipping) --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        x-cloak
        @click.outside="if (!$refs.bell.contains($event.target)) open = false"
        :style="dropStyle"
        class="fixed z-[60] w-[calc(100vw-2rem)] max-w-sm overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-xl ring-1 ring-black/5 dark:border-zinc-700 dark:bg-zinc-800 dark:ring-white/5 sm:w-96"
        role="menu"
        aria-label="{{ __('Notifications') }}"
    >
        {{-- Header --}}
        <div class="flex items-center justify-between border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
            <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">
                {{ __('Notifications') }}
            </h3>
            @if ($unreadCount > 0)
                <button
                    @click="$wire.markAllAsRead()"
                    class="text-xs font-medium text-indigo-600 transition hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
                >
                    {{ __('Mark all read') }}
                </button>
            @endif
        </div>

        {{-- Notification List --}}
        <div class="max-h-80 overflow-y-auto overscroll-contain sm:max-h-96">
            @forelse ($recentNotifications as $notification)
                <button
                    type="button"
                    @click="
                        $wire.markAsRead('{{ $notification['id'] }}').then(url => {
                            open = false;
                            if (url && url !== '#') window.location.href = url;
                        })
                    "
                    class="group flex w-full items-start gap-3 px-4 py-3 text-start transition hover:bg-zinc-50 dark:hover:bg-zinc-700/50 {{ ! $notification['read'] ? 'bg-indigo-50/50 dark:bg-indigo-500/5' : '' }}"
                >
                    {{-- Icon --}}
                    <span @class([
                        'mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full',
                        'bg-indigo-100 text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-400' => in_array($notification['type_label'], ['result', 'assignment', 'quiz', 'notice']),
                        'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400' => $notification['type_label'] === 'approval',
                        'bg-red-100 text-red-600 dark:bg-red-500/20 dark:text-red-400' => $notification['type_label'] === 'rejection',
                        'bg-purple-100 text-purple-600 dark:bg-purple-500/20 dark:text-purple-400' => $notification['type_label'] === 'game',
                        'bg-amber-100 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400' => in_array($notification['type_label'], ['school', 'credit_purchase']),
                        'bg-zinc-100 text-zinc-600 dark:bg-zinc-600 dark:text-zinc-300' => ! in_array($notification['type_label'], ['result', 'assignment', 'quiz', 'notice', 'approval', 'rejection', 'game', 'school', 'credit_purchase']),
                    ])>
                        @switch($notification['icon'])
                            @case('document-text')
                                <flux:icon.document-text class="size-4" />
                                @break
                            @case('academic-cap')
                                <flux:icon.academic-cap class="size-4" />
                                @break
                            @case('megaphone')
                                <flux:icon.megaphone class="size-4" />
                                @break
                            @case('question-mark-circle')
                                <flux:icon.question-mark-circle class="size-4" />
                                @break
                            @case('puzzle-piece')
                                <flux:icon.puzzle-piece class="size-4" />
                                @break
                            @case('check-circle')
                                <flux:icon.check-circle class="size-4" />
                                @break
                            @case('x-circle')
                                <flux:icon.x-circle class="size-4" />
                                @break
                            @case('building-office-2')
                                <flux:icon.building-office-2 class="size-4" />
                                @break
                            @case('sparkles')
                                <flux:icon.sparkles class="size-4" />
                                @break
                            @default
                                <flux:icon.bell class="size-4" />
                        @endswitch
                    </span>

                    {{-- Content --}}
                    <div class="min-w-0 flex-1">
                        <p class="text-sm leading-snug {{ ! $notification['read'] ? 'font-medium text-zinc-900 dark:text-white' : 'text-zinc-600 dark:text-zinc-300' }}">
                            {{ $notification['message'] }}
                        </p>
                        <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                            {{ $notification['time'] }}
                        </p>
                    </div>

                    {{-- Unread indicator --}}
                    @if (! $notification['read'])
                        <span class="mt-2 h-2 w-2 shrink-0 rounded-full bg-indigo-500" aria-label="{{ __('Unread') }}"></span>
                    @endif
                </button>
            @empty
                <div class="px-4 py-10 text-center">
                    <flux:icon.bell class="mx-auto size-8 text-zinc-300 dark:text-zinc-600" />
                    <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('No notifications yet') }}
                    </p>
                </div>
            @endforelse
        </div>

        {{-- Footer --}}
        @if (count($recentNotifications) > 0)
            <div class="border-t border-zinc-200 dark:border-zinc-700">
                <a
                    href="{{ url('/portal/notifications') }}"
                    @click="open = false"
                    wire:navigate
                    class="flex items-center justify-center gap-1.5 px-4 py-2.5 text-xs font-medium text-indigo-600 transition hover:bg-zinc-50 dark:text-indigo-400 dark:hover:bg-zinc-700/50"
                >
                    {{ __('View all notifications') }}
                    <flux:icon.arrow-right class="size-3.5" />
                </a>
            </div>
        @endif
    </div>
</div>
