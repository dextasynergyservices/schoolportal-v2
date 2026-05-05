<x-layouts::app :title="__('Content Library')">
    <div class="space-y-6">
        <x-admin-header
            :title="__('Content Library')"
            :description="__('Read-only cross-school overview of all educational content on the platform')"
        />

        @if (session('success'))
            <flux:callout variant="success" icon="check-circle">{{ session('success') }}</flux:callout>
        @endif

        {{-- Platform-wide summary cards --}}
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
            @foreach ([
                ['label' => __('Quizzes'),     'total' => $totals['quizzes'],     'pending' => $pendingCounts['quizzes'],     'icon' => 'document-text',          'color' => 'bg-indigo-50 dark:bg-indigo-900/20',  'iconColor' => 'text-indigo-600 dark:text-indigo-400'],
                ['label' => __('Games'),       'total' => $totals['games'],       'pending' => 0,                             'icon' => 'puzzle-piece',            'color' => 'bg-violet-50 dark:bg-violet-900/20',  'iconColor' => 'text-violet-600 dark:text-violet-400'],
                ['label' => __('Exams (CBT)'), 'total' => $totals['exams'],       'pending' => $pendingCounts['exams'],       'icon' => 'clipboard-document-list', 'color' => 'bg-blue-50 dark:bg-blue-900/20',     'iconColor' => 'text-blue-600 dark:text-blue-400'],
                ['label' => __('Results'),     'total' => $totals['results'],     'pending' => $pendingCounts['results'],     'icon' => 'chart-bar',               'color' => 'bg-green-50 dark:bg-green-900/20',   'iconColor' => 'text-green-600 dark:text-green-400'],
                ['label' => __('Assignments'), 'total' => $totals['assignments'], 'pending' => $pendingCounts['assignments'], 'icon' => 'paper-clip',              'color' => 'bg-amber-50 dark:bg-amber-900/20',   'iconColor' => 'text-amber-600 dark:text-amber-400'],
            ] as $stat)
                <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex items-start justify-between gap-2">
                        <div class="{{ $stat['color'] }} flex size-9 shrink-0 items-center justify-center rounded-lg">
                            <flux:icon :name="$stat['icon']" class="size-5 {{ $stat['iconColor'] }}" />
                        </div>
                        @if ($stat['pending'] > 0)
                            <span
                                class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-amber-100 px-1.5 text-xs font-semibold text-amber-700 dark:bg-amber-900/40 dark:text-amber-400"
                                title="{{ __(':n pending approval', ['n' => $stat['pending']]) }}"
                            >{{ $stat['pending'] }}</span>
                        @endif
                    </div>
                    <div class="mt-3 text-2xl font-bold text-zinc-900 dark:text-white">
                        {{ number_format($stat['total']) }}
                    </div>
                    <div class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">{{ $stat['label'] }}</div>
                </div>
            @endforeach
        </div>

        {{-- Filters --}}
        <form method="GET" action="{{ route('super-admin.content.index') }}" class="flex flex-wrap items-end gap-3">
            <div class="min-w-48 flex-1">
                <flux:input
                    name="search"
                    :value="request('search')"
                    placeholder="{{ __('Filter by school name…') }}"
                    icon="magnifying-glass"
                    aria-label="{{ __('Search schools') }}"
                />
            </div>
            <flux:select name="sort" class="min-w-48" aria-label="{{ __('Sort by') }}">
                <option value="" @selected(!request('sort'))>{{ __('Most Quizzes') }}</option>
                <option value="games"       @selected(request('sort') === 'games')>{{ __('Most Games') }}</option>
                <option value="exams"       @selected(request('sort') === 'exams')>{{ __('Most Exams') }}</option>
                <option value="results"     @selected(request('sort') === 'results')>{{ __('Most Results') }}</option>
                <option value="assignments" @selected(request('sort') === 'assignments')>{{ __('Most Assignments') }}</option>
                <option value="name"        @selected(request('sort') === 'name')>{{ __('Name A→Z') }}</option>
            </flux:select>
            <flux:button type="submit" variant="filled" size="sm">{{ __('Filter') }}</flux:button>
            @if (request()->hasAny(['search', 'sort']))
                <flux:button variant="subtle" size="sm" href="{{ route('super-admin.content.index') }}" wire:navigate>
                    {{ __('Clear') }}
                </flux:button>
            @endif
        </form>

        {{-- Per-school breakdown --}}
        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            {{-- Table header --}}
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                {{ __('School') }}
                            </th>
                            <th scope="col" class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400 sm:table-cell">
                                <div class="flex items-center gap-1.5">
                                    <flux:icon.document-text class="size-3.5 text-indigo-500" />
                                    {{ __('Quizzes') }}
                                </div>
                            </th>
                            <th scope="col" class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400 sm:table-cell">
                                <div class="flex items-center gap-1.5">
                                    <flux:icon.puzzle-piece class="size-3.5 text-violet-500" />
                                    {{ __('Games') }}
                                </div>
                            </th>
                            <th scope="col" class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400 md:table-cell">
                                <div class="flex items-center gap-1.5">
                                    <flux:icon.clipboard-document-list class="size-3.5 text-blue-500" />
                                    {{ __('Exams') }}
                                </div>
                            </th>
                            <th scope="col" class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400 md:table-cell">
                                <div class="flex items-center gap-1.5">
                                    <flux:icon.chart-bar class="size-3.5 text-green-500" />
                                    {{ __('Results') }}
                                </div>
                            </th>
                            <th scope="col" class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400 lg:table-cell">
                                <div class="flex items-center gap-1.5">
                                    <flux:icon.paper-clip class="size-3.5 text-amber-500" />
                                    {{ __('Assignments') }}
                                </div>
                            </th>
                            <th scope="col" class="w-24 px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse ($schools as $school)
                            <tr class="group hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                {{-- School name --}}
                                <td class="px-4 py-3">
                                    <a href="{{ route('super-admin.schools.show', $school) }}" wire:navigate class="block min-w-0 hover:underline">
                                        <div class="flex items-center gap-2">
                                            @if ($school->logo_url)
                                                <img src="{{ $school->logoSmallUrl() }}" alt="" class="size-7 shrink-0 rounded object-contain" aria-hidden="true" />
                                            @else
                                                <div class="flex size-7 shrink-0 items-center justify-center rounded bg-zinc-100 dark:bg-zinc-700">
                                                    <flux:icon.building-office-2 class="size-4 text-zinc-400" />
                                                </div>
                                            @endif
                                            <div>
                                                <div class="font-medium text-zinc-900 dark:text-white">{{ $school->name }}</div>
                                                <div class="truncate text-xs text-zinc-500">{{ $school->custom_domain ?? $school->email }}</div>
                                            </div>
                                        </div>
                                    </a>
                                </td>

                                {{-- Quizzes --}}
                                <td class="hidden px-4 py-3 sm:table-cell">
                                    @if ($school->quizzes_total > 0)
                                        <div class="text-sm font-semibold text-zinc-900 dark:text-white">{{ number_format($school->quizzes_total) }}</div>
                                        <div class="mt-0.5 flex flex-wrap gap-x-2 gap-y-0.5">
                                            @if ($school->quizzes_published > 0)
                                                <span class="inline-flex items-center gap-1 text-xs text-green-600 dark:text-green-400">
                                                    <span class="size-1.5 rounded-full bg-green-500" aria-hidden="true"></span>{{ $school->quizzes_published }} {{ __('live') }}
                                                </span>
                                            @endif
                                            @if ($school->quizzes_pending > 0)
                                                <span class="inline-flex items-center gap-1 text-xs font-medium text-amber-600 dark:text-amber-400">
                                                    <span class="size-1.5 rounded-full bg-amber-500" aria-hidden="true"></span>{{ $school->quizzes_pending }} {{ __('pending') }}
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-xs text-zinc-300 dark:text-zinc-600">—</span>
                                    @endif
                                </td>

                                {{-- Games --}}
                                <td class="hidden px-4 py-3 sm:table-cell">
                                    @if ($school->games_total > 0)
                                        <div class="text-sm font-semibold text-zinc-900 dark:text-white">{{ number_format($school->games_total) }}</div>
                                        @if ($school->games_published > 0)
                                            <div class="mt-0.5 flex items-center gap-1 text-xs text-green-600 dark:text-green-400">
                                                <span class="size-1.5 rounded-full bg-green-500" aria-hidden="true"></span>{{ $school->games_published }} {{ __('live') }}
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-xs text-zinc-300 dark:text-zinc-600">—</span>
                                    @endif
                                </td>

                                {{-- Exams --}}
                                <td class="hidden px-4 py-3 md:table-cell">
                                    @if ($school->exams_total > 0)
                                        <div class="text-sm font-semibold text-zinc-900 dark:text-white">{{ number_format($school->exams_total) }}</div>
                                        @if ($school->exams_published > 0)
                                            <div class="mt-0.5 flex items-center gap-1 text-xs text-green-600 dark:text-green-400">
                                                <span class="size-1.5 rounded-full bg-green-500" aria-hidden="true"></span>{{ $school->exams_published }} {{ __('live') }}
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-xs text-zinc-300 dark:text-zinc-600">—</span>
                                    @endif
                                </td>

                                {{-- Results --}}
                                <td class="hidden px-4 py-3 md:table-cell">
                                    @if ($school->results_total > 0)
                                        <div class="text-sm font-semibold text-zinc-900 dark:text-white">{{ number_format($school->results_total) }}</div>
                                        <div class="mt-0.5 flex flex-wrap gap-x-2 gap-y-0.5">
                                            @if ($school->results_approved > 0)
                                                <span class="inline-flex items-center gap-1 text-xs text-green-600 dark:text-green-400">
                                                    <span class="size-1.5 rounded-full bg-green-500" aria-hidden="true"></span>{{ $school->results_approved }} {{ __('ok') }}
                                                </span>
                                            @endif
                                            @if ($school->results_pending > 0)
                                                <span class="inline-flex items-center gap-1 text-xs font-medium text-amber-600 dark:text-amber-400">
                                                    <span class="size-1.5 rounded-full bg-amber-500" aria-hidden="true"></span>{{ $school->results_pending }} {{ __('pending') }}
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-xs text-zinc-300 dark:text-zinc-600">—</span>
                                    @endif
                                </td>

                                {{-- Assignments --}}
                                <td class="hidden px-4 py-3 lg:table-cell">
                                    @if ($school->assignments_total > 0)
                                        <div class="text-sm font-semibold text-zinc-900 dark:text-white">{{ number_format($school->assignments_total) }}</div>
                                        @if ($school->assignments_pending > 0)
                                            <div class="mt-0.5 flex items-center gap-1 text-xs font-medium text-amber-600 dark:text-amber-400">
                                                <span class="size-1.5 rounded-full bg-amber-500" aria-hidden="true"></span>{{ $school->assignments_pending }} {{ __('pending') }}
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-xs text-zinc-300 dark:text-zinc-600">—</span>
                                    @endif
                                </td>

                                {{-- Action --}}
                                <td class="px-4 py-3">
                                    <flux:button
                                        variant="ghost"
                                        size="xs"
                                        icon="arrow-top-right-on-square"
                                        href="{{ route('super-admin.schools.show', $school) }}"
                                        wire:navigate
                                        aria-label="{{ __('View :name', ['name' => $school->name]) }}"
                                    >
                                        {{ __('View') }}
                                    </flux:button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-16 text-center">
                                    <flux:icon.document-text class="mx-auto mb-3 size-12 text-zinc-200 dark:text-zinc-700" />
                                    <flux:text class="text-zinc-500">{{ __('No schools found.') }}</flux:text>
                                    @if (request()->hasAny(['search', 'sort']))
                                        <div class="mt-2">
                                            <flux:button variant="subtle" size="sm" href="{{ route('super-admin.content.index') }}" wire:navigate>
                                                {{ __('Clear filters') }}
                                            </flux:button>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if ($schools->hasPages())
                <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                    {{ $schools->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layouts::app>
