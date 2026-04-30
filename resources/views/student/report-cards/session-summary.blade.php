<x-layouts::app :title="__('Session Summary — :session', ['session' => $session->name])">
    <div class="space-y-6">
        <div>
            <flux:button variant="subtle" size="sm" href="{{ route('student.report-cards.index') }}" wire:navigate class="mb-2">
                <flux:icon name="arrow-left" class="size-4 mr-1" /> {{ __('Back to Report Cards') }}
            </flux:button>
            <h1 class="text-xl font-bold text-zinc-900 dark:text-white">{{ __('Session Summary') }}</h1>
            <p class="text-sm text-zinc-500">{{ $session->name }} — {{ __('All terms combined') }}</p>
        </div>

        {{-- Overall Stats --}}
        @php
            $overallAvg = $reports->avg('average_weighted_score');
            $bestTerm = $reports->sortByDesc('average_weighted_score')->first();
        @endphp
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 text-center">
                <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ number_format($overallAvg ?? 0, 1) }}%</div>
                <div class="text-xs text-zinc-400 mt-1">{{ __('Session Average') }}</div>
            </div>
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 text-center">
                <div class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $reports->count() }}</div>
                <div class="text-xs text-zinc-400 mt-1">{{ __('Terms') }}</div>
            </div>
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 text-center">
                <div class="text-2xl font-bold text-green-600">{{ $bestTerm?->term->name ?? '—' }}</div>
                <div class="text-xs text-zinc-400 mt-1">{{ __('Best Term') }}</div>
            </div>
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 text-center">
                <div class="text-2xl font-bold text-zinc-600 dark:text-zinc-300">{{ number_format($bestTerm?->average_weighted_score ?? 0, 1) }}%</div>
                <div class="text-xs text-zinc-400 mt-1">{{ __('Best Score') }}</div>
            </div>
        </div>

        {{-- Term-by-Term Comparison --}}
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 overflow-hidden">
            <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                <h3 class="font-semibold text-zinc-900 dark:text-white">{{ __('Term-by-Term Performance') }}</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-xs text-zinc-500 uppercase bg-zinc-50 dark:bg-zinc-800">
                        <tr>
                            <th class="px-4 py-3 text-left">{{ __('Term') }}</th>
                            <th class="px-3 py-3 text-center">{{ __('Average') }}</th>
                            <th class="px-3 py-3 text-center">{{ __('Position') }}</th>
                            <th class="px-3 py-3 text-center">{{ __('Out Of') }}</th>
                            <th class="px-3 py-3 text-center">{{ __('Subjects') }}</th>
                            <th class="px-3 py-3 text-center">{{ __('Class') }}</th>
                            <th class="px-3 py-3 text-center">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                        @foreach ($reports as $report)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <td class="px-4 py-3 font-medium text-zinc-900 dark:text-white">{{ $report->term->name ?? '—' }}</td>
                                <td class="px-3 py-3 text-center font-semibold text-indigo-600 dark:text-indigo-400">{{ number_format($report->average_weighted_score ?? 0, 1) }}%</td>
                                <td class="px-3 py-3 text-center">
                                    @if ($report->position)
                                        {{ $report->position }}<sup class="text-xs">{{ match($report->position % 10) { 1 => 'st', 2 => 'nd', 3 => 'rd', default => 'th' } }}</sup>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-center text-zinc-600 dark:text-zinc-400">{{ $report->out_of ?? '—' }}</td>
                                <td class="px-3 py-3 text-center text-zinc-600 dark:text-zinc-400">{{ $report->subjects_count ?? 0 }}</td>
                                <td class="px-3 py-3 text-center text-zinc-600 dark:text-zinc-400">{{ $report->class->name ?? '—' }}</td>
                                <td class="px-3 py-3 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <a href="{{ route('student.report-cards.show', $report) }}" wire:navigate>
                                            <flux:button variant="subtle" size="sm" icon="eye">{{ __('View') }}</flux:button>
                                        </a>
                                        <a href="{{ route('student.report-cards.download', $report) }}">
                                            <flux:button variant="subtle" size="sm" icon="arrow-down-tray">{{ __('PDF') }}</flux:button>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts::app>
