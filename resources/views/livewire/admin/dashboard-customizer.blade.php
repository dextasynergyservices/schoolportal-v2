<div>
    {{-- Trigger Button --}}
    <button
        wire:click="openPanel"
        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-white/70 hover:text-white hover:bg-white/10 transition-colors"
        title="{{ __('Customize Dashboard') }}"
    >
        <flux:icon.adjustments-horizontal class="w-4 h-4" />
        <span class="hidden sm:inline">{{ __('Customize') }}</span>
    </button>

    {{-- Slide-over Panel --}}
    @teleport('body')
    <div
        x-data="{ open: $wire.entangle('showPanel') }"
        x-show="open"
        x-cloak
        class="fixed inset-0 z-50"
        @keydown.escape.window="if (open) open = false"
    >
        {{-- Backdrop --}}
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-black/40 backdrop-blur-sm"
            @click="open = false"
        ></div>

        {{-- Panel --}}
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            class="fixed right-0 inset-y-0 w-full max-w-md bg-white dark:bg-zinc-900 shadow-2xl flex flex-col border-l border-zinc-200 dark:border-zinc-700/50"
            @click.outside="if (open) open = false"
            x-data="{
                draggingIndex: null,
                dragOverIndex: null,
                handleDragStart(e, index) {
                    this.draggingIndex = index;
                    e.dataTransfer.effectAllowed = 'move';
                    e.dataTransfer.setData('text/plain', index.toString());
                    requestAnimationFrame(() => {
                        e.target.closest('[data-widget-item]')?.classList.add('!opacity-40', '!scale-[0.97]');
                    });
                },
                handleDragOver(e) {
                    e.preventDefault();
                    e.dataTransfer.dropEffect = 'move';
                },
                handleDragEnter(index) {
                    if (this.draggingIndex !== null && this.draggingIndex !== index) {
                        this.dragOverIndex = index;
                    }
                },
                handleDragLeave(e, index) {
                    if (!e.currentTarget.contains(e.relatedTarget)) {
                        if (this.dragOverIndex === index) this.dragOverIndex = null;
                    }
                },
                handleDrop(e, index) {
                    e.preventDefault();
                    if (this.draggingIndex !== null && this.draggingIndex !== index) {
                        $wire.reorder(this.draggingIndex, index);
                    }
                    this.cleanup();
                },
                handleDragEnd() {
                    this.cleanup();
                },
                cleanup() {
                    this.draggingIndex = null;
                    this.dragOverIndex = null;
                    document.querySelectorAll('[data-widget-item]').forEach(el => {
                        el.classList.remove('!opacity-40', '!scale-[0.97]');
                    });
                }
            }"
        >
            {{-- Header --}}
            <div class="flex items-center justify-between px-5 py-4 border-b border-zinc-200 dark:border-zinc-700/50">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center">
                        <flux:icon.squares-2x2 class="w-4.5 h-4.5 text-white" />
                    </div>
                    <div>
                        <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Customize Dashboard') }}</h2>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Drag to reorder, toggle to show/hide') }}</p>
                    </div>
                </div>
                <button @click="open = false" class="p-1.5 rounded-lg text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                    <flux:icon.x-mark class="w-5 h-5" />
                </button>
            </div>

            {{-- Widget List --}}
            <div class="flex-1 overflow-y-auto px-5 py-4">
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-3">
                    <flux:icon.information-circle class="w-3.5 h-3.5 inline -mt-0.5" />
                    {{ __('Drag items or use arrows to reorder. Toggle switches to show/hide sections.') }}
                </p>

                <div class="space-y-1.5">
                    @foreach ($widgets as $index => $widget)
                        @php $wm = $meta[$widget['id']] ?? null; @endphp
                        @if ($wm)
                            <div
                                data-widget-item
                                draggable="true"
                                @dragstart="handleDragStart($event, {{ $index }})"
                                @dragover="handleDragOver($event)"
                                @dragenter.prevent="handleDragEnter({{ $index }})"
                                @dragleave="handleDragLeave($event, {{ $index }})"
                                @drop="handleDrop($event, {{ $index }})"
                                @dragend="handleDragEnd()"
                                class="group relative flex items-center gap-3 px-3 py-3 rounded-xl border transition-all duration-200 cursor-grab active:cursor-grabbing select-none"
                                :class="dragOverIndex === {{ $index }}
                                    ? 'border-blue-400 dark:border-blue-500 bg-blue-50 dark:bg-blue-950/30 shadow-sm ring-1 ring-blue-200 dark:ring-blue-800'
                                    : '{{ $widget['visible']
                                        ? 'border-zinc-200 dark:border-zinc-700/70 bg-white dark:bg-zinc-800/60 hover:border-zinc-300 dark:hover:border-zinc-600 hover:shadow-sm'
                                        : 'border-zinc-100 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-800/20' }}'"
                                wire:key="widget-{{ $widget['id'] }}"
                            >
                                {{-- Drag Handle (visible on hover) --}}
                                <div class="flex flex-col items-center gap-px text-zinc-400 dark:text-zinc-500 shrink-0 opacity-0 group-hover:opacity-100 transition-opacity duration-150" aria-hidden="true">
                                    <svg class="w-4 h-4" viewBox="0 0 16 16" fill="currentColor">
                                        <circle cx="5" cy="3" r="1.5" />
                                        <circle cx="11" cy="3" r="1.5" />
                                        <circle cx="5" cy="8" r="1.5" />
                                        <circle cx="11" cy="8" r="1.5" />
                                        <circle cx="5" cy="13" r="1.5" />
                                        <circle cx="11" cy="13" r="1.5" />
                                    </svg>
                                </div>

                                {{-- Icon --}}
                                <div @class([
                                    'w-9 h-9 rounded-lg flex items-center justify-center shrink-0 transition-colors',
                                    match($wm['color']) {
                                        'amber' => $widget['visible'] ? 'bg-amber-100 dark:bg-amber-900/30' : 'bg-zinc-100 dark:bg-zinc-800',
                                        'blue' => $widget['visible'] ? 'bg-blue-100 dark:bg-blue-900/30' : 'bg-zinc-100 dark:bg-zinc-800',
                                        'indigo' => $widget['visible'] ? 'bg-indigo-100 dark:bg-indigo-900/30' : 'bg-zinc-100 dark:bg-zinc-800',
                                        'emerald' => $widget['visible'] ? 'bg-emerald-100 dark:bg-emerald-900/30' : 'bg-zinc-100 dark:bg-zinc-800',
                                        'cyan' => $widget['visible'] ? 'bg-cyan-100 dark:bg-cyan-900/30' : 'bg-zinc-100 dark:bg-zinc-800',
                                        default => 'bg-zinc-100 dark:bg-zinc-800',
                                    },
                                ])>
                                    @switch($widget['id'])
                                        @case('alerts')
                                            <flux:icon.exclamation-triangle @class(['w-4 h-4 transition-colors', $widget['visible'] ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-400 dark:text-zinc-500']) />
                                            @break
                                        @case('primary_stats')
                                            <flux:icon.chart-bar @class(['w-4 h-4 transition-colors', $widget['visible'] ? 'text-blue-600 dark:text-blue-400' : 'text-zinc-400 dark:text-zinc-500']) />
                                            @break
                                        @case('term_stats')
                                            <flux:icon.presentation-chart-line @class(['w-4 h-4 transition-colors', $widget['visible'] ? 'text-indigo-600 dark:text-indigo-400' : 'text-zinc-400 dark:text-zinc-500']) />
                                            @break
                                        @case('quick_actions')
                                            <flux:icon.bolt @class(['w-4 h-4 transition-colors', $widget['visible'] ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-400 dark:text-zinc-500']) />
                                            @break
                                        @case('approvals_activity')
                                            <flux:icon.clock @class(['w-4 h-4 transition-colors', $widget['visible'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-zinc-400 dark:text-zinc-500']) />
                                            @break
                                        @case('analytics_link')
                                            <flux:icon.chart-bar-square @class(['w-4 h-4 transition-colors', $widget['visible'] ? 'text-cyan-600 dark:text-cyan-400' : 'text-zinc-400 dark:text-zinc-500']) />
                                            @break
                                    @endswitch
                                </div>

                                {{-- Text --}}
                                <div class="min-w-0 flex-1">
                                    <p @class([
                                        'text-sm font-medium transition-colors',
                                        $widget['visible'] ? 'text-zinc-900 dark:text-white' : 'text-zinc-400 dark:text-zinc-500',
                                    ])>
                                        {{ __($wm['label']) }}
                                    </p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400 truncate">{{ __($wm['description']) }}</p>
                                </div>

                                {{-- Controls --}}
                                <div class="flex items-center gap-0.5 shrink-0">
                                    {{-- Move Up --}}
                                    <button
                                        wire:click="moveUp({{ $index }})"
                                        @class([
                                            'p-1.5 rounded-md text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700/60 transition-colors',
                                            'invisible' => $index === 0,
                                        ])
                                        title="{{ __('Move up') }}"
                                    >
                                        <flux:icon.chevron-up class="w-3.5 h-3.5" />
                                    </button>

                                    {{-- Move Down --}}
                                    <button
                                        wire:click="moveDown({{ $index }})"
                                        @class([
                                            'p-1.5 rounded-md text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700/60 transition-colors',
                                            'invisible' => $index === count($widgets) - 1,
                                        ])
                                        title="{{ __('Move down') }}"
                                    >
                                        <flux:icon.chevron-down class="w-3.5 h-3.5" />
                                    </button>

                                    {{-- Visibility Toggle --}}
                                    <button
                                        wire:click="toggleVisibility({{ $index }})"
                                        class="ml-1 relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-zinc-900 {{ $widget['visible'] ? 'bg-blue-600' : 'bg-zinc-300 dark:bg-zinc-600' }}"
                                        role="switch"
                                        aria-checked="{{ $widget['visible'] ? 'true' : 'false' }}"
                                        aria-label="{{ __('Toggle :widget visibility', ['widget' => $wm['label']]) }}"
                                    >
                                        <span
                                            class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $widget['visible'] ? 'translate-x-4' : 'translate-x-0' }}"
                                        ></span>
                                    </button>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>

            {{-- Footer --}}
            <div class="px-5 py-4 border-t border-zinc-200 dark:border-zinc-700/50 bg-zinc-50/50 dark:bg-zinc-800/30">
                <div class="flex items-center justify-between gap-3">
                    <button
                        wire:click="resetDefaults"
                        class="inline-flex items-center gap-1.5 text-xs font-medium text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors"
                    >
                        <flux:icon.arrow-path class="w-3.5 h-3.5" />
                        {{ __('Reset to defaults') }}
                    </button>
                    <div class="flex gap-2">
                        <button
                            @click="open = false"
                            class="px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-700 rounded-lg transition-colors"
                        >
                            {{ __('Cancel') }}
                        </button>
                        <button
                            wire:click="save"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 active:bg-blue-800 rounded-lg transition-colors shadow-sm"
                        >
                            <span wire:loading.remove wire:target="save">{{ __('Save Layout') }}</span>
                            <span wire:loading wire:target="save" class="inline-flex items-center gap-1.5">
                                <svg class="animate-spin w-3.5 h-3.5" viewBox="0 0 24 24" fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                {{ __('Saving…') }}
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endteleport
</div>
