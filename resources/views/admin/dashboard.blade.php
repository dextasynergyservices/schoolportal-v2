<x-layouts::app :title="__('Admin Dashboard')">
    @include('partials.dashboard-styles')

    <div class="space-y-6">
        {{-- ── Welcome Banner ─────────────────────────────────────── --}}
        <div class="dash-welcome dash-animate" role="banner">
            <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-white">
                        {{ __('Welcome back, :name', ['name' => auth()->user()->name]) }} 👋
                    </h1>
                    @if ($currentSession && $currentTerm)
                        <p class="mt-1 text-sm text-white/70">
                            {{ $currentSession->name }} &mdash; {{ $currentTerm->name }}
                        </p>
                    @else
                        <p class="mt-1 text-sm text-amber-300">
                            {{ __('No active session/term configured.') }}
                        </p>
                    @endif
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('admin.students.create') }}" wire:navigate class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-white/15 hover:bg-white/25 text-white text-sm font-medium transition-colors backdrop-blur-sm border border-white/10">
                        <flux:icon.plus class="w-4 h-4" />
                        {{ __('Add Student') }}
                    </a>
                    <a href="{{ route('admin.results.create') }}" wire:navigate class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-white/15 hover:bg-white/25 text-white text-sm font-medium transition-colors backdrop-blur-sm border border-white/10">
                        <flux:icon.arrow-up-tray class="w-4 h-4" />
                        {{ __('Upload Result') }}
                    </a>
                </div>
            </div>
        </div>

        {{-- ── Alerts ─────────────────────────────────────────────── --}}
        @if ($unassignedTeachers->isNotEmpty())
            <div class="dash-alert dash-alert-amber dash-animate dash-animate-delay-1" role="alert" aria-label="{{ __('Unassigned teachers alert') }}">
                <flux:icon.exclamation-triangle class="w-5 h-5 mt-0.5 shrink-0 text-amber-600 dark:text-amber-400" />
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-amber-800 dark:text-amber-200">
                        {{ trans_choice(':count teacher has no class assigned|:count teachers have no class assigned', $unassignedTeachers->count(), ['count' => $unassignedTeachers->count()]) }}
                    </p>
                    <p class="mt-0.5 text-xs text-amber-700 dark:text-amber-300">
                        {{ $unassignedTeachers->pluck('name')->join(', ', ' & ') }}
                    </p>
                    <a href="{{ route('admin.teachers.index') }}" wire:navigate class="mt-1.5 inline-flex items-center gap-1 text-xs font-semibold text-amber-800 dark:text-amber-200 hover:underline">
                        {{ __('Assign classes') }}
                        <flux:icon.arrow-right class="w-3 h-3" />
                    </a>
                </div>
            </div>
        @endif

        {{-- ── Primary Stats ──────────────────────────────────────── --}}
        <section aria-label="{{ __('School statistics') }}">
            <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                <div class="stat-card stat-card-blue dash-animate dash-animate-delay-1" data-test="stat-students">
                    <div class="flex items-center gap-3">
                        <div class="stat-icon bg-blue-500/15">
                            <flux:icon.academic-cap class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 truncate">{{ __('Students') }}</p>
                            <p class="stat-value text-zinc-900 dark:text-white">{{ number_format($totalStudents) }}</p>
                        </div>
                    </div>
                    <div class="mt-2 flex gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                        <span>{{ $maleStudents }} {{ __('M') }}</span>
                        <span class="text-zinc-300 dark:text-zinc-600">&bull;</span>
                        <span>{{ $femaleStudents }} {{ __('F') }}</span>
                    </div>
                </div>

                <div class="stat-card stat-card-emerald dash-animate dash-animate-delay-2" data-test="stat-teachers">
                    <div class="flex items-center gap-3">
                        <div class="stat-icon bg-emerald-500/15">
                            <flux:icon.user-group class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 truncate">{{ __('Teachers') }}</p>
                            <p class="stat-value text-zinc-900 dark:text-white">{{ number_format($totalTeachers) }}</p>
                        </div>
                    </div>
                    @if ($unassignedTeachers->isNotEmpty())
                        <p class="mt-2 text-xs font-medium text-amber-600 dark:text-amber-400">
                            {{ $unassignedTeachers->count() }} {{ __('unassigned') }}
                        </p>
                    @endif
                </div>

                <div class="stat-card stat-card-purple dash-animate dash-animate-delay-3" data-test="stat-parents">
                    <div class="flex items-center gap-3">
                        <div class="stat-icon bg-purple-500/15">
                            <flux:icon.users class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 truncate">{{ __('Parents') }}</p>
                            <p class="stat-value text-zinc-900 dark:text-white">{{ number_format($totalParents) }}</p>
                        </div>
                    </div>
                </div>

                <div class="stat-card stat-card-amber dash-animate dash-animate-delay-4" data-test="stat-classes">
                    <div class="flex items-center gap-3">
                        <div class="stat-icon bg-amber-500/15">
                            <flux:icon.building-library class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 truncate">{{ __('Classes') }}</p>
                            <p class="stat-value text-zinc-900 dark:text-white">{{ number_format($totalClasses) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ── Term Stats Row ─────────────────────────────────────── --}}
        <section aria-label="{{ __('Term statistics') }}">
            <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                <div class="stat-card stat-card-indigo dash-animate dash-animate-delay-2">
                    <div class="flex items-center gap-3">
                        <div class="stat-icon bg-indigo-500/15">
                            <flux:icon.sparkles class="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 truncate">{{ __('AI Credits') }}</p>
                            <p class="stat-value text-zinc-900 dark:text-white">{{ number_format($aiTotalCredits) }}</p>
                        </div>
                    </div>
                    <div class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                        {{ $aiFreeCredits }} {{ __('free') }} &bull; {{ $aiPurchasedCredits }} {{ __('purchased') }}
                    </div>
                </div>

                <div class="stat-card stat-card-cyan dash-animate dash-animate-delay-3">
                    <div class="flex items-center gap-3">
                        <div class="stat-icon bg-cyan-500/15">
                            <flux:icon.document-check class="w-5 h-5 text-cyan-600 dark:text-cyan-400" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 truncate">{{ __('Results') }}</p>
                            <p class="stat-value text-zinc-900 dark:text-white">{{ number_format($termResultsCount) }}</p>
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('This term') }}</p>
                </div>

                <div class="stat-card stat-card-teal dash-animate dash-animate-delay-4">
                    <div class="flex items-center gap-3">
                        <div class="stat-icon bg-teal-500/15">
                            <flux:icon.clipboard-document-list class="w-5 h-5 text-teal-600 dark:text-teal-400" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 truncate">{{ __('Assignments') }}</p>
                            <p class="stat-value text-zinc-900 dark:text-white">{{ number_format($termAssignmentsCount) }}</p>
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('This term') }}</p>
                </div>

                <div class="stat-card stat-card-pink dash-animate dash-animate-delay-5">
                    <div class="flex items-center gap-3">
                        <div class="stat-icon bg-pink-500/15">
                            <flux:icon.megaphone class="w-5 h-5 text-pink-600 dark:text-pink-400" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 truncate">{{ __('Notices') }}</p>
                            <p class="stat-value text-zinc-900 dark:text-white">{{ number_format($termNoticesCount) }}</p>
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('This term') }}</p>
                </div>
            </div>
        </section>

        {{-- ── Quick Actions ──────────────────────────────────────── --}}
        <section aria-labelledby="quick-actions-heading" class="dash-animate dash-animate-delay-3">
            <h2 id="quick-actions-heading" class="text-sm font-semibold text-zinc-900 dark:text-white mb-3">{{ __('Quick Actions') }}</h2>
            <div class="grid grid-cols-3 gap-2 sm:grid-cols-4 lg:grid-cols-8">
                <a href="{{ route('admin.students.create') }}" wire:navigate class="quick-action">
                    <div class="quick-action-icon bg-blue-100 dark:bg-blue-900/30">
                        <flux:icon.plus class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                    </div>
                    <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300 text-center">{{ __('Student') }}</span>
                </a>
                <a href="{{ route('admin.teachers.create') }}" wire:navigate class="quick-action">
                    <div class="quick-action-icon bg-emerald-100 dark:bg-emerald-900/30">
                        <flux:icon.plus class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                    </div>
                    <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300 text-center">{{ __('Teacher') }}</span>
                </a>
                <a href="{{ route('admin.parents.create') }}" wire:navigate class="quick-action">
                    <div class="quick-action-icon bg-purple-100 dark:bg-purple-900/30">
                        <flux:icon.plus class="w-4 h-4 text-purple-600 dark:text-purple-400" />
                    </div>
                    <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300 text-center">{{ __('Parent') }}</span>
                </a>
                <a href="{{ route('admin.results.create') }}" wire:navigate class="quick-action">
                    <div class="quick-action-icon bg-cyan-100 dark:bg-cyan-900/30">
                        <flux:icon.arrow-up-tray class="w-4 h-4 text-cyan-600 dark:text-cyan-400" />
                    </div>
                    <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300 text-center">{{ __('Result') }}</span>
                </a>
                <a href="{{ route('admin.assignments.create') }}" wire:navigate class="quick-action">
                    <div class="quick-action-icon bg-teal-100 dark:bg-teal-900/30">
                        <flux:icon.clipboard-document-list class="w-4 h-4 text-teal-600 dark:text-teal-400" />
                    </div>
                    <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300 text-center">{{ __('Assignment') }}</span>
                </a>
                <a href="{{ route('admin.notices.create') }}" wire:navigate class="quick-action">
                    <div class="quick-action-icon bg-pink-100 dark:bg-pink-900/30">
                        <flux:icon.megaphone class="w-4 h-4 text-pink-600 dark:text-pink-400" />
                    </div>
                    <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300 text-center">{{ __('Notice') }}</span>
                </a>
                <a href="{{ route('admin.students.import') }}" wire:navigate class="quick-action">
                    <div class="quick-action-icon bg-amber-100 dark:bg-amber-900/30">
                        <flux:icon.arrow-up-on-square-stack class="w-4 h-4 text-amber-600 dark:text-amber-400" />
                    </div>
                    <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300 text-center">{{ __('Import') }}</span>
                </a>
                <a href="{{ route('admin.settings.index') }}" wire:navigate class="quick-action">
                    <div class="quick-action-icon bg-zinc-100 dark:bg-zinc-700">
                        <flux:icon.cog-6-tooth class="w-4 h-4 text-zinc-600 dark:text-zinc-400" />
                    </div>
                    <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300 text-center">{{ __('Settings') }}</span>
                </a>
            </div>
        </section>

        {{-- ── Results Progress + Students by Level ───────────────── --}}
        <div class="grid gap-4 sm:gap-6 lg:grid-cols-2">
            <div class="dash-panel dash-animate dash-animate-delay-3">
                <div class="dash-panel-header">
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Results Upload Progress') }}</h2>
                </div>
                <div class="dash-panel-body">
                    @if ($currentSession && $currentTerm)
                        @php
                            $resultsPercent = $resultsTotal > 0 ? round(($resultsUploaded / $resultsTotal) * 100) : 0;
                        @endphp
                        <div class="flex items-center justify-between text-sm mb-2">
                            <span class="text-zinc-600 dark:text-zinc-400">{{ $resultsUploaded }} / {{ $resultsTotal }} {{ __('students') }}</span>
                            <span class="font-bold text-zinc-900 dark:text-white">{{ $resultsPercent }}%</span>
                        </div>
                        <div class="progress-track bg-zinc-200 dark:bg-zinc-700" role="progressbar" aria-valuenow="{{ $resultsPercent }}" aria-valuemin="0" aria-valuemax="100" aria-label="{{ __('Results upload progress') }}">
                            <div class="progress-fill {{ $resultsPercent >= 80 ? 'bg-emerald-500' : ($resultsPercent >= 50 ? 'bg-amber-500' : 'bg-red-500') }}" style="width: {{ $resultsPercent }}%"></div>
                        </div>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-2">
                            @if ($resultsPercent >= 100) {{ __('All results uploaded! 🎉') }}
                            @elseif ($resultsPercent >= 80) {{ __('Almost there! A few results remaining.') }}
                            @elseif ($resultsPercent >= 50) {{ __('Good progress. Keep uploading results.') }}
                            @else {{ __('Results upload is behind schedule.') }}
                            @endif
                        </p>
                    @else
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Set an active session and term to track results.') }}</p>
                    @endif
                </div>
            </div>

            <div class="dash-panel dash-animate dash-animate-delay-4">
                <div class="dash-panel-header">
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Students by Level') }}</h2>
                </div>
                <div class="dash-panel-body">
                    @if ($levelBreakdown->isNotEmpty())
                        <div class="space-y-3">
                            @foreach ($levelBreakdown as $level)
                                @php $levelPercent = $totalStudents > 0 ? round(($level['count'] / $totalStudents) * 100) : 0; @endphp
                                <div>
                                    <div class="flex items-center justify-between text-sm mb-1">
                                        <span class="text-zinc-700 dark:text-zinc-300 font-medium">{{ $level['name'] }}</span>
                                        <span class="font-bold text-zinc-900 dark:text-white">{{ number_format($level['count']) }}</span>
                                    </div>
                                    <div class="progress-track bg-zinc-200 dark:bg-zinc-700" role="progressbar" aria-valuenow="{{ $levelPercent }}" aria-valuemin="0" aria-valuemax="100">
                                        <div class="progress-fill bg-blue-500 dark:bg-blue-400" style="width: {{ $levelPercent }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No school levels configured yet.') }}</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- ── Class Occupancy + Assignments Coverage ─────────────── --}}
        <div x-data="{ open: window.innerWidth >= 768 }" x-init="window.addEventListener('resize', () => { if (window.innerWidth >= 768) open = true })" class="dash-animate dash-animate-delay-4">
            <button @click="open = !open" class="flex items-center justify-between w-full text-left md:hidden mb-3">
                <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Class Occupancy & Assignments') }}</h2>
                <flux:icon.chevron-down class="w-4 h-4 text-zinc-500 transition-transform" ::class="open ? 'rotate-180' : ''" />
            </button>
            <div x-show="open" x-collapse>
                <div class="grid gap-4 sm:gap-6 lg:grid-cols-2">
            @if ($classOccupancy->isNotEmpty())
                <div class="dash-panel dash-animate dash-animate-delay-4">
                    <div class="dash-panel-header">
                        <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Class Occupancy') }}</h2>
                    </div>
                    <div class="divide-y divide-zinc-100 dark:divide-zinc-700/50 max-h-80 overflow-y-auto">
                        @foreach ($classOccupancy as $cls)
                            @php
                                $occupancyPercent = $cls->capacity > 0 ? round(($cls->students_count / $cls->capacity) * 100) : 0;
                                $isFull = $occupancyPercent >= 100;
                                $isNearFull = $occupancyPercent >= 85;
                            @endphp
                            <div class="activity-item">
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-zinc-900 dark:text-white truncate">{{ $cls->name }}</p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $cls->level?->name }}</p>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <div class="w-20 progress-track bg-zinc-200 dark:bg-zinc-700" role="progressbar" aria-valuenow="{{ min($occupancyPercent, 100) }}" aria-valuemin="0" aria-valuemax="100">
                                        <div class="progress-fill {{ $isFull ? 'bg-red-500' : ($isNearFull ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ min($occupancyPercent, 100) }}%"></div>
                                    </div>
                                    <span class="text-xs font-semibold w-14 text-right {{ $isFull ? 'text-red-600 dark:text-red-400' : ($isNearFull ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-600 dark:text-zinc-400') }}">
                                        {{ $cls->students_count }}/{{ $cls->capacity }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($assignmentCoverage->isNotEmpty())
                <div class="dash-panel dash-animate dash-animate-delay-5">
                    <div class="dash-panel-header">
                        <div>
                            <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Assignment Coverage') }}</h2>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">{{ __(':total weeks per term', ['total' => $weeksPerTerm]) }}</p>
                        </div>
                    </div>
                    <div class="divide-y divide-zinc-100 dark:divide-zinc-700/50 max-h-80 overflow-y-auto">
                        @foreach ($assignmentCoverage as $cov)
                            @php $covPercent = $cov['total'] > 0 ? round(($cov['uploaded'] / $cov['total']) * 100) : 0; @endphp
                            <div class="activity-item">
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-zinc-900 dark:text-white truncate">{{ $cov['name'] }}</p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $cov['level'] }}</p>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <div class="w-20 progress-track bg-zinc-200 dark:bg-zinc-700" role="progressbar" aria-valuenow="{{ $covPercent }}" aria-valuemin="0" aria-valuemax="100">
                                        <div class="progress-fill {{ $covPercent >= 80 ? 'bg-emerald-500' : ($covPercent >= 50 ? 'bg-amber-500' : 'bg-red-500') }}" style="width: {{ $covPercent }}%"></div>
                                    </div>
                                    <span class="text-xs font-semibold w-10 text-right text-zinc-600 dark:text-zinc-400">
                                        {{ $cov['uploaded'] }}/{{ $cov['total'] }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
            </div>
        </div>

        {{-- ── Pending Approvals + Recent Activity ────────────────── --}}
        <div x-data="{ open: window.innerWidth >= 768 }" x-init="window.addEventListener('resize', () => { if (window.innerWidth >= 768) open = true })" class="dash-animate dash-animate-delay-5">
            <button @click="open = !open" class="flex items-center justify-between w-full text-left md:hidden mb-3">
                <div class="flex items-center gap-2">
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Approvals & Activity') }}</h2>
                    @if ($pendingCount > 0)
                        <span class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-amber-500 rounded-full">{{ $pendingCount }}</span>
                    @endif
                </div>
                <flux:icon.chevron-down class="w-4 h-4 text-zinc-500 transition-transform" ::class="open ? 'rotate-180' : ''" />
            </button>
            <div x-show="open" x-collapse>
        <div class="grid gap-4 sm:gap-6 lg:grid-cols-2">
            <div class="dash-panel dash-animate dash-animate-delay-5">
                <div class="dash-panel-header">
                    <div class="flex items-center gap-2">
                        <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Pending Approvals') }}</h2>
                        @if ($pendingCount > 0)
                            <span class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-amber-500 rounded-full badge-pulse">{{ $pendingCount }}</span>
                        @endif
                    </div>
                    @if ($pendingCount > 0)
                        <a href="{{ route('admin.approvals.index') }}" wire:navigate class="text-xs font-medium text-blue-600 dark:text-blue-400 hover:underline">{{ __('View all') }}</a>
                    @endif
                </div>
                <div class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
                    @forelse ($pendingApprovals as $action)
                        <div class="activity-item">
                            <div class="activity-dot
                                @if ($action->entity_type === 'result') bg-blue-100 dark:bg-blue-900/30
                                @elseif ($action->entity_type === 'assignment') bg-emerald-100 dark:bg-emerald-900/30
                                @elseif ($action->entity_type === 'notice') bg-purple-100 dark:bg-purple-900/30
                                @else bg-zinc-100 dark:bg-zinc-700 @endif">
                                @if ($action->entity_type === 'result')
                                    <flux:icon.document-text class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                                @elseif ($action->entity_type === 'assignment')
                                    <flux:icon.clipboard-document-list class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                                @elseif ($action->entity_type === 'notice')
                                    <flux:icon.megaphone class="w-4 h-4 text-purple-600 dark:text-purple-400" />
                                @else
                                    <flux:icon.clock class="w-4 h-4 text-zinc-500" />
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm text-zinc-700 dark:text-zinc-300">
                                    <span class="font-semibold text-zinc-900 dark:text-white">{{ $action->teacher->name }}</span>
                                    {{ __('submitted a :type', ['type' => $action->entity_type]) }}
                                </p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $action->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center">
                            <div class="w-12 h-12 mx-auto rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center mb-3">
                                <flux:icon.check-circle class="w-6 h-6 text-emerald-500" />
                            </div>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No pending approvals') }}</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="dash-panel dash-animate dash-animate-delay-6">
                <div class="dash-panel-header">
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Recent Activity') }}</h2>
                    <a href="{{ route('admin.audit-logs.index') }}" wire:navigate class="text-xs font-medium text-blue-600 dark:text-blue-400 hover:underline">{{ __('View all') }}</a>
                </div>
                <div class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
                    @forelse ($recentActivity as $log)
                        <div class="activity-item">
                            <div class="activity-dot bg-zinc-100 dark:bg-zinc-700">
                                <flux:icon.arrow-path class="w-4 h-4 text-zinc-500 dark:text-zinc-400" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm text-zinc-700 dark:text-zinc-300">
                                    @if ($log->user)
                                        <span class="font-semibold text-zinc-900 dark:text-white">{{ $log->user->name }}</span>
                                    @else
                                        <span class="font-medium text-zinc-500">{{ __('System') }}</span>
                                    @endif
                                    {{ str_replace('.', ' ', $log->action) }}
                                </p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $log->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center">
                            <div class="w-12 h-12 mx-auto rounded-full bg-zinc-100 dark:bg-zinc-700 flex items-center justify-center mb-3">
                                <flux:icon.clock class="w-6 h-6 text-zinc-400" />
                            </div>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No recent activity') }}</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
            </div>
        </div>

        {{-- ── Recent Logins ──────────────────────────────────────── --}}
        @if ($recentLogins->isNotEmpty())
            <div x-data="{ open: window.innerWidth >= 768 }" x-init="window.addEventListener('resize', () => { if (window.innerWidth >= 768) open = true })" class="dash-animate dash-animate-delay-6">
                <button @click="open = !open" class="flex items-center justify-between w-full text-left md:hidden mb-3">
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Recent Staff Logins') }}</h2>
                    <flux:icon.chevron-down class="w-4 h-4 text-zinc-500 transition-transform" ::class="open ? 'rotate-180' : ''" />
                </button>
                <div x-show="open" x-collapse>
            <div class="dash-panel">
                <div class="dash-panel-header hidden md:flex">
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Recent Staff Logins') }}</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <caption class="sr-only">{{ __('Recent staff login activity') }}</caption>
                        <thead>
                            <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">{{ __('Name') }}</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider hidden sm:table-cell">{{ __('Role') }}</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">{{ __('Time') }}</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider hidden md:table-cell">{{ __('IP') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
                            @foreach ($recentLogins as $login)
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-7 h-7 rounded-full bg-[#000c99] flex items-center justify-center text-white text-xs font-bold shrink-0">
                                                {{ strtoupper(substr($login->name, 0, 1)) }}
                                            </div>
                                            <span class="font-medium text-zinc-900 dark:text-white">{{ $login->name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 hidden sm:table-cell">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                            @if($login->role === 'school_admin') bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300
                                            @elseif($login->role === 'teacher') bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300
                                            @else bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300 @endif">
                                            {{ str_replace('_', ' ', $login->role) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-zinc-500 dark:text-zinc-400 whitespace-nowrap">{{ $login->last_login_at->diffForHumans() }}</td>
                                    <td class="px-4 py-3 text-zinc-500 dark:text-zinc-400 font-mono text-xs hidden md:table-cell">{{ $login->last_login_ip ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
                </div>
            </div>
        @endif
    </div>
</x-layouts::app>
