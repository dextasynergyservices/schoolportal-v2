<x-layouts::app :title="__('AI Analytics')">

    @include('partials.dashboard-styles')

    <style>
        /* ── Credits banner ── */
        .credits-banner {
            background: linear-gradient(135deg, #4c1d95 0%, #5b21b6 40%, #2d1b69 100%);
            border-radius: 20px;
            padding: 1.75rem 2rem;
            position: relative;
            overflow: hidden;
        }
        .credits-banner::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse 60% 80% at 85% 40%, rgba(167,139,250,0.25) 0%, transparent 60%),
                        radial-gradient(ellipse 50% 60% at 5% 90%, rgba(196,181,253,0.1) 0%, transparent 55%);
            pointer-events: none;
        }
        .credits-banner::after {
            content: '';
            position: absolute;
            top: -80px; right: -50px;
            width: 280px; height: 280px;
            border-radius: 50%;
            background: rgba(139,92,246,0.15);
            pointer-events: none;
        }

        /* ── KPI cards ── */
        .kpi-card {
            background: white;
            border: 1px solid #e4e4e7;
            border-radius: 16px;
            padding: 1.3rem 1.4rem;
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .kpi-card:hover { box-shadow: 0 6px 22px rgba(0,0,0,0.07); transform: translateY(-2px); }
        :is(.dark .kpi-card) { background: #27272a; border-color: #3f3f46; }

        .kpi-icon {
            width: 42px; height: 42px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .kpi-value {
            font-size: 1.9rem;
            font-weight: 800;
            letter-spacing: -0.04em;
            line-height: 1;
        }

        /* ── Chart panels ── */
        .chart-panel {
            background: white;
            border: 1px solid #e4e4e7;
            border-radius: 18px;
            overflow: hidden;
            transition: box-shadow 0.2s;
        }
        .chart-panel:hover { box-shadow: 0 8px 28px rgba(0,0,0,0.06); }
        :is(.dark .chart-panel) { background: #27272a; border-color: #3f3f46; }
        .chart-panel-header {
            padding: 1.1rem 1.5rem 0.9rem;
            border-bottom: 1px solid #f4f4f5;
            display: flex; align-items: center; justify-content: space-between;
        }
        :is(.dark .chart-panel-header) { border-bottom-color: #3f3f46; }
        .chart-canvas-wrap { padding: 1rem 1.25rem 0.75rem; height: 260px; position: relative; }

        /* ── Top schools table ── */
        .usage-bar-bg {
            height: 6px;
            border-radius: 99px;
            background: #f4f4f5;
            overflow: hidden;
        }
        :is(.dark .usage-bar-bg) { background: #3f3f46; }
        .usage-bar-fill {
            height: 100%;
            border-radius: 99px;
            background: linear-gradient(90deg, #7c3aed, #a78bfa);
        }
    </style>

    {{-- Chart.js CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.1/dist/chart.umd.min.js"></script>

    <div class="space-y-6">

        {{-- ── Banner ─────────────────────────────────────────────── --}}
        <div class="credits-banner dash-animate" role="banner">
            <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2.5 mb-1">
                        <div class="flex items-center justify-center w-9 h-9 rounded-xl bg-white/15 backdrop-blur-sm">
                            <flux:icon.sparkles class="w-5 h-5 text-white" />
                        </div>
                        <h1 class="text-xl sm:text-2xl font-bold text-white tracking-tight">
                            {{ __('AI Usage Analytics') }}
                        </h1>
                    </div>
                    <p class="text-sm text-white/55 ml-11">
                        {{ __('Platform-wide breakdown of AI content generation (quizzes, games, exams, assessments & assignments) across all schools.') }}
                    </p>
                </div>
            </div>

            {{-- Sub-nav --}}
            <div class="relative z-10 mt-5">
                @include('partials.credits-subnav')
            </div>
        </div>

        {{-- ── Overview KPIs ───────────────────────────────────────── --}}
        <div class="grid grid-cols-3 gap-3 dash-animate dash-animate-delay-1">

            <div class="kpi-card">
                <div class="flex items-center gap-3 mb-3">
                    <div class="kpi-icon bg-violet-50 dark:bg-violet-950/40">
                        <flux:icon.bolt class="w-5 h-5 text-violet-600 dark:text-violet-400" />
                    </div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">{{ __('Total Used') }}</p>
                </div>
                <p class="kpi-value text-zinc-900 dark:text-white">{{ number_format($totalUsed) }}</p>
                <p class="text-xs text-zinc-400 mt-1">{{ __('all time') }}</p>
            </div>

            <div class="kpi-card">
                <div class="flex items-center gap-3 mb-3">
                    <div class="kpi-icon bg-indigo-50 dark:bg-indigo-950/40">
                        <flux:icon.calendar-days class="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                    </div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">{{ __('This Month') }}</p>
                </div>
                <p class="kpi-value text-zinc-900 dark:text-white">{{ number_format($thisMonthUsed) }}</p>
                <p class="text-xs text-zinc-400 mt-1">{{ now()->format('M Y') }}</p>
            </div>

            <div class="kpi-card">
                <div class="flex items-center gap-3 mb-3">
                    <div class="kpi-icon bg-emerald-50 dark:bg-emerald-950/40">
                        <flux:icon.building-office-2 class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                    </div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">{{ __('Schools Using AI') }}</p>
                </div>
                <p class="kpi-value text-zinc-900 dark:text-white">{{ number_format($totalSchoolsUsing) }}</p>
                <p class="text-xs text-zinc-400 mt-1">{{ __('with activity') }}</p>
            </div>

        </div>

        {{-- ── By Content Type ─────────────────────────────────────── --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 dash-animate dash-animate-delay-1">

            <div class="kpi-card">
                <div class="flex items-center gap-3 mb-3">
                    <div class="kpi-icon bg-indigo-50 dark:bg-indigo-950/40">
                        <flux:icon.clipboard-document-list class="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                    </div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">{{ __('Quizzes') }}</p>
                </div>
                <p class="kpi-value text-zinc-900 dark:text-white">{{ number_format($quizTotal) }}</p>
                <p class="text-xs text-zinc-400 mt-1">{{ $totalUsed > 0 ? round($quizTotal / $totalUsed * 100) : 0 }}% {{ __('of usage') }}</p>
            </div>

            <div class="kpi-card">
                <div class="flex items-center gap-3 mb-3">
                    <div class="kpi-icon bg-fuchsia-50 dark:bg-fuchsia-950/40">
                        <flux:icon.puzzle-piece class="w-5 h-5 text-fuchsia-600 dark:text-fuchsia-400" />
                    </div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">{{ __('Games') }}</p>
                </div>
                <p class="kpi-value text-zinc-900 dark:text-white">{{ number_format($gameTotal) }}</p>
                <p class="text-xs text-zinc-400 mt-1">{{ $totalUsed > 0 ? round($gameTotal / $totalUsed * 100) : 0 }}% {{ __('of usage') }}</p>
            </div>

            <div class="kpi-card">
                <div class="flex items-center gap-3 mb-3">
                    <div class="kpi-icon bg-rose-50 dark:bg-rose-950/40">
                        <flux:icon.academic-cap class="w-5 h-5 text-rose-600 dark:text-rose-400" />
                    </div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">{{ __('Exams') }}</p>
                </div>
                <p class="kpi-value text-zinc-900 dark:text-white">{{ number_format($examTotal) }}</p>
                <p class="text-xs text-zinc-400 mt-1">{{ $totalUsed > 0 ? round($examTotal / $totalUsed * 100) : 0 }}% {{ __('of usage') }}</p>
            </div>

            <div class="kpi-card">
                <div class="flex items-center gap-3 mb-3">
                    <div class="kpi-icon bg-amber-50 dark:bg-amber-950/40">
                        <flux:icon.chart-bar class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                    </div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">{{ __('Assessments') }}</p>
                </div>
                <p class="kpi-value text-zinc-900 dark:text-white">{{ number_format($assessmentTotal) }}</p>
                <p class="text-xs text-zinc-400 mt-1">{{ $totalUsed > 0 ? round($assessmentTotal / $totalUsed * 100) : 0 }}% {{ __('of usage') }}</p>
            </div>

            <div class="kpi-card col-span-2 sm:col-span-1">
                <div class="flex items-center gap-3 mb-3">
                    <div class="kpi-icon bg-teal-50 dark:bg-teal-950/40">
                        <flux:icon.document-text class="w-5 h-5 text-teal-600 dark:text-teal-400" />
                    </div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">{{ __('Assignments') }}</p>
                </div>
                <p class="kpi-value text-zinc-900 dark:text-white">{{ number_format($assignmentTotal) }}</p>
                <p class="text-xs text-zinc-400 mt-1">{{ $totalUsed > 0 ? round($assignmentTotal / $totalUsed * 100) : 0 }}% {{ __('of usage') }}</p>
            </div>

        </div>

        {{-- ── Charts row ────────────────────────────────────────── --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 dash-animate dash-animate-delay-2">

            {{-- Monthly trend (takes 2/3 width) --}}
            <div class="chart-panel lg:col-span-2">
                <div class="chart-panel-header">
                    <div>
                        <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">{{ __('Monthly AI Usage') }}</p>
                        <p class="text-xs text-zinc-400 mt-0.5">{{ __('AI credits used by content type over last 12 months') }}</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-zinc-500">
                        <span class="flex items-center gap-1.5"><span class="inline-block w-3 h-3 rounded-sm" style="background:#6366f1"></span>{{ __('Quiz') }}</span>
                        <span class="flex items-center gap-1.5"><span class="inline-block w-3 h-3 rounded-sm" style="background:#a855f7"></span>{{ __('Game') }}</span>
                        <span class="flex items-center gap-1.5"><span class="inline-block w-3 h-3 rounded-sm" style="background:#f43f5e"></span>{{ __('Exam') }}</span>
                        <span class="flex items-center gap-1.5"><span class="inline-block w-3 h-3 rounded-sm" style="background:#f59e0b"></span>{{ __('Assessment') }}</span>
                        <span class="flex items-center gap-1.5"><span class="inline-block w-3 h-3 rounded-sm" style="background:#14b8a6"></span>{{ __('Assignment') }}</span>
                    </div>
                </div>
                <div class="chart-canvas-wrap">
                    <canvas id="chart-monthly-trend"></canvas>
                </div>
            </div>

            {{-- Usage split donut (takes 1/3 width) --}}
            <div class="chart-panel">
                <div class="chart-panel-header">
                    <div>
                        <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">{{ __('Usage Split by Type') }}</p>
                        <p class="text-xs text-zinc-400 mt-0.5">{{ __('All-time usage distribution') }}</p>
                    </div>
                </div>
                <div class="flex items-center justify-center py-4 px-4">
                    <div style="height: 200px; width: 200px; position: relative;">
                        <canvas id="chart-split-donut"></canvas>
                    </div>
                </div>
                <div class="px-5 pb-4 space-y-1.5">
                    <div class="flex items-center justify-between text-sm">
                        <span class="flex items-center gap-2 text-zinc-600 dark:text-zinc-400"><span class="w-2.5 h-2.5 rounded-full" style="background:#6366f1"></span>{{ __('Quizzes') }}</span>
                        <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ number_format($quizTotal) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="flex items-center gap-2 text-zinc-600 dark:text-zinc-400"><span class="w-2.5 h-2.5 rounded-full" style="background:#a855f7"></span>{{ __('Games') }}</span>
                        <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ number_format($gameTotal) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="flex items-center gap-2 text-zinc-600 dark:text-zinc-400"><span class="w-2.5 h-2.5 rounded-full" style="background:#f43f5e"></span>{{ __('Exams') }}</span>
                        <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ number_format($examTotal) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="flex items-center gap-2 text-zinc-600 dark:text-zinc-400"><span class="w-2.5 h-2.5 rounded-full" style="background:#f59e0b"></span>{{ __('Assessments') }}</span>
                        <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ number_format($assessmentTotal) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="flex items-center gap-2 text-zinc-600 dark:text-zinc-400"><span class="w-2.5 h-2.5 rounded-full" style="background:#14b8a6"></span>{{ __('Assignments') }}</span>
                        <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ number_format($assignmentTotal) }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Top schools table ────────────────────────────────── --}}
        <div class="chart-panel dash-animate dash-animate-delay-3">
            <div class="chart-panel-header">
                <div>
                    <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">{{ __('Top Schools by AI Usage') }}</p>
                    <p class="text-xs text-zinc-400 mt-0.5">{{ __('Most active schools using AI generation, all time') }}</p>
                </div>
                <flux:badge color="violet" size="sm">{{ __('Top :n', ['n' => $topSchools->count()]) }}</flux:badge>
            </div>

            @if ($topSchools->isEmpty())
                <div class="px-6 py-8 text-center text-sm text-zinc-400">
                    {{ __('No AI usage recorded yet.') }}
                </div>
            @else
                @php $maxUsed = $topSchools->max('total_used') ?: 1; @endphp
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[500px] text-sm">
                        <thead>
                            <tr class="border-b border-zinc-100 dark:border-zinc-800 bg-zinc-50/60 dark:bg-zinc-800/40">
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-400 w-10">#</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-400">{{ __('School') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-400 hidden md:table-cell">{{ __('Type Breakdown') }}</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-400">{{ __('Total') }}</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-400 hidden lg:table-cell">{{ __('Last used') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($topSchools as $i => $entry)
                                <tr class="border-b border-zinc-50 dark:border-zinc-800/60 last:border-0 hover:bg-violet-50/30 dark:hover:bg-violet-950/10 transition-colors">
                                    <td class="px-5 py-3.5">
                                        @if ($i === 0)
                                            <span class="text-base">🥇</span>
                                        @elseif ($i === 1)
                                            <span class="text-base">🥈</span>
                                        @elseif ($i === 2)
                                            <span class="text-base">🥉</span>
                                        @else
                                            <span class="text-sm font-medium text-zinc-400">{{ $i + 1 }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5">
                                        <p class="font-medium text-zinc-800 dark:text-zinc-200">{{ $entry->school_name }}</p>
                                        @if ($entry->last_used_at)
                                            <p class="text-xs text-zinc-400 mt-0.5">
                                                {{ \Carbon\Carbon::parse($entry->last_used_at)->diffForHumans() }}
                                            </p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 hidden md:table-cell">
                                        @php $pct = (int) round($entry->total_used / $maxUsed * 100); @endphp
                                        <div class="usage-bar-bg w-full max-w-[160px] mb-1.5">
                                            <div class="usage-bar-fill" style="width: {{ $pct }}%"></div>
                                        </div>
                                        <div class="flex flex-wrap gap-x-2 gap-y-0.5">
                                            @if ($entry->quiz_used)       <span class="text-xs font-medium text-indigo-600 dark:text-indigo-400">Qz {{ number_format($entry->quiz_used) }}</span>       @endif
                                            @if ($entry->game_used)       <span class="text-xs font-medium text-fuchsia-600 dark:text-fuchsia-400">Gm {{ number_format($entry->game_used) }}</span>       @endif
                                            @if ($entry->exam_used)       <span class="text-xs font-medium text-rose-600 dark:text-rose-400">Ex {{ number_format($entry->exam_used) }}</span>           @endif
                                            @if ($entry->assessment_used) <span class="text-xs font-medium text-amber-600 dark:text-amber-400">Asmt {{ number_format($entry->assessment_used) }}</span> @endif
                                            @if ($entry->assignment_used) <span class="text-xs font-medium text-teal-600 dark:text-teal-400">Asgn {{ number_format($entry->assignment_used) }}</span>  @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3.5 text-right">
                                        <span class="text-sm font-bold text-zinc-800 dark:text-zinc-200">{{ number_format($entry->total_used) }}</span>
                                    </td>
                                    <td class="px-4 py-3.5 text-right hidden lg:table-cell">
                                        @if ($entry->last_used_at)
                                            <span class="text-xs text-zinc-400">
                                                {{ \Carbon\Carbon::parse($entry->last_used_at)->format('d M Y') }}
                                            </span>
                                        @else
                                            <span class="text-xs text-zinc-300 dark:text-zinc-600">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

    </div>

    {{-- ── Charts initialisation ─────────────────────────────────── --}}
    <script>
    (function () {
        const isDark = () => document.documentElement.classList.contains('dark');

        const gridColor = () => isDark() ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
        const labelColor = () => isDark() ? '#a1a1aa' : '#71717a';

        const trendLabels     = @json($trendLabels);
        const trendQuiz       = @json($trendQuiz);
        const trendGame       = @json($trendGame);
        const trendExam       = @json($trendExam);
        const trendAssessment = @json($trendAssessment);
        const trendAssignment = @json($trendAssignment);
        const quizTotal       = {{ $quizTotal }};
        const gameTotal       = {{ $gameTotal }};
        const examTotal       = {{ $examTotal }};
        const assessmentTotal = {{ $assessmentTotal }};
        const assignmentTotal = {{ $assignmentTotal }};

        function initCharts() {
            // Monthly trend — stacked bar
            const trendCtx = document.getElementById('chart-monthly-trend');
            if (trendCtx) {
                const existing = Chart.getChart(trendCtx);
                if (existing) existing.destroy();

                new Chart(trendCtx, {
                    type: 'bar',
                    data: {
                        labels: trendLabels,
                        datasets: [
                            {
                                label: 'Quiz',
                                data: trendQuiz,
                                backgroundColor: 'rgba(99,102,241,0.85)',
                                borderRadius: { topLeft: 4, topRight: 4, bottomLeft: 0, bottomRight: 0 },
                                borderSkipped: false,
                            },
                            {
                                label: 'Game',
                                data: trendGame,
                                backgroundColor: 'rgba(168,85,247,0.8)',
                                borderRadius: { topLeft: 4, topRight: 4, bottomLeft: 0, bottomRight: 0 },
                                borderSkipped: false,
                            },
                            {
                                label: 'Exam',
                                data: trendExam,
                                backgroundColor: 'rgba(244,63,94,0.85)',
                                borderRadius: { topLeft: 4, topRight: 4, bottomLeft: 0, bottomRight: 0 },
                                borderSkipped: false,
                            },
                            {
                                label: 'Assessment',
                                data: trendAssessment,
                                backgroundColor: 'rgba(245,158,11,0.85)',
                                borderRadius: { topLeft: 4, topRight: 4, bottomLeft: 0, bottomRight: 0 },
                                borderSkipped: false,
                            },
                            {
                                label: 'Assignment',
                                data: trendAssignment,
                                backgroundColor: 'rgba(20,184,166,0.85)',
                                borderRadius: { topLeft: 4, topRight: 4, bottomLeft: 0, bottomRight: 0 },
                                borderSkipped: false,
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: ctx => ' ' + ctx.dataset.label + ': ' + ctx.parsed.y + ' credits',
                                },
                            },
                        },
                        scales: {
                            x: {
                                stacked: true,
                                grid: { display: false },
                                ticks: { color: labelColor(), font: { size: 11 }, maxRotation: 40, minRotation: 30 },
                            },
                            y: {
                                stacked: true,
                                grid: { color: gridColor(), lineWidth: 1 },
                                ticks: { color: labelColor(), font: { size: 11 }, precision: 0 },
                                beginAtZero: true,
                            },
                        },
                    },
                });
            }

            // Donut — quiz vs game
            const donutCtx = document.getElementById('chart-split-donut');
            if (donutCtx) {
                const existing = Chart.getChart(donutCtx);
                if (existing) existing.destroy();

                new Chart(donutCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Quiz', 'Game', 'Exam', 'Assessment', 'Assignment'],
                        datasets: [{
                            data: [quizTotal, gameTotal, examTotal, assessmentTotal, assignmentTotal],
                            backgroundColor: [
                                'rgba(99,102,241,0.9)',
                                'rgba(168,85,247,0.85)',
                                'rgba(244,63,94,0.9)',
                                'rgba(245,158,11,0.9)',
                                'rgba(20,184,166,0.9)',
                            ],
                            borderColor: isDark() ? '#27272a' : '#ffffff',
                            borderWidth: 3,
                            hoverOffset: 6,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '68%',
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: ctx => ' ' + ctx.label + ': ' + ctx.parsed + ' credits',
                                },
                            },
                        },
                    },
                });
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initCharts);
        } else {
            initCharts();
        }

        // Re-init after wire:navigate
        document.addEventListener('livewire:navigated', function () {
            if (document.getElementById('chart-monthly-trend')) {
                initCharts();
            }
        });
    })();
    </script>

</x-layouts::app>
