<x-layouts::app :title="$title">
    <div
        x-data="helpGuideSearch()"
        x-init="prepareContent(); $watch('query', () => search())"
        class="space-y-4"
    >
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $title }}</h1>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ __('Search the guide, then use Previous and Next to move between matches.') }}</p>
            </div>

            <div class="flex w-full flex-wrap items-center gap-2 sm:w-auto">
                <div class="min-w-0 flex-1 sm:w-80">
                    <flux:input
                        x-model.debounce.250ms="query"
                        x-on:keydown.enter.prevent="$event.shiftKey ? previous() : next()"
                        x-on:keydown.escape.prevent="clear()"
                        type="search"
                        icon="magnifying-glass"
                        :placeholder="__('Search help guide...')"
                    />
                </div>
                <div class="flex items-center gap-1">
                    <flux:button type="button" variant="subtle" size="sm" icon="chevron-up" x-on:click="previous()" x-bind:disabled="matches.length === 0" />
                    <flux:button type="button" variant="subtle" size="sm" icon="chevron-down" x-on:click="next()" x-bind:disabled="matches.length === 0" />
                </div>
                <span class="min-w-16 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400" x-text="matchLabel()"></span>
            </div>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div
                x-ref="content"
                class="help-guide-content mx-auto max-w-5xl px-5 py-6 text-zinc-800 dark:text-zinc-100 sm:px-8"
            >
                {!! $guideBody !!}
            </div>
        </div>

        <template x-teleport="body">
            <div
                x-show="query.trim().length >= 2"
                x-transition.opacity
                x-cloak
                class="pointer-events-none fixed inset-x-0 bottom-3 z-50 flex justify-center px-3 sm:inset-x-auto sm:bottom-auto sm:right-3 sm:top-1/2 sm:-translate-y-1/2 sm:justify-end sm:px-0"
            >
                <div class="pointer-events-auto flex items-center gap-1 rounded-lg border border-zinc-200 bg-white p-1.5 shadow-lg dark:border-zinc-700 dark:bg-zinc-900 sm:flex-col">
                    <span class="min-w-14 px-1 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400" x-text="matchLabel()"></span>
                    <flux:button
                        type="button"
                        variant="subtle"
                        size="sm"
                        icon="chevron-up"
                        x-on:click="previous()"
                        x-bind:disabled="matches.length === 0"
                        :title="__('Previous match')"
                        :aria-label="__('Previous match')"
                    />
                    <flux:button
                        type="button"
                        variant="subtle"
                        size="sm"
                        icon="chevron-down"
                        x-on:click="next()"
                        x-bind:disabled="matches.length === 0"
                        :title="__('Next match')"
                        :aria-label="__('Next match')"
                    />
                    <flux:button
                        type="button"
                        variant="ghost"
                        size="sm"
                        icon="x-mark"
                        x-on:click="clear()"
                        :title="__('Clear search')"
                        :aria-label="__('Clear search')"
                    />
                </div>
            </div>
        </template>
    </div>

    @push('styles')
        <style>
            .help-guide-content,
            .help-guide-content * {
                box-sizing: border-box;
            }

            .help-guide-content {
                line-height: 1.7;
            }

            .help-guide-content .cover {
                display: flex;
                min-height: 50vh;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                gap: 1rem;
                padding: 3rem 1rem;
                text-align: center;
            }

            .help-guide-content .cover-icon {
                display: flex;
                height: 5rem;
                width: 5rem;
                align-items: center;
                justify-content: center;
                border-radius: 1.25rem;
                background: #000c99;
                color: #fff;
            }

            .help-guide-content .cover-icon svg {
                height: 3rem;
                width: 3rem;
            }

            .help-guide-content h1 {
                margin-bottom: .75rem;
                color: #000c99;
                font-size: 2rem;
                font-weight: 800;
            }

            .help-guide-content h2 {
                margin-top: 2rem;
                margin-bottom: .75rem;
                border-bottom: 1px solid #e5e7eb;
                padding-bottom: .5rem;
                color: #000c99;
                font-size: 1.35rem;
                font-weight: 700;
            }

            .help-guide-content h3 {
                margin-top: 1.25rem;
                margin-bottom: .5rem;
                font-size: 1.1rem;
                font-weight: 650;
            }

            .help-guide-content h4 {
                margin-top: 1rem;
                margin-bottom: .35rem;
                font-weight: 650;
            }

            .help-guide-content p,
            .help-guide-content ul,
            .help-guide-content ol,
            .help-guide-content table,
            .help-guide-content details {
                margin-bottom: .85rem;
            }

            .help-guide-content ul,
            .help-guide-content ol {
                margin-left: 1.5rem;
            }

            .help-guide-content li {
                margin-bottom: .3rem;
            }

            .help-guide-content table {
                width: 100%;
                border-collapse: collapse;
                font-size: .9rem;
            }

            .help-guide-content th,
            .help-guide-content td {
                border: 1px solid #e5e7eb;
                padding: .6rem .75rem;
                text-align: left;
            }

            .help-guide-content th {
                background: #f8fafc;
                color: #374151;
                font-weight: 650;
            }

            .help-guide-content .toc,
            .help-guide-content .flow,
            .help-guide-content .sidebar-ref,
            .help-guide-content .info-box,
            .help-guide-content .tip-box,
            .help-guide-content .warning-box {
                border-radius: .75rem;
                margin: 1rem 0;
                padding: 1rem 1.25rem;
            }

            .help-guide-content .toc,
            .help-guide-content .flow {
                border: 1px solid #e5e7eb;
                background: #f8fafc;
                color: #374151;
            }

            .help-guide-content .flow {
                overflow-x: auto;
                white-space: pre-wrap;
                font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
                font-size: .85rem;
            }

            .help-guide-content .info-box {
                border-left: 4px solid #3b82f6;
                background: #eff6ff;
            }

            .help-guide-content .tip-box {
                border-left: 4px solid #22c55e;
                background: #f0fdf4;
            }

            .help-guide-content .warning-box {
                border-left: 4px solid #f59e0b;
                background: #fffbeb;
            }

            .help-guide-content .role-header {
                border-radius: .75rem;
                color: #fff;
                font-size: 1.35rem;
                font-weight: 750;
                margin: 2.5rem 0 1.25rem;
                padding: 1.25rem 1.5rem;
            }

            .help-guide-content .role-admin { background: linear-gradient(135deg, #000c99, #1a3abf); }
            .help-guide-content .role-teacher { background: linear-gradient(135deg, #059669, #10b981); }
            .help-guide-content .role-student { background: linear-gradient(135deg, #2563eb, #3b82f6); }
            .help-guide-content .role-parent { background: linear-gradient(135deg, #7c3aed, #8b5cf6); }

            .help-guide-content .badge {
                display: inline-block;
                border-radius: 999px;
                padding: .15rem .5rem;
                font-size: .75rem;
                font-weight: 650;
            }

            .help-guide-content .badge-blue { background: #dbeafe; color: #1d4ed8; }
            .help-guide-content .badge-green { background: #dcfce7; color: #15803d; }
            .help-guide-content .badge-amber { background: #fef3c7; color: #92400e; }
            .help-guide-content .badge-red { background: #fee2e2; color: #991b1b; }
            .help-guide-content .badge-purple { background: #f3e8ff; color: #6b21a8; }

            .help-guide-content mark[data-help-search] {
                background: #fde047;
                border-radius: .15rem;
                color: #111827;
                padding: 0 .05rem;
            }

            .help-guide-content mark[data-help-search][data-active="true"] {
                background: #f97316;
                color: #fff;
            }

            .dark .help-guide-content th,
            .dark .help-guide-content .toc,
            .dark .help-guide-content .flow {
                border-color: #3f3f46;
                background: #18181b;
                color: #d4d4d8;
            }

            .dark .help-guide-content .info-box {
                border-left-color: #60a5fa;
                background: #172554;
                color: #dbeafe;
            }

            .dark .help-guide-content .tip-box {
                border-left-color: #4ade80;
                background: #052e16;
                color: #dcfce7;
            }

            .dark .help-guide-content .warning-box {
                border-left-color: #fbbf24;
                background: #451a03;
                color: #fef3c7;
            }

            .dark .help-guide-content .sidebar-ref {
                border-color: #6d28d9;
                background: #2e1065;
                color: #ede9fe;
            }

            .dark .help-guide-content .info-box strong,
            .dark .help-guide-content .tip-box strong,
            .dark .help-guide-content .warning-box strong,
            .dark .help-guide-content .sidebar-ref strong {
                color: inherit;
            }
        </style>
    @endpush
</x-layouts::app>
