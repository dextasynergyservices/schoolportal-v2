<x-layouts::app :title="__('My Report Cards')">
    <div class="space-y-6">
        <x-student-header
            :title="__('My Report Cards')"
            :description="__('View your term report cards and CBT results.')"
        />

        {{-- Tabs: Term Reports | CBT Results --}}
        <div class="flex items-center gap-1 overflow-x-auto border-b border-zinc-200 dark:border-zinc-700" role="tablist" aria-label="{{ __('Report type') }}">
            <a href="{{ route('student.report-cards.index', array_filter(['session_id' => $selectedSessionId])) }}"
               role="tab"
               aria-selected="{{ $activeTab === 'term-reports' ? 'true' : 'false' }}"
               @if ($activeTab === 'term-reports') aria-current="page" @endif
               class="shrink-0 px-4 py-2.5 text-sm font-medium border-b-2 transition {{ $activeTab === 'term-reports' ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}"
               wire:navigate>
                {{ __('Term Reports') }}
                <span class="ml-1 text-xs text-zinc-400">({{ $reports->count() }})</span>
            </a>
            <a href="{{ route('student.report-cards.index', ['tab' => 'cbt-results']) }}"
               role="tab"
               aria-selected="{{ $activeTab === 'cbt-results' ? 'true' : 'false' }}"
               @if ($activeTab === 'cbt-results') aria-current="page" @endif
               class="shrink-0 class="ml-1 text-xs text-zinc-400">({{ $reports->count() }})</span>
            </a>
            <a href="{{ route('student.report-cards.index', ['tab' => 'cbt-results']) }}"
               role="tab"
               aria-selected="{{ $activeTab === 'cbt-results' ? 'true' : 'false' }}"
               @if ($activeTab === 'cbt-results') aria-current="page" @endif
               class="shrink-0 px-4 py-2.5 text-sm font-medium border-b-2 transition {{ $activeTab === 'cbt-results' ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}"
               wire:navigate>
                {{ __('CBT Results') }}
                <span class="ml-1 text-xs text-zinc-400">({{ $cbtResultsCount }})</span>
            </a>
        </div>

        {{-- ════════════════════════════════════════════════════════ --}}
        {{-- Tab 1: Term Reports                                     --}}
        {{-- ════════════════════════════════════════════════════════ --}}
        @if ($activeTab === 'term-reports')
            {{-- Session Filter --}}
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('student.report-cards.index') }}"
                   class="px-3 py-1.5 rounded-lg text-sm font-medium transition {{ !$selectedSessionId ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-400 dark:hover:bg-zinc-700' }}"
                   wire:navigate>
                    {{ __('All') }}
                </a>
                @foreach ($sessions as $session)
                    <a href="{{ route('student.report-cards.index', ['session_id' => $session->id]) }}"
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
                    <p class="text-sm text-zinc-500">{{ __('Your published report cards will appear here.') }}</p>
                </div>
            @else
                {{-- Group by session --}}
                @php $groupedBySession = $reports->groupBy('session_id'); @endphp
                @foreach ($groupedBySession as $sessionId => $sessionReports)
                    @php
                        $sessionName = $sessionReports->first()->session->name ?? 'Unknown';
                        $termReports = $sessionReports->whereNotNull('term_id')->groupBy(fn($r) => $r->term->name ?? 'Unknown');
                        $sessionLevelReports = $sessionReports->whereNull('term_id');
                    @endphp
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-zinc-500 uppercase tracking-wide">{{ $sessionName }} {{ __('Session') }}</h3>
                            @if ($sessionReports->count() > 1)
                                <a href="{{ route('student.report-cards.session-summary', $sessionId) }}" class="text-xs text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 font-medium" wire:navigate>
                                    {{ __('View Session Summary →') }}
                                </a>
                            @endif
                        </div>

                        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 divide-y divide-zinc-100 dark:divide-zinc-700">
                            {{-- Term reports grouped by term --}}
                            @foreach ($termReports as $termName => $reportsForTerm)
                                <div class="p-4 space-y-3">
                                    <h4 class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $termName }}</h4>
                                    <div class="space-y-2">
                                        @foreach ($reportsForTerm as $report)
                                            @include('student.report-cards._report-row', ['report' => $report, 'showPosition' => $showPosition])
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach

                            {{-- Session-level reports --}}
                            @if ($sessionLevelReports->isNotEmpty())
                                <div class="p-4 space-y-3">
                                    <h4 class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Session Overview') }}</h4>
                                    <div class="space-y-2">
                                        @foreach ($sessionLevelReports as $report)
                                            @include('student.report-cards._report-row', ['report' => $report, 'showPosition' => $showPosition])
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            @endif

        {{-- ════════════════════════════════════════════════════════ --}}
        {{-- Tab 2: CBT Results                                      --}}
        {{-- ════════════════════════════════════════════════════════ --}}
        @else
            {{-- Category Filter --}}
            <div class="flex flex-wrap items-center gap-3">
                @foreach ([['all', 'All'], ['exam', 'Exams'], ['assessment', 'Assessments'], ['assignment', 'Assignments']] as [$key, $label])
                    <a href="{{ route('student.report-cards.index', ['tab' => 'cbt-results', 'category' => $key]) }}"
                       class="px-3 py-1.5 rounded-lg text-sm font-medium transition {{ $selectedCategory === $key ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-400 dark:hover:bg-zinc-700' }}"
                       wire:navigate>
                        {{ __($label) }}
                    </a>
                @endforeach
            </div>

            @if ($attempts && $attempts->isNotEmpty())
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="text-xs text-zinc-500 uppercase bg-zinc-50 dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700">
                                <tr>
                                    <th class="px-4 py-3 text-left">{{ __('Title') }}</th>
                                    <th class="px-3 py-3 text-left">{{ __('Subject') }}</th>
                                    <th class="px-3 py-3 text-center">{{ __('Type') }}</th>
                                    <th class="px-3 py-3 text-center">{{ __('Score') }}</th>
                                    <th class="px-3 py-3 text-center">{{ __('Percentage') }}</th>
                                    <th class="px-3 py-3 text-center">{{ __('Grade') }}</th>
                                    <th class="px-3 py-3 text-center">{{ __('Status') }}</th>
                                    <th class="px-3 py-3 text-center">{{ __('Date') }}</th>
                                    <th class="px-3 py-3 text-center">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                                @foreach ($attempts as $attempt)
                                    @php
                                        $exam = $attempt->exam;
                                        $categoryLabel = match($exam->category ?? '') {
                                            'exam' => 'Exam',
                                            'assessment' => 'Assessment',
                                            'assignment' => 'Assignment',
                                            default => ucfirst($exam->category ?? ''),
                                        };
                                        $categoryColor = match($exam->category ?? '') {
                                            'exam' => 'red',
                                            'assessment' => 'blue',
                                            'assignment' => 'amber',
                                            default => 'zinc',
                                        };
                                        $routePrefix = 'student.exams';
                                    @endphp
                                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                        <td class="px-4 py-3 font-medium text-zinc-900 dark:text-white">{{ $exam->title ?? '—' }}</td>
                                        <td class="px-3 py-3 text-zinc-600 dark:text-zinc-400">{{ $exam->subject->short_name ?? $exam->subject->name ?? '—' }}</td>
                                        <td class="px-3 py-3 text-center">
                                            <flux:badge size="sm" :color="$categoryColor">{{ $categoryLabel }}</flux:badge>
                                        </td>
                                        <td class="px-3 py-3 text-center font-semibold {{ $attempt->passed ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $attempt->score ?? '?' }}/{{ $attempt->total_points }}
                                        </td>
                                        <td class="px-3 py-3 text-center font-semibold {{ $attempt->passed ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $attempt->percentage !== null ? number_format($attempt->percentage, 1) . '%' : '—' }}
                                        </td>
                                        <td class="px-3 py-3 text-center">
                                            @if (isset($grades[$attempt->id]) && $grades[$attempt->id])
                                                <span class="text-sm font-semibold text-indigo-600 dark:text-indigo-400" title="{{ $grades[$attempt->id]['label'] }}">{{ $grades[$attempt->id]['grade'] }}</span>
                                            @else
                                                <span class="text-zinc-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3 text-center">
                                            @if ($attempt->status === 'grading')
                                                <flux:badge size="sm" color="amber">{{ __('Grading') }}</flux:badge>
                                            @elseif ($attempt->passed)
                                                <flux:badge size="sm" color="green">{{ __('Passed') }}</flux:badge>
                                            @else
                                                <flux:badge size="sm" color="red">{{ __('Failed') }}</flux:badge>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3 text-center text-zinc-500">{{ $attempt->submitted_at?->format('M d, Y') ?? '—' }}</td>
                                        <td class="px-3 py-3 text-center">
                                            <a href="{{ route($routePrefix . '.results', $attempt) }}" wire:navigate>
                                                <flux:button variant="subtle" size="sm" icon="eye">{{ __('Review') }}</flux:button>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-4">{{ $attempts->links() }}</div>
            @else
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-8 text-center">
                    <flux:icon name="computer-desktop" class="mx-auto size-12 text-zinc-300 dark:text-zinc-600 mb-3" />
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-1">{{ __('No CBT Results Yet') }}</h3>
                    <p class="text-sm text-zinc-500">{{ __('Your completed CBT results will appear here.') }}</p>
                </div>
            @endif
        @endif
    </div>
</x-layouts::app>
