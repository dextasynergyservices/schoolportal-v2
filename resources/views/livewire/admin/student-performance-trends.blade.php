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
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div>
            <flux:select wire:model.live="classId" placeholder="{{ __('Select Class') }}">
                <option value="">{{ __('Select Class') }}</option>
                @foreach ($classes as $class)
                    <option value="{{ $class->id }}">{{ $class->name }}</option>
                @endforeach
            </flux:select>
        </div>
        <div>
            <flux:select wire:model.live="studentId" placeholder="{{ __('Select Student') }}" :disabled="!$this->classId">
                <option value="">{{ __('Select Student') }}</option>
                @foreach ($students as $student)
                    <option value="{{ $student->id }}">{{ $student->name }} ({{ $student->username }})</option>
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

    @if (!$this->classId || !$this->studentId || !$this->sessionId)
        <flux:card class="p-8 text-center">
            <flux:icon name="academic-cap" class="mx-auto size-12 text-zinc-300 dark:text-zinc-600 mb-3" />
            <p class="text-zinc-500">{{ __('Select a class, student, and session to view performance trends.') }}</p>
        </flux:card>
    @elseif (!$overallStats)
        <flux:card class="p-8 text-center">
            <flux:icon name="academic-cap" class="mx-auto size-12 text-zinc-300 dark:text-zinc-600 mb-3" />
            <p class="text-zinc-500">{{ __('No exam data found for this student.') }}</p>
        </flux:card>
    @else
        {{-- Overall Stats --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
            <flux:card class="p-4">
                <span class="text-xs text-zinc-500 block">{{ __('Exams Taken') }}</span>
                <span class="text-2xl font-bold">{{ $overallStats['exams_taken'] }}/{{ $overallStats['exams_available'] }}</span>
            </flux:card>
            <flux:card class="p-4">
                <span class="text-xs text-zinc-500 block">{{ __('Overall Average') }}</span>
                <span class="text-2xl font-bold {{ ($overallStats['overall_average'] ?? 0) >= 50 ? 'text-green-600' : 'text-red-600' }}">
                    {{ $overallStats['overall_average'] !== null ? $overallStats['overall_average'] . '%' : '—' }}
                </span>
                @if ($overallStats['overall_average'] !== null && $gradingItems->isNotEmpty())
                    @php $overallGrade = $resolveGrade((float) $overallStats['overall_average']); @endphp
                    @if ($overallGrade)
                        <span class="block text-xs font-semibold text-indigo-600 dark:text-indigo-400 mt-0.5">{{ $overallGrade->grade }} &mdash; {{ $overallGrade->label }}</span>
                    @endif
                @endif
            </flux:card>
            <flux:card class="p-4">
                <span class="text-xs text-zinc-500 block">{{ __('Highest') }}</span>
                <span class="text-2xl font-bold text-green-600">{{ $overallStats['highest'] !== null ? $overallStats['highest'] . '%' : '—' }}</span>
            </flux:card>
            <flux:card class="p-4">
                <span class="text-xs text-zinc-500 block">{{ __('Lowest') }}</span>
                <span class="text-2xl font-bold text-red-600">{{ $overallStats['lowest'] !== null ? $overallStats['lowest'] . '%' : '—' }}</span>
            </flux:card>
            <flux:card class="p-4">
                <span class="text-xs text-zinc-500 block">{{ __('Passed') }}</span>
                <span class="text-2xl font-bold text-green-600">{{ $overallStats['passed'] }}</span>
            </flux:card>
            <flux:card class="p-4">
                <span class="text-xs text-zinc-500 block">{{ __('Failed') }}</span>
                <span class="text-2xl font-bold text-red-600">{{ $overallStats['failed'] }}</span>
            </flux:card>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            {{-- Overall Term Trend Chart --}}
            <flux:card class="p-5">
                <h3 class="text-sm font-semibold mb-4">{{ __('Overall Performance Across Terms') }}</h3>
                @if (collect($studentTrend)->pluck('average')->filter()->isNotEmpty())
                    <div class="h-64" wire:ignore>
                        <canvas id="studentOverallTrendChart"></canvas>
                    </div>
                @else
                    <div class="h-64 flex items-center justify-center text-zinc-400 text-sm">
                        {{ __('No data yet') }}
                    </div>
                @endif
            </flux:card>

            {{-- Subject Breakdown Radar Chart --}}
            <flux:card class="p-5">
                <h3 class="text-sm font-semibold mb-4">{{ __('Subject Strengths') }}</h3>
                @if (count($subjectBreakdown) >= 3)
                    <div class="h-64" wire:ignore>
                        <canvas id="studentRadarChart"></canvas>
                    </div>
                @else
                    <div class="h-64 flex items-center justify-center text-zinc-400 text-sm">
                        {{ count($subjectBreakdown) > 0 ? __('Need at least 3 subjects for radar chart') : __('No data yet') }}
                    </div>
                @endif
            </flux:card>
        </div>

        {{-- Subject-by-Term Breakdown Table --}}
        <flux:card class="p-5">
            <h3 class="text-sm font-semibold mb-4">{{ __('Subject Breakdown by Term') }}</h3>
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
                            <th class="text-center py-2 px-3 font-medium text-zinc-600 dark:text-zinc-400">{{ __('Trend') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($subjectBreakdown as $subject)
                            <tr class="border-b border-zinc-100 dark:border-zinc-800">
                                <td class="py-2 px-3 font-medium" title="{{ $subject['full_name'] }}">{{ $subject['subject_name'] }}</td>
                                @foreach ($subject['terms'] as $termData)
                                    <td class="text-center py-2 px-3">
                                        @if ($termData['average'] !== null)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                                {{ $termData['average'] >= 70 ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' :
                                                   ($termData['average'] >= 50 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' :
                                                   'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400') }}">
                                                {{ $termData['average'] }}%
                                            </span>
                                            @if ($termData['attempted'] < $termData['exam_count'])
                                                <span class="text-xs text-zinc-400 block">{{ $termData['attempted'] }}/{{ $termData['exam_count'] }}</span>
                                            @endif
                                        @else
                                            <span class="text-zinc-400">—</span>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="text-center py-2 px-3">
                                    @if ($subject['overall'] !== null)
                                        <span class="font-bold {{ $subject['overall'] >= 50 ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $subject['overall'] }}%
                                        </span>
                                    @else
                                        <span class="text-zinc-400">—</span>
                                    @endif
                                </td>
                                @if ($gradingItems->isNotEmpty())
                                    <td class="text-center py-2 px-3">
                                        @if ($subject['overall'] !== null)
                                            @php $g = $resolveGrade((float) $subject['overall']); @endphp
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

    @if ($overallStats)
        @push('scripts')
            <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
            <script>
                document.addEventListener('livewire:navigated', initStudentTrendCharts);
                document.addEventListener('DOMContentLoaded', initStudentTrendCharts);

                function initStudentTrendCharts() {
                    // Overall Term Trend (Bar + Line)
                    const trendCanvas = document.getElementById('studentOverallTrendChart');
                    if (trendCanvas) {
                        if (trendCanvas._chartInstance) trendCanvas._chartInstance.destroy();

                        const trend = @json($studentTrend);
                        trendCanvas._chartInstance = new Chart(trendCanvas, {
                            type: 'bar',
                            data: {
                                labels: trend.map(t => t.term_name),
                                datasets: [{
                                    label: '{{ __("Average Score") }}',
                                    data: trend.map(t => t.average),
                                    backgroundColor: trend.map(t => {
                                        if (t.average === null) return '#d1d5db';
                                        return t.average >= 70 ? '#10B98140' : t.average >= 50 ? '#F59E0B40' : '#EF444440';
                                    }),
                                    borderColor: trend.map(t => {
                                        if (t.average === null) return '#d1d5db';
                                        return t.average >= 70 ? '#10B981' : t.average >= 50 ? '#F59E0B' : '#EF4444';
                                    }),
                                    borderWidth: 2,
                                    borderRadius: 6,
                                }],
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    y: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%' } },
                                },
                                plugins: {
                                    legend: { display: false },
                                    tooltip: {
                                        callbacks: {
                                            label: ctx => 'Average: ' + (ctx.parsed.y !== null ? ctx.parsed.y + '%' : 'N/A'),
                                            afterLabel: ctx => {
                                                const d = trend[ctx.dataIndex];
                                                return 'Exams taken: ' + d.exams_taken + '/' + d.exams_available;
                                            },
                                        },
                                    },
                                },
                            },
                        });
                    }

                    // Radar Chart (Subject Strengths)
                    const radarCanvas = document.getElementById('studentRadarChart');
                    if (radarCanvas) {
                        if (radarCanvas._chartInstance) radarCanvas._chartInstance.destroy();

                        const subjects = @json($subjectBreakdown);
                        if (subjects.length >= 3) {
                            radarCanvas._chartInstance = new Chart(radarCanvas, {
                                type: 'radar',
                                data: {
                                    labels: subjects.map(s => s.subject_name),
                                    datasets: [{
                                        label: '{{ __("Overall Average") }}',
                                        data: subjects.map(s => s.overall),
                                        borderColor: '#4F46E5',
                                        backgroundColor: '#4F46E520',
                                        pointBackgroundColor: '#4F46E5',
                                        pointRadius: 4,
                                    }],
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    scales: {
                                        r: {
                                            beginAtZero: true,
                                            max: 100,
                                            ticks: { stepSize: 20, callback: v => v + '%' },
                                        },
                                    },
                                    plugins: {
                                        legend: { display: false },
                                        tooltip: {
                                            callbacks: {
                                                label: ctx => ctx.label + ': ' + (ctx.parsed.r !== null ? ctx.parsed.r + '%' : 'N/A'),
                                            },
                                        },
                                    },
                                },
                            });
                        }
                    }
                }
            </script>
        @endpush
    @endif
</div>
