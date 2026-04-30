<x-layouts::app :title="__(':name — Report Cards', ['name' => $child->name])">
    <div class="space-y-6">
        {{-- Breadcrumb --}}
        <div class="flex items-center gap-2 flex-wrap">
            <flux:link href="{{ route('parent.dashboard') }}" wire:navigate class="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">
                {{ __('Dashboard') }}
            </flux:link>
            <flux:icon.chevron-right class="w-3 h-3 text-zinc-400" />
            <flux:link href="{{ route('parent.children.show', $child) }}" wire:navigate class="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">
                {{ $child->name }}
            </flux:link>
            <flux:icon.chevron-right class="w-3 h-3 text-zinc-400" />
            <flux:text class="text-sm">{{ __('Report Cards') }}</flux:text>
        </div>

        <x-admin-header
            :title="__(':name\'s Report Cards', ['name' => $child->name])"
            :description="$child->studentProfile?->class?->name"
        />

        {{-- Session Filter --}}
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('parent.children.report-cards', $child) }}"
               class="px-3 py-1.5 rounded-lg text-sm font-medium transition {{ !$selectedSessionId ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-400 dark:hover:bg-zinc-700' }}"
               wire:navigate>
                {{ __('All') }}
            </a>
            @foreach ($sessions as $session)
                <a href="{{ route('parent.children.report-cards', [$child, 'session_id' => $session->id]) }}"
                   class="px-3 py-1.5 rounded-lg text-sm font-medium transition {{ $selectedSessionId == $session->id ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-400 dark:hover:bg-zinc-700' }}"
                   wire:navigate>
                    {{ $session->name }}
                </a>
            @endforeach
        </div>

        @if ($reports->isEmpty())
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-8 text-center">
                <flux:icon name="document-text" class="mx-auto size-12 text-zinc-300 dark:text-zinc-600 mb-3" />
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-1">{{ __('No Report Cards Yet') }}</h3>
                <p class="text-sm text-zinc-500">{{ __('Published report cards for :name will appear here.', ['name' => $child->name]) }}</p>
            </div>
        @else
            @php $groupedBySession = $reports->groupBy('session_id'); @endphp
            @foreach ($groupedBySession as $sessionId => $sessionReports)
                @php
                    $sessionName = $sessionReports->first()->session->name ?? 'Unknown';
                    $termReports = $sessionReports->whereNotNull('term_id')->groupBy(fn($r) => $r->term->name ?? 'Unknown');
                    $sessionLevelReports = $sessionReports->whereNull('term_id');
                @endphp
                <div class="space-y-4">
                    <h3 class="text-sm font-semibold text-zinc-500 uppercase tracking-wide">{{ $sessionName }} {{ __('Session') }}</h3>

                    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 divide-y divide-zinc-100 dark:divide-zinc-700">
                        @foreach ($termReports as $termName => $reportsForTerm)
                            <div class="p-4 space-y-3">
                                <h4 class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $termName }}</h4>
                                <div class="space-y-2">
                                    @foreach ($reportsForTerm as $report)
                                        @include('student.report-cards._report-row', ['report' => $report, 'child' => $child, 'showPosition' => $showPosition])
                                    @endforeach
                                </div>
                            </div>
                        @endforeach

                        @if ($sessionLevelReports->isNotEmpty())
                            <div class="p-4 space-y-3">
                                <h4 class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Session Overview') }}</h4>
                                <div class="space-y-2">
                                    @foreach ($sessionLevelReports as $report)
                                        @include('student.report-cards._report-row', ['report' => $report, 'child' => $child, 'showPosition' => $showPosition])
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</x-layouts::app>
