<x-layouts::app :title="__('Analytics')">
    @include('partials.dashboard-styles')

    <style>
        /* ── Analytics-specific styles ── */
        .analytics-hero {
            background: linear-gradient(135deg, #000c99 0%, #1e40af 50%, #0ea5e9 100%);
            border-radius: 16px;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
        }
        .analytics-hero::before {
            content: '';
            position: absolute;
            top: -40%;
            right: -15%;
            width: 280px;
            height: 280px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.06);
            pointer-events: none;
        }
        .analytics-hero::after {
            content: '';
            position: absolute;
            bottom: -40%;
            left: 10%;
            width: 180px;
            height: 180px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.04);
            pointer-events: none;
        }
        .metric-ring {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        .metric-ring svg {
            transform: rotate(-90deg);
            position: absolute;
            inset: 0;
        }
        .metric-ring circle {
            fill: none;
            stroke-width: 6;
            stroke-linecap: round;
        }
        .metric-ring .ring-bg {
            stroke: #e5e7eb;
        }
        :is(.dark .metric-ring .ring-bg) {
            stroke: #3f3f46;
        }
        .occupancy-bar {
            height: 28px;
            border-radius: 8px;
            overflow: hidden;
            position: relative;
        }
        .occupancy-fill {
            height: 100%;
            border-radius: 8px;
            transition: width 1s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
        }
        .occupancy-fill::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.15) 50%, transparent 100%);
            animation: shimmer 2s infinite;
            background-size: 200% 100%;
        }
        .login-row {
            transition: all 0.2s ease;
        }
        .login-row:hover {
            background: rgba(59, 130, 246, 0.04);
        }
        :is(.dark .login-row:hover) {
            background: rgba(59, 130, 246, 0.06);
        }
        .pg-btn {
            width: 30px; height: 30px;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 8px; font-size: 0.75rem; font-weight: 500;
            color: #71717a; background: transparent; border: none;
            transition: all 0.15s ease; cursor: pointer;
        }
        .pg-btn:hover:not(:disabled):not(.pg-active) { background: #f4f4f5; color: #3f3f46; }
        :is(.dark .pg-btn:hover:not(:disabled):not(.pg-active)) { background: #3f3f46; color: #d4d4d8; }
        :is(.dark .pg-btn) { color: #a1a1aa; }
        .pg-btn:disabled { opacity: 0.35; cursor: not-allowed; }
        .pg-active { background: #000c99 !important; color: white !important; font-weight: 600; }
    </style>

    <div class="space-y-6">
        {{-- ── Header ─────────────────────────────────────────────── --}}
        <div class="analytics-hero dash-animate" role="banner">
            <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <flux:icon.chart-bar-square class="w-5 h-5 text-white/70" />
                        <h1 class="text-xl sm:text-2xl font-bold text-white">{{ __('Analytics') }}</h1>
                    </div>
                    @if ($currentSession && $currentTerm)
                        <p class="text-sm text-white/70">
                            {{ $currentSession->name }} &mdash; {{ $currentTerm->name }}
                        </p>
                    @else
                        <p class="text-sm text-amber-300">{{ __('No active session/term configured.') }}</p>
                    @endif
                </div>

                @include('partials.session-term-filter', [
                    'route' => 'admin.analytics',
                    'allSessions' => $allSessions,
                    'sessionTerms' => $sessionTerms,
                    'currentSession' => $currentSession,
                    'currentTerm' => $currentTerm,
                ])
            </div>
        </div>

        {{-- ── Snapshot Cards ─────────────────────────────────────── --}}
        <section aria-label="{{ __('Snapshot metrics') }}">
            <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-3">
                {{-- Total Students --}}
                <div class="stat-card stat-card-blue dash-animate dash-animate-delay-1">
                    <div class="flex items-center gap-3">
                        <div class="stat-icon bg-blue-500/15">
                            <flux:icon.academic-cap class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 truncate">{{ __('Total Students') }}</p>
                            <p class="stat-value text-zinc-900 dark:text-white">{{ number_format($totalStudents) }}</p>
                        </div>
                    </div>
                    <div class="mt-2 flex gap-3 text-xs text-zinc-500 dark:text-zinc-400">
                        <span>{{ $maleStudents }} {{ __('male') }}</span>
                        <span>&bull;</span>
                        <span>{{ $femaleStudents }} {{ __('female') }}</span>
                    </div>
                </div>

                {{-- Results Progress --}}
                <div class="stat-card stat-card-cyan dash-animate dash-animate-delay-2">
                    <div class="flex items-center gap-3">
                        <div class="stat-icon bg-cyan-500/15">
                            <flux:icon.document-check class="w-5 h-5 text-cyan-600 dark:text-cyan-400" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 truncate">{{ __('Results Uploaded') }}</p>
                            <p class="stat-value text-zinc-900 dark:text-white">{{ $resultsUploaded }}<span class="text-base font-semibold text-zinc-400">/{{ $resultsTotal }}</span></p>
                        </div>
                    </div>
                    @php $resultsPercent = $resultsTotal > 0 ? round(($resultsUploaded / $resultsTotal) * 100) : 0; @endphp
                    <div class="mt-2">
                        <div class="progress-track bg-zinc-200 dark:bg-zinc-700" role="progressbar" aria-valuenow="{{ $resultsPercent }}" aria-valuemin="0" aria-valuemax="100" aria-label="{{ __('Results progress') }}">
                            <div class="progress-fill {{ $resultsPercent >= 80 ? 'bg-emerald-500' : ($resultsPercent >= 50 ? 'bg-amber-500' : 'bg-red-500') }}" style="width: {{ $resultsPercent }}%"></div>
                        </div>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">{{ $resultsPercent }}% {{ __('complete') }}</p>
                    </div>
                </div>

                {{-- Assignments Coverage (summary) --}}
                @php
                    $totalAssignments = $assignmentCoverage->sum('uploaded');
                    $totalWeeks = $assignmentCoverage->sum('total');
                    $overallCoverage = $totalWeeks > 0 ? round(($totalAssignments / $totalWeeks) * 100) : 0;
                @endphp
                <div class="stat-card stat-card-teal dash-animate dash-animate-delay-3 col-span-2 lg:col-span-1">
                    <div class="flex items-center gap-3">
                        <div class="stat-icon bg-teal-500/15">
                            <flux:icon.clipboard-document-list class="w-5 h-5 text-teal-600 dark:text-teal-400" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 truncate">{{ __('Assignment Coverage') }}</p>
                            <p class="stat-value text-zinc-900 dark:text-white">{{ $overallCoverage }}%</p>
                        </div>
                    </div>
                    <div class="mt-2">
                        <div class="progress-track bg-zinc-200 dark:bg-zinc-700" role="progressbar" aria-valuenow="{{ $overallCoverage }}" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-fill {{ $overallCoverage >= 80 ? 'bg-emerald-500' : ($overallCoverage >= 50 ? 'bg-amber-500' : 'bg-red-500') }}" style="width: {{ $overallCoverage }}%"></div>
                        </div>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">{{ $totalAssignments }} / {{ $totalWeeks }} {{ __('class-weeks covered') }}</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- ── Students by Level + Results Progress Detail ─────────── --}}
        <div class="grid gap-4 sm:gap-6 lg:grid-cols-2 dash-animate dash-animate-delay-3">
            {{-- Students by Level --}}
            <div class="dash-panel">
                <div class="dash-panel-header">
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-white flex items-center gap-2">
                        <flux:icon.user-group class="w-4 h-4 text-blue-500" />
                        {{ __('Students by Level') }}
                    </h2>
                    <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ number_format($totalStudents) }} {{ __('total') }}</span>
                </div>
                <div class="dash-panel-body">
                    @if ($levelBreakdown->isNotEmpty())
                        <div class="space-y-4">
                            @foreach ($levelBreakdown as $level)
                                @php $levelPercent = $totalStudents > 0 ? round(($level['count'] / $totalStudents) * 100) : 0; @endphp
                                <div>
                                    <div class="flex items-center justify-between mb-1.5">
                                        <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $level['name'] }}</span>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $levelPercent }}%</span>
                                            <span class="text-sm font-bold text-zinc-900 dark:text-white">{{ number_format($level['count']) }}</span>
                                        </div>
                                    </div>
                                    <div class="occupancy-bar bg-zinc-100 dark:bg-zinc-700/50">
                                        <div class="occupancy-fill bg-gradient-to-r from-blue-500 to-blue-400" style="width: {{ $levelPercent }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="p-6 text-center">
                            <flux:icon.academic-cap class="w-8 h-8 text-zinc-300 dark:text-zinc-600 mx-auto mb-2" />
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No school levels configured yet.') }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Results Upload Progress Detail --}}
            <div class="dash-panel">
                <div class="dash-panel-header">
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-white flex items-center gap-2">
                        <flux:icon.document-check class="w-4 h-4 text-cyan-500" />
                        {{ __('Results Upload Progress') }}
                    </h2>
                </div>
                <div class="dash-panel-body">
                    @if ($currentSession && $currentTerm)
                        <div class="flex items-center gap-6">
                            {{-- Circular progress --}}
                            <div class="metric-ring shrink-0">
                                <svg viewBox="0 0 100 100" width="100" height="100">
                                    <circle class="ring-bg" cx="50" cy="50" r="42" />
                                    <circle class="ring-value" cx="50" cy="50" r="42"
                                        stroke="{{ $resultsPercent >= 80 ? '#10b981' : ($resultsPercent >= 50 ? '#f59e0b' : '#ef4444') }}"
                                        stroke-dasharray="{{ 2 * 3.14159 * 42 }}"
                                        stroke-dashoffset="{{ 2 * 3.14159 * 42 * (1 - $resultsPercent / 100) }}" />
                                </svg>
                                <span class="text-xl font-bold text-zinc-900 dark:text-white relative z-10">{{ $resultsPercent }}%</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $resultsUploaded }} <span class="text-base font-normal text-zinc-500 dark:text-zinc-400">/ {{ $resultsTotal }}</span></p>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">{{ __('students have results') }}</p>
                                <div class="mt-3">
                                    @if ($resultsPercent >= 100)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                                            <flux:icon.check-circle class="w-3.5 h-3.5" /> {{ __('All results uploaded!') }}
                                        </span>
                                    @elseif ($resultsPercent >= 80)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                                            {{ __('Almost complete') }}
                                        </span>
                                    @elseif ($resultsPercent >= 50)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">
                                            {{ __('Good progress') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300">
                                            {{ __('Behind schedule') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="p-6 text-center">
                            <flux:icon.calendar class="w-8 h-8 text-zinc-300 dark:text-zinc-600 mx-auto mb-2" />
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Set an active session and term to track results.') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ── Student Performance Insights ─────────────────────── --}}
        <div class="dash-animate dash-animate-delay-4">
            <livewire:admin.student-insights :session-id="$currentSession?->id" :term-id="$currentTerm?->id" :compact="true" lazy />
        </div>

        {{-- ── Class Occupancy ────────────────────────────────────── --}}
        @if ($classOccupancy->isNotEmpty())
            <div class="dash-panel dash-animate dash-animate-delay-5"
                 x-data="{ open: window.innerWidth >= 768, page: 1, perPage: 6, total: {{ $classOccupancy->count() }}, get pages() { return Math.ceil(this.total / this.perPage) } }"
                 x-init="window.addEventListener('resize', () => { if (window.innerWidth >= 768) open = true })">
                {{-- Mobile: tappable toggle header --}}
                <button @click="open = !open" class="md:hidden flex items-center justify-between w-full px-4 py-3.5 text-left">
                    <div class="flex items-center gap-2">
                        <flux:icon.building-library class="w-4 h-4 text-indigo-500" />
                        <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Class Occupancy') }}</h2>
                        <span class="text-xs bg-zinc-100 dark:bg-zinc-700 text-zinc-500 dark:text-zinc-400 px-2 py-0.5 rounded-full">{{ $classOccupancy->count() }}</span>
                    </div>
                    <span class="inline-flex items-center gap-1 shrink-0 text-xs font-medium text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/20 px-2.5 py-1 rounded-full">
                        <span x-text="open ? '{{ __('Hide') }}' : '{{ __('Show') }}'"></span>
                        <flux:icon.chevron-down class="w-3.5 h-3.5 transition-transform duration-200" ::class="open && 'rotate-180'" />
                    </span>
                </button>
                {{-- Desktop: static header --}}
                <div class="hidden md:flex dash-panel-header">
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-white flex items-center gap-2">
                        <flux:icon.building-library class="w-4 h-4 text-indigo-500" />
                        {{ __('Class Occupancy') }}
                    </h2>
                    <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $classOccupancy->count() }} {{ __('classes with capacity set') }}</span>
                </div>
                <div x-show="open" x-collapse>
                <div class="p-4 sm:p-5">
                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($classOccupancy as $cls)
                            @php
                                $occupancyPercent = $cls->capacity > 0 ? round(($cls->students_count / $cls->capacity) * 100) : 0;
                                $isFull = $occupancyPercent >= 100;
                                $isNearFull = $occupancyPercent >= 85;
                                $barColor = $isFull ? 'from-red-500 to-red-400' : ($isNearFull ? 'from-amber-500 to-amber-400' : 'from-emerald-500 to-emerald-400');
                            @endphp
                            <div x-show="{{ $loop->index }} >= (page - 1) * perPage && {{ $loop->index }} < page * perPage"
                                 class="rounded-xl border border-zinc-100 dark:border-zinc-700/50 p-3.5 hover:border-zinc-200 dark:hover:border-zinc-600 transition-colors">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-zinc-900 dark:text-white truncate">{{ $cls->name }}</p>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $cls->level?->name }}</p>
                                    </div>
                                    <span class="text-sm font-bold shrink-0 {{ $isFull ? 'text-red-600 dark:text-red-400' : ($isNearFull ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400') }}">
                                        {{ $cls->students_count }}/{{ $cls->capacity }}
                                    </span>
                                </div>
                                <div class="occupancy-bar bg-zinc-100 dark:bg-zinc-700/50">
                                    <div class="occupancy-fill bg-gradient-to-r {{ $barColor }}" style="width: {{ min($occupancyPercent, 100) }}%"></div>
                                </div>
                                @if ($isFull)
                                    <p class="text-xs font-medium text-red-600 dark:text-red-400 mt-1.5">{{ __('At capacity') }}</p>
                                @elseif ($isNearFull)
                                    <p class="text-xs text-amber-600 dark:text-amber-400 mt-1.5">{{ $cls->capacity - $cls->students_count }} {{ __('spots left') }}</p>
                                @else
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1.5">{{ $cls->capacity - $cls->students_count }} {{ __('spots available') }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
                <template x-if="pages > 1">
                    <div class="flex items-center justify-between border-t border-zinc-100 dark:border-zinc-700/50 px-4 sm:px-5 py-3">
                        <p class="text-xs text-zinc-500 dark:text-zinc-400" x-text="`{{ __('Showing') }} ${(page - 1) * perPage + 1}–${Math.min(page * perPage, total)} {{ __('of') }} ${total}`"></p>
                        <div class="flex items-center gap-0.5">
                            <button @click="page--" :disabled="page <= 1" class="pg-btn" aria-label="{{ __('Previous page') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="w-3.5 h-3.5"><path fill-rule="evenodd" d="M9.78 4.22a.75.75 0 0 1 0 1.06L7.06 8l2.72 2.72a.75.75 0 1 1-1.06 1.06L5.47 8.53a.75.75 0 0 1 0-1.06l3.25-3.25a.75.75 0 0 1 1.06 0Z" clip-rule="evenodd" /></svg>
                            </button>
                            <template x-for="p in pages" :key="p">
                                <button @click="page = p" :class="page === p ? 'pg-btn pg-active' : 'pg-btn'" x-text="p"></button>
                            </template>
                            <button @click="page++" :disabled="page >= pages" class="pg-btn" aria-label="{{ __('Next page') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="w-3.5 h-3.5"><path fill-rule="evenodd" d="M6.22 4.22a.75.75 0 0 1 1.06 0l3.25 3.25a.75.75 0 0 1 0 1.06l-3.25 3.25a.75.75 0 0 1-1.06-1.06L8.94 8 6.22 5.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" /></svg>
                            </button>
                        </div>
                    </div>
                </template>
                </div>{{-- /x-collapse --}}
            </div>
        @endif

        {{-- ── Assignment Coverage by Class ───────────────────────── --}}
        @if ($assignmentCoverage->isNotEmpty())
            <div class="dash-panel dash-animate dash-animate-delay-6"
                 x-data="{ open: window.innerWidth >= 768 }"
                 x-init="window.addEventListener('resize', () => { if (window.innerWidth >= 768) open = true })">
                {{-- Mobile: tappable toggle header --}}
                <button @click="open = !open" class="md:hidden flex items-center justify-between w-full px-4 py-3.5 text-left">
                    <div class="flex items-center gap-2">
                        <flux:icon.clipboard-document-list class="w-4 h-4 text-teal-500" />
                        <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Assignment Coverage') }}</h2>
                        <span class="text-xs bg-zinc-100 dark:bg-zinc-700 text-zinc-500 dark:text-zinc-400 px-2 py-0.5 rounded-full">{{ $assignmentCoverage->count() }}</span>
                    </div>
                    <span class="inline-flex items-center gap-1 shrink-0 text-xs font-medium text-teal-600 dark:text-teal-400 bg-teal-50 dark:bg-teal-900/20 px-2.5 py-1 rounded-full">
                        <span x-text="open ? '{{ __('Hide') }}' : '{{ __('Show') }}'"></span>
                        <flux:icon.chevron-down class="w-3.5 h-3.5 transition-transform duration-200" ::class="open && 'rotate-180'" />
                    </span>
                </button>
                {{-- Desktop: static header --}}
                <div class="hidden md:flex dash-panel-header">
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-white flex items-center gap-2">
                        <flux:icon.clipboard-document-list class="w-4 h-4 text-teal-500" />
                        {{ __('Assignment Coverage by Class') }}
                    </h2>
                    <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $weeksPerTerm }} {{ __('weeks per term') }}</span>
                </div>
                <div x-show="open" x-collapse>
                <div x-data="{ page: 1, perPage: 5, total: {{ $assignmentCoverage->count() }}, get pages() { return Math.ceil(this.total / this.perPage) } }">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <caption class="sr-only">{{ __('Assignment coverage per class') }}</caption>
                            <thead>
                                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">{{ __('Class') }}</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider hidden sm:table-cell">{{ __('Level') }}</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">{{ __('Progress') }}</th>
                                    <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">{{ __('Coverage') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
                                @foreach ($assignmentCoverage as $cov)
                                    @php $covPercent = $cov['total'] > 0 ? round(($cov['uploaded'] / $cov['total']) * 100) : 0; @endphp
                                    <tr x-show="{{ $loop->index }} >= (page - 1) * perPage && {{ $loop->index }} < page * perPage" class="login-row">
                                        <td class="px-4 py-3 font-medium text-zinc-900 dark:text-white">{{ $cov['name'] }}</td>
                                        <td class="px-4 py-3 text-zinc-500 dark:text-zinc-400 hidden sm:table-cell">{{ $cov['level'] }}</td>
                                        <td class="px-4 py-3">
                                            <div class="w-full max-w-[160px]">
                                                <div class="progress-track bg-zinc-200 dark:bg-zinc-700" role="progressbar" aria-valuenow="{{ $covPercent }}" aria-valuemin="0" aria-valuemax="100">
                                                    <div class="progress-fill {{ $covPercent >= 80 ? 'bg-emerald-500' : ($covPercent >= 50 ? 'bg-amber-500' : 'bg-red-500') }}" style="width: {{ $covPercent }}%"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <span class="font-semibold {{ $covPercent >= 80 ? 'text-emerald-600 dark:text-emerald-400' : ($covPercent >= 50 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400') }}">
                                                {{ $cov['uploaded'] }}/{{ $cov['total'] }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <template x-if="pages > 1">
                        <div class="flex items-center justify-between border-t border-zinc-100 dark:border-zinc-700/50 px-4 py-3">
                            <p class="text-xs text-zinc-500 dark:text-zinc-400" x-text="`{{ __('Showing') }} ${(page - 1) * perPage + 1}–${Math.min(page * perPage, total)} {{ __('of') }} ${total}`"></p>
                            <div class="flex items-center gap-0.5">
                                <button @click="page--" :disabled="page <= 1" class="pg-btn" aria-label="{{ __('Previous page') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="w-3.5 h-3.5"><path fill-rule="evenodd" d="M9.78 4.22a.75.75 0 0 1 0 1.06L7.06 8l2.72 2.72a.75.75 0 1 1-1.06 1.06L5.47 8.53a.75.75 0 0 1 0-1.06l3.25-3.25a.75.75 0 0 1 1.06 0Z" clip-rule="evenodd" /></svg>
                                </button>
                                <template x-for="p in pages" :key="p">
                                    <button @click="page = p" :class="page === p ? 'pg-btn pg-active' : 'pg-btn'" x-text="p"></button>
                                </template>
                                <button @click="page++" :disabled="page >= pages" class="pg-btn" aria-label="{{ __('Next page') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="w-3.5 h-3.5"><path fill-rule="evenodd" d="M6.22 4.22a.75.75 0 0 1 1.06 0l3.25 3.25a.75.75 0 0 1 0 1.06l-3.25 3.25a.75.75 0 0 1-1.06-1.06L8.94 8 6.22 5.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" /></svg>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
                </div>{{-- /x-collapse --}}
            </div>
        @endif

        {{-- ── Recent Staff Logins ────────────────────────────────── --}}
        @if ($recentLogins->isNotEmpty())
            <div class="dash-panel dash-animate dash-animate-delay-7"
                 x-data="{ open: window.innerWidth >= 768 }"
                 x-init="window.addEventListener('resize', () => { if (window.innerWidth >= 768) open = true })">
                {{-- Mobile: tappable toggle header --}}
                <button @click="open = !open" class="md:hidden flex items-center justify-between w-full px-4 py-3.5 text-left">
                    <div class="flex items-center gap-2">
                        <flux:icon.arrow-right-end-on-rectangle class="w-4 h-4 text-purple-500" />
                        <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Staff Logins') }}</h2>
                        <span class="text-xs bg-zinc-100 dark:bg-zinc-700 text-zinc-500 dark:text-zinc-400 px-2 py-0.5 rounded-full">{{ $recentLogins->count() }}</span>
                    </div>
                    <span class="inline-flex items-center gap-1 shrink-0 text-xs font-medium text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/20 px-2.5 py-1 rounded-full">
                        <span x-text="open ? '{{ __('Hide') }}' : '{{ __('Show') }}'"></span>
                        <flux:icon.chevron-down class="w-3.5 h-3.5 transition-transform duration-200" ::class="open && 'rotate-180'" />
                    </span>
                </button>
                {{-- Desktop: static header --}}
                <div class="hidden md:flex dash-panel-header">
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-white flex items-center gap-2">
                        <flux:icon.arrow-right-end-on-rectangle class="w-4 h-4 text-purple-500" />
                        {{ __('Recent Staff Logins') }}
                    </h2>
                    <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Last :count staff', ['count' => $recentLogins->count()]) }}</span>
                </div>
                <div x-show="open" x-collapse>
                <div x-data="{ page: 1, perPage: 5, total: {{ $recentLogins->count() }}, get pages() { return Math.ceil(this.total / this.perPage) } }">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <caption class="sr-only">{{ __('Recent staff login activity') }}</caption>
                            <thead>
                                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">{{ __('Name') }}</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider hidden sm:table-cell">{{ __('Role') }}</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">{{ __('Last Login') }}</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider hidden md:table-cell">{{ __('IP Address') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
                                @foreach ($recentLogins as $login)
                                    <tr x-show="{{ $loop->index }} >= (page - 1) * perPage && {{ $loop->index }} < page * perPage" class="login-row">
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-2.5">
                                                @if ($login->avatar_url)
                                                    <img src="{{ $login->avatar_url }}" alt="" class="w-8 h-8 rounded-full object-cover shrink-0">
                                                @else
                                                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-blue-600 flex items-center justify-center text-white text-xs font-bold shrink-0">
                                                        {{ strtoupper(substr($login->name, 0, 1)) }}
                                                    </div>
                                                @endif
                                                <span class="font-medium text-zinc-900 dark:text-white">{{ $login->name }}</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 hidden sm:table-cell">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                                @if($login->role === 'school_admin') bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300
                                                @elseif($login->role === 'teacher') bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300
                                                @else bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300 @endif">
                                                {{ ucfirst(str_replace('_', ' ', $login->role)) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-zinc-500 dark:text-zinc-400 whitespace-nowrap">{{ $login->last_login_at->diffForHumans() }}</td>
                                        <td class="px-4 py-3 text-zinc-500 dark:text-zinc-400 font-mono text-xs hidden md:table-cell">{{ $login->last_login_ip ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <template x-if="pages > 1">
                        <div class="flex items-center justify-between border-t border-zinc-100 dark:border-zinc-700/50 px-4 py-3">
                            <p class="text-xs text-zinc-500 dark:text-zinc-400" x-text="`{{ __('Showing') }} ${(page - 1) * perPage + 1}–${Math.min(page * perPage, total)} {{ __('of') }} ${total}`"></p>
                            <div class="flex items-center gap-0.5">
                                <button @click="page--" :disabled="page <= 1" class="pg-btn" aria-label="{{ __('Previous page') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="w-3.5 h-3.5"><path fill-rule="evenodd" d="M9.78 4.22a.75.75 0 0 1 0 1.06L7.06 8l2.72 2.72a.75.75 0 1 1-1.06 1.06L5.47 8.53a.75.75 0 0 1 0-1.06l3.25-3.25a.75.75 0 0 1 1.06 0Z" clip-rule="evenodd" /></svg>
                                </button>
                                <template x-for="p in pages" :key="p">
                                    <button @click="page = p" :class="page === p ? 'pg-btn pg-active' : 'pg-btn'" x-text="p"></button>
                                </template>
                                <button @click="page++" :disabled="page >= pages" class="pg-btn" aria-label="{{ __('Next page') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="w-3.5 h-3.5"><path fill-rule="evenodd" d="M6.22 4.22a.75.75 0 0 1 1.06 0l3.25 3.25a.75.75 0 0 1 0 1.06l-3.25 3.25a.75.75 0 0 1-1.06-1.06L8.94 8 6.22 5.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" /></svg>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
                </div>{{-- /x-collapse --}}
            </div>
        @endif

        {{-- ── Back to Dashboard Link ─────────────────────────────── --}}
        <div class="text-center dash-animate dash-animate-delay-7">
            <a href="{{ route('admin.dashboard') }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200 transition-colors">
                <flux:icon.arrow-left class="w-4 h-4" />
                {{ __('Back to Dashboard') }}
            </a>
        </div>
    </div>
</x-layouts::app>
