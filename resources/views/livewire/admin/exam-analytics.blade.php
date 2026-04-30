<div>
    {{-- Summary Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
        <flux:card class="p-4">
            <span class="text-xs text-zinc-500 block">{{ __('Total Attempts') }}</span>
            <span class="text-2xl font-bold">{{ $stats['total_attempts'] }}</span>
        </flux:card>
        <flux:card class="p-4">
            <span class="text-xs text-zinc-500 block">{{ __('Average Score') }}</span>
            <span class="text-2xl font-bold">{{ $stats['average_score'] !== null ? $stats['average_score'] . '%' : '—' }}</span>
        </flux:card>
        <flux:card class="p-4">
            <span class="text-xs text-zinc-500 block">{{ __('Median Score') }}</span>
            <span class="text-2xl font-bold">{{ $stats['median_score'] !== null ? $stats['median_score'] . '%' : '—' }}</span>
        </flux:card>
        <flux:card class="p-4">
            <span class="text-xs text-zinc-500 block">{{ __('Pass Rate') }}</span>
            <span class="text-2xl font-bold {{ ($stats['pass_rate'] ?? 0) >= 50 ? 'text-green-600' : 'text-red-600' }}">
                {{ $stats['pass_rate'] !== null ? $stats['pass_rate'] . '%' : '—' }}
            </span>
        </flux:card>
        <flux:card class="p-4">
            <span class="text-xs text-zinc-500 block">{{ __('Pass / Fail') }}</span>
            <span class="text-2xl font-bold">
                <span class="text-green-600">{{ $stats['pass_count'] }}</span>
                /
                <span class="text-red-600">{{ $stats['fail_count'] }}</span>
            </span>
        </flux:card>
        <flux:card class="p-4">
            <span class="text-xs text-zinc-500 block">{{ __('Avg Time') }}</span>
            <span class="text-2xl font-bold">
                @if ($stats['avg_time'])
                    {{ gmdate('i:s', (int) $stats['avg_time']) }}
                @else
                    —
                @endif
            </span>
        </flux:card>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- Score Distribution Chart --}}
        <flux:card class="p-5">
            <h3 class="text-sm font-semibold mb-4">{{ __('Score Distribution') }}</h3>
            @if ($stats['total_attempts'] > 0)
                <div class="h-64" wire:ignore>
                    <canvas id="scoreDistributionChart"></canvas>
                </div>
            @else
                <div class="h-64 flex items-center justify-center text-zinc-400 text-sm">
                    {{ __('No data yet') }}
                </div>
            @endif
        </flux:card>

        {{-- Pass/Fail Doughnut --}}
        <flux:card class="p-5">
            <h3 class="text-sm font-semibold mb-4">{{ __('Pass vs Fail') }}</h3>
            @if ($stats['pass_count'] + $stats['fail_count'] > 0)
                <div class="h-64 flex items-center justify-center" wire:ignore>
                    <canvas id="passFailChart" class="max-w-[250px]"></canvas>
                </div>
            @else
                <div class="h-64 flex items-center justify-center text-zinc-400 text-sm">
                    {{ __('No graded attempts yet') }}
                </div>
            @endif
        </flux:card>
    </div>

    {{-- Hardest Questions --}}
    <flux:card class="p-5 mb-6">
        <h3 class="text-sm font-semibold mb-4">{{ __('Question Difficulty Analysis') }}</h3>
        @if ($hardestQuestions->isEmpty())
            <div class="p-8 text-center text-zinc-400 text-sm">{{ __('No data yet') }}</div>
        @else
            <div class="space-y-3">
                @foreach ($hardestQuestions->take(10) as $i => $q)
                    <div class="flex items-center gap-3">
                        <span class="text-xs font-mono text-zinc-400 w-6 shrink-0">Q{{ $i + 1 }}</span>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm truncate" title="{{ strip_tags($q->question_text) }}">{{ Str::limit(strip_tags($q->question_text), 80) }}</div>
                            <div class="flex items-center gap-2 mt-1">
                                <div class="flex-1 h-2 bg-zinc-200 dark:bg-zinc-700 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full transition-all {{ $q->correct_rate >= 70 ? 'bg-green-500' : ($q->correct_rate >= 40 ? 'bg-amber-500' : 'bg-red-500') }}" style="width: {{ $q->correct_rate }}%"></div>
                                </div>
                                <span class="text-xs text-zinc-500 w-10 text-right">{{ $q->correct_rate }}%</span>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <flux:badge :color="$q->correct_rate >= 70 ? 'green' : ($q->correct_rate >= 40 ? 'amber' : 'red')" size="sm">
                                {{ $q->correct_count }}/{{ $q->total_answered }}
                            </flux:badge>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="flex items-center gap-4 mt-4 pt-3 border-t text-xs text-zinc-500">
                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-green-500"></span> {{ __('Easy (≥70%)') }}</span>
                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> {{ __('Medium (40-69%)') }}</span>
                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-red-500"></span> {{ __('Hard (<40%)') }}</span>
            </div>
        @endif
    </flux:card>

    {{-- Student Scores Table --}}
    <flux:card class="p-5">
        <h3 class="text-sm font-semibold mb-4">{{ __('Individual Student Scores') }}</h3>
        @if ($studentResults->isEmpty())
            <div class="p-8 text-center text-zinc-400 text-sm">{{ __('No graded attempts yet') }}</div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-zinc-500">
                            <th class="p-3 font-medium w-8">#</th>
                            <th class="p-3 font-medium">{{ __('Student') }}</th>
                            <th class="p-3 font-medium">{{ __('Score') }}</th>
                            <th class="p-3 font-medium">{{ __('Percentage') }}</th>
                            <th class="p-3 font-medium">{{ __('Result') }}</th>
                            <th class="p-3 font-medium hidden sm:table-cell">{{ __('Time') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($studentResults as $rank => $attempt)
                            <tr class="border-b hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <td class="p-3 font-mono text-zinc-400">{{ $rank + 1 }}</td>
                                <td class="p-3">
                                    <div class="font-medium">{{ $attempt->student?->name }}</div>
                                    <div class="text-xs text-zinc-400">{{ $attempt->student?->username }}</div>
                                </td>
                                <td class="p-3 font-mono">{{ $attempt->score }}/{{ $attempt->total_points }}</td>
                                <td class="p-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-16 h-1.5 bg-zinc-200 dark:bg-zinc-700 rounded-full overflow-hidden">
                                            <div class="h-full {{ $attempt->passed ? 'bg-green-500' : 'bg-red-500' }} rounded-full" style="width: {{ $attempt->percentage }}%"></div>
                                        </div>
                                        <span class="font-mono text-sm">{{ $attempt->percentage }}%</span>
                                    </div>
                                </td>
                                <td class="p-3">
                                    @if ($attempt->passed)
                                        <flux:badge color="green" size="sm">{{ __('Passed') }}</flux:badge>
                                    @else
                                        <flux:badge color="red" size="sm">{{ __('Failed') }}</flux:badge>
                                    @endif
                                </td>
                                <td class="p-3 hidden sm:table-cell text-xs text-zinc-500">
                                    @if ($attempt->time_spent_seconds)
                                        {{ gmdate('H:i:s', $attempt->time_spent_seconds) }}
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </flux:card>

    @if ($stats['total_attempts'] > 0)
        @push('scripts')
            <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const isDark = document.documentElement.classList.contains('dark');
                    const textColor = isDark ? '#a1a1aa' : '#71717a';
                    const gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';

                    // Score Distribution Bar Chart
                    const distCtx = document.getElementById('scoreDistributionChart');
                    if (distCtx) {
                        new Chart(distCtx, {
                            type: 'bar',
                            data: {
                                labels: {!! json_encode(array_keys($distribution)) !!},
                                datasets: [{
                                    label: '{{ __("Students") }}',
                                    data: {!! json_encode(array_values($distribution)) !!},
                                    backgroundColor: [
                                        '#ef4444', '#ef4444', '#f97316', '#f97316', '#f59e0b',
                                        '#eab308', '#84cc16', '#22c55e', '#10b981', '#059669'
                                    ],
                                    borderRadius: 4,
                                    borderSkipped: false,
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { display: false },
                                    tooltip: {
                                        callbacks: {
                                            label: (ctx) => ctx.parsed.y + ' ' + (ctx.parsed.y === 1 ? 'student' : 'students')
                                        }
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: { stepSize: 1, color: textColor },
                                        grid: { color: gridColor }
                                    },
                                    x: {
                                        ticks: { color: textColor, font: { size: 10 } },
                                        grid: { display: false }
                                    }
                                }
                            }
                        });
                    }

                    // Pass/Fail Doughnut
                    const pfCtx = document.getElementById('passFailChart');
                    if (pfCtx) {
                        new Chart(pfCtx, {
                            type: 'doughnut',
                            data: {
                                labels: ['{{ __("Passed") }}', '{{ __("Failed") }}'],
                                datasets: [{
                                    data: [{{ $stats['pass_count'] }}, {{ $stats['fail_count'] }}],
                                    backgroundColor: ['#22c55e', '#ef4444'],
                                    borderWidth: 0,
                                    hoverOffset: 8,
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                cutout: '60%',
                                plugins: {
                                    legend: {
                                        position: 'bottom',
                                        labels: { color: textColor, padding: 16 }
                                    }
                                }
                            }
                        });
                    }
                });
            </script>
        @endpush
    @endif
</div>
