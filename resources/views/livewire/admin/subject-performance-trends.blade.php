<div>
    @php
        $resolveGrade = function (float $pct) use ($gradingItems) {
            foreach ($gradingItems as $item) {
                if ($pct >= $item->min_score && $pct <= $item->max_score) {
                    return $item;
                }
            }
            return null;
        };
    @endphp

    {{-- Filters --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div>
            <flux:select wire:model.live="classId" placeholder="{{ __('Select Class') }}">
                <option value="">{{ __('All Classes') }}</option>
                @foreach ($classes as $class)
                    <option value="{{ $class->id }}">{{ $class->name }}</option>
                @endforeach
            </flux:select>
        </div>
        <div>
            <flux:select wire:model.live="sessionId" placeholder="{{ __('Select Session') }}">
                @foreach ($sessions as $session)
                    <option value="{{ $session->id }}">{{ $session->name }}</option>
                @endforeach
            </flux:select>
        </div>
        <div>
            <flux:select wire:model.live="category" placeholder="{{ __('All Categories') }}">
                <option value="">{{ __('All Categories') }}</option>
                <option value="assessment">{{ __('Assessments') }}</option>
                <option value="assignment">{{ __('Assignments') }}</option>
                <option value="exam">{{ __('Exams') }}</option>
            </flux:select>
        </div>
    </div>

    @if (!$this->classId || !$this->sessionId)
        <flux:card class="p-8 text-center">
            <flux:icon name="chart-bar" class="mx-auto size-12 text-zinc-300 dark:text-zinc-600 mb-3" />
            <p class="text-zinc-500">{{ __('Select a class and session to view subject performance trends.') }}</p>
        </flux:card>
    @elseif (empty($trendData))
        <flux:card class="p-8 text-center">
            <flux:icon name="chart-bar" class="mx-auto size-12 text-zinc-300 dark:text-zinc-600 mb-3" />
            <p class="text-zinc-500">{{ __('No exam data found for the selected filters.') }}</p>
        </flux:card>
    @else
        {{-- Trend Chart --}}
        <flux:card class="p-5 mb-6">
            <h3 class="text-sm font-semibold mb-4">{{ __('Average Score by Subject Across Terms') }}</h3>
            <div class="h-80" wire:ignore>
                <canvas id="subjectTrendChart"></canvas>
            </div>
        </flux:card>

        {{-- Summary Table --}}
        <flux:card class="p-5">
            <h3 class="text-sm font-semibold mb-4">{{ __('Subject Summary') }}</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 dark:border-zinc-700">
                            <th class="text-left py-2 px-3 font-medium text-zinc-600 dark:text-zinc-400">{{ __('Subject') }}</th>
                            @foreach ($terms as $term)
                                <th class="text-center py-2 px-3 font-medium text-zinc-600 dark:text-zinc-400">{{ $term->name }}</th>
                            @endforeach
                            <th class="text-center py-2 px-3 font-medium text-zinc-600 dark:text-zinc-400">{{ __('Overall') }}</th>
                            @if ($gradingItems->isNotEmpty())
                                <th class="text-center py-2 px-3 font-medium text-zinc-600 dark:text-zinc-400">{{ __('Grade') }}</th>
                            @endif
                            <th class="text-center py-2 px-3 font-medium text-zinc-600 dark:text-zinc-400">{{ __('Exams') }}</th>
                            <th class="text-center py-2 px-3 font-medium text-zinc-600 dark:text-zinc-400">{{ __('Trend') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($subjectSummary as $subject)
                            <tr class="border-b border-zinc-100 dark:border-zinc-800">
                                <td class="py-2 px-3 font-medium">{{ $subject['subject_name'] }}</td>
                                @foreach ($subject['term_averages'] as $termName => $avg)
                                    <td class="text-center py-2 px-3">
                                        @if ($avg !== null)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                                {{ $avg >= 70 ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' :
                                                   ($avg >= 50 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' :
                                                   'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400') }}">
                                                {{ $avg }}%
                                            </span>
                                        @else
                                            <span class="text-zinc-400">—</span>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="text-center py-2 px-3">
                                    @if ($subject['overall_average'] !== null)
                                        <span class="font-bold {{ $subject['overall_average'] >= 50 ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $subject['overall_average'] }}%
                                        </span>
                                    @else
                                        <span class="text-zinc-400">—</span>
                                    @endif
                                </td>
                                @if ($gradingItems->isNotEmpty())
                                    <td class="text-center py-2 px-3">
                                        @if ($subject['overall_average'] !== null)
                                            @php $g = $resolveGrade($subject['overall_average']); @endphp
                                            @if ($g)
                                                <span class="text-sm font-semibold text-indigo-600 dark:text-indigo-400" title="{{ $g->label }}">{{ $g->grade }}</span>
                                            @else
                                                <span class="text-zinc-400">—</span>
                                            @endif
                                        @else
                                            <span class="text-zinc-400">—</span>
                                        @endif
                                    </td>
                                @endif
                                <td class="text-center py-2 px-3 text-zinc-500">{{ $subject['exam_count'] }}</td>
                                <td class="text-center py-2 px-3">
                                    @if ($subject['trend'] === 'up')
                                        <flux:icon name="arrow-trending-up" class="inline size-5 text-green-500" />
                                    @elseif ($subject['trend'] === 'down')
                                        <flux:icon name="arrow-trending-down" class="inline size-5 text-red-500" />
                                    @else
                                        <flux:icon name="minus" class="inline size-5 text-zinc-400" />
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </flux:card>
    @endif

    @if (!empty($trendData))
        @push('scripts')
            <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
            <script>
                document.addEventListener('livewire:navigated', initSubjectTrendChart);
                document.addEventListener('DOMContentLoaded', initSubjectTrendChart);

                function initSubjectTrendChart() {
                    const canvas = document.getElementById('subjectTrendChart');
                    if (!canvas) return;

                    if (canvas._chartInstance) {
                        canvas._chartInstance.destroy();
                    }

                    const trendData = @json($trendData);
                    const terms = @json($terms->pluck('name'));
                    const colors = [
                        '#4F46E5', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6',
                        '#EC4899', '#06B6D4', '#84CC16', '#F97316', '#6366F1',
                        '#14B8A6', '#E11D48', '#0EA5E9', '#A855F7', '#22C55E',
                    ];

                    const datasets = trendData.map((subject, index) => ({
                        label: subject.subject_name,
                        data: subject.terms.map(t => t.average),
                        borderColor: colors[index % colors.length],
                        backgroundColor: colors[index % colors.length] + '20',
                        tension: 0.3,
                        fill: false,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        spanGaps: true,
                    }));

                    canvas._chartInstance = new Chart(canvas, {
                        type: 'line',
                        data: { labels: terms, datasets },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    max: 100,
                                    ticks: { callback: v => v + '%' },
                                },
                            },
                            plugins: {
                                tooltip: {
                                    callbacks: {
                                        label: ctx => ctx.dataset.label + ': ' + (ctx.parsed.y !== null ? ctx.parsed.y + '%' : 'N/A'),
                                    },
                                },
                                legend: { position: 'bottom' },
                            },
                        },
                    });
                }
            </script>
        @endpush
    @endif
</div>
