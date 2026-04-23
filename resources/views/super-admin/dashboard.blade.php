<x-layouts::app :title="__('Platform Dashboard')">
    @php
        $hour = (int) now()->format('G');
        $timeGreeting = match (true) {
            $hour >= 5 && $hour < 12  => __('Good morning'),
            $hour >= 12 && $hour < 17 => __('Good afternoon'),
            default                    => __('Good evening'),
        };
    @endphp
    @include('partials.dashboard-styles')

    <div class="space-y-6">
        {{-- ── Welcome Banner ─────────────────────────────────────── --}}
        <div class="dash-welcome dash-welcome-super dash-animate" role="banner">
            <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-white">
                        {{ __('Platform Overview') }}
                    </h1>
                    <p class="mt-1 text-sm text-white/70">
                        {{ $timeGreeting }}, {{ auth()->user()->name }}
                    </p>
                </div>
                <a href="{{ route('super-admin.schools.create') }}" wire:navigate class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-white/15 hover:bg-white/25 text-white text-sm font-medium transition-colors backdrop-blur-sm border border-white/10">
                    <flux:icon.plus class="w-4 h-4" />
                    {{ __('New School') }}
                </a>
            </div>
        </div>

        {{-- ── Growth This Month ──────────────────────────────────── --}}
        @if ($newSchoolsThisMonth > 0 || $newStudentsThisMonth > 0 || $newTeachersThisMonth > 0)
            <div class="dash-alert dash-alert-blue dash-animate dash-animate-delay-1" role="status" aria-label="{{ __('Growth this month') }}">
                <flux:icon.arrow-trending-up class="w-5 h-5 mt-0.5 shrink-0 text-blue-600 dark:text-blue-400" />
                <div class="flex flex-wrap gap-x-4 gap-y-1 text-sm">
                    <span class="font-semibold text-blue-800 dark:text-blue-200">{{ __('This month:') }}</span>
                    @if ($newSchoolsThisMonth > 0)
                        <span class="text-blue-700 dark:text-blue-300">+{{ $newSchoolsThisMonth }} {{ trans_choice('school|schools', $newSchoolsThisMonth) }}</span>
                    @endif
                    @if ($newStudentsThisMonth > 0)
                        <span class="text-blue-700 dark:text-blue-300">+{{ $newStudentsThisMonth }} {{ trans_choice('student|students', $newStudentsThisMonth) }}</span>
                    @endif
                    @if ($newTeachersThisMonth > 0)
                        <span class="text-blue-700 dark:text-blue-300">+{{ $newTeachersThisMonth }} {{ trans_choice('teacher|teachers', $newTeachersThisMonth) }}</span>
                    @endif
                </div>
            </div>
        @endif

        {{-- ── Primary Stats ──────────────────────────────────────── --}}
        <section aria-label="{{ __('Platform statistics') }}">
            <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                <div class="stat-card stat-card-blue dash-animate dash-animate-delay-1">
                    <div class="flex items-center gap-3">
                        <div class="stat-icon bg-blue-500/15">
                            <flux:icon.building-office-2 class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 truncate">{{ __('Schools') }}</p>
                            <p class="stat-value text-zinc-900 dark:text-white">{{ number_format($totalSchools) }}</p>
                        </div>
                    </div>
                    <div class="mt-2 flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                        <span>{{ $activeSchools }} {{ __('active') }}</span>
                        <span class="text-zinc-300 dark:text-zinc-600">&bull;</span>
                        <span>{{ $inactiveSchools }} {{ __('inactive') }}</span>
                        @if ($newSchoolsThisMonth > 0)
                            <span class="text-emerald-600 dark:text-emerald-400 font-medium">+{{ $newSchoolsThisMonth }}</span>
                        @endif
                    </div>
                </div>

                <div class="stat-card stat-card-emerald dash-animate dash-animate-delay-2">
                    <div class="flex items-center gap-3">
                        <div class="stat-icon bg-emerald-500/15">
                            <flux:icon.academic-cap class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 truncate">{{ __('Students') }}</p>
                            <p class="stat-value text-zinc-900 dark:text-white">{{ number_format($totalStudents) }}</p>
                        </div>
                    </div>
                    @if ($newStudentsThisMonth > 0)
                        <p class="mt-2 text-xs text-emerald-600 dark:text-emerald-400 font-medium">
                            +{{ $newStudentsThisMonth }} {{ __('this month') }}
                        </p>
                    @endif
                </div>

                <div class="stat-card stat-card-purple dash-animate dash-animate-delay-3">
                    <div class="flex items-center gap-3">
                        <div class="stat-icon bg-purple-500/15">
                            <flux:icon.user-group class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 truncate">{{ __('Staff & Parents') }}</p>
                            <p class="stat-value text-zinc-900 dark:text-white">{{ number_format($totalTeachers + $totalParents + $totalAdmins) }}</p>
                        </div>
                    </div>
                    <div class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                        {{ $totalAdmins }} {{ __('admins') }} &bull; {{ $totalTeachers }} {{ __('teachers') }} &bull; {{ $totalParents }} {{ __('parents') }}
                    </div>
                </div>

                <div class="stat-card stat-card-amber dash-animate dash-animate-delay-4">
                    <div class="flex items-center gap-3">
                        <div class="stat-icon bg-amber-500/15">
                            <flux:icon.banknotes class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 truncate">{{ __('Revenue') }}</p>
                            <p class="stat-value text-zinc-900 dark:text-white">₦{{ number_format((float) $totalRevenue) }}</p>
                        </div>
                    </div>
                    <div class="mt-2 flex items-center gap-1.5 text-xs text-zinc-500 dark:text-zinc-400">
                        <span>₦{{ number_format((float) $monthlyRevenue) }} {{ __('this month') }}</span>
                        @if ($revenueChangePercent != 0)
                            <span class="font-medium {{ $revenueChangePercent > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $revenueChangePercent > 0 ? '+' : '' }}{{ $revenueChangePercent }}%
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        {{-- ── Secondary Stats ────────────────────────────────────── --}}
        <section aria-label="{{ __('Secondary statistics') }}">
            <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                <div class="stat-card stat-card-indigo dash-animate dash-animate-delay-2">
                    <div class="flex items-center gap-3">
                        <div class="stat-icon bg-indigo-500/15">
                            <flux:icon.sparkles class="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 truncate">{{ __('AI Credits Used') }}</p>
                            <p class="stat-value text-zinc-900 dark:text-white">{{ number_format($creditsUsedThisMonth) }}</p>
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('This month') }}</p>
                </div>

                <div class="stat-card stat-card-cyan dash-animate dash-animate-delay-3">
                    <div class="flex items-center gap-3">
                        <div class="stat-icon bg-cyan-500/15">
                            <flux:icon.shopping-cart class="w-5 h-5 text-cyan-600 dark:text-cyan-400" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 truncate">{{ __('Credits Purchased') }}</p>
                            <p class="stat-value text-zinc-900 dark:text-white">{{ number_format($totalPurchasedCredits) }}</p>
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Lifetime total') }}</p>
                </div>

                @if ($pendingPayments > 0)
                    <div class="stat-card stat-card-rose dash-animate dash-animate-delay-4">
                        <div class="flex items-center gap-3">
                            <div class="stat-icon bg-rose-500/15">
                                <flux:icon.exclamation-triangle class="w-5 h-5 text-rose-600 dark:text-rose-400" />
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 truncate">{{ __('Pending Payments') }}</p>
                                <p class="stat-value text-rose-600 dark:text-rose-400">{{ $pendingPayments }}</p>
                            </div>
                        </div>
                        <p class="mt-2 text-xs text-rose-600 dark:text-rose-400 font-medium">{{ __('Awaiting verification') }}</p>
                    </div>
                @else
                    <div class="stat-card stat-card-emerald dash-animate dash-animate-delay-4">
                        <div class="flex items-center gap-3">
                            <div class="stat-icon bg-emerald-500/15">
                                <flux:icon.check-badge class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 truncate">{{ __('Payments') }}</p>
                                <p class="stat-value text-emerald-600 dark:text-emerald-400">{{ __('Clear') }}</p>
                            </div>
                        </div>
                        <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('No pending payments') }}</p>
                    </div>
                @endif

                <div class="stat-card stat-card-teal dash-animate dash-animate-delay-5">
                    <div class="flex items-center gap-3">
                        <div class="stat-icon bg-teal-500/15">
                            <flux:icon.finger-print class="w-5 h-5 text-teal-600 dark:text-teal-400" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 truncate">{{ __('Logins') }}</p>
                            <p class="stat-value text-zinc-900 dark:text-white">{{ number_format($loginsThisMonth) }}</p>
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ number_format($loginsThisWeek) }} {{ __('this week') }}</p>
                </div>
            </div>
        </section>

        {{-- ── Health Alerts ──────────────────────────────────────── --}}
        @if ($healthAlerts->isNotEmpty())
            <div class="dash-panel dash-animate dash-animate-delay-3 !border-amber-300 dark:!border-amber-700">
                <div class="dash-panel-header !border-amber-300 dark:!border-amber-700 !bg-amber-50 dark:!bg-amber-950/40">
                    <div class="flex items-center gap-2">
                        <flux:icon.exclamation-triangle class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                        <h2 class="text-sm font-semibold text-amber-800 dark:text-amber-200">
                            {{ __('School Health Alerts') }}
                        </h2>
                        <span class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-amber-500 rounded-full">{{ $healthAlerts->count() }}</span>
                    </div>
                </div>
                <div class="divide-y divide-amber-100 dark:divide-amber-900/30">
                    @foreach ($healthAlerts->take(8) as $alert)
                        <div class="activity-item">
                            <div class="activity-dot bg-amber-100 dark:bg-amber-900/30">
                                @if ($alert['type'] === 'no_session')
                                    <flux:icon.calendar-days class="w-4 h-4 text-amber-600 dark:text-amber-400" />
                                @elseif ($alert['type'] === 'no_login')
                                    <flux:icon.clock class="w-4 h-4 text-amber-600 dark:text-amber-400" />
                                @else
                                    <flux:icon.user-minus class="w-4 h-4 text-amber-600 dark:text-amber-400" />
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-zinc-900 dark:text-white truncate">{{ $alert['school']->name }}</p>
                                <p class="text-xs text-amber-700 dark:text-amber-400">{{ $alert['message'] }}</p>
                            </div>
                            <a href="{{ route('super-admin.schools.show', $alert['school']) }}" wire:navigate class="text-xs font-medium text-blue-600 dark:text-blue-400 hover:underline shrink-0">{{ __('View') }}</a>
                        </div>
                    @endforeach
                </div>
                @if ($healthAlerts->count() > 8)
                    <div class="px-4 py-2 text-center border-t border-amber-100 dark:border-amber-900/30">
                        <span class="text-xs text-amber-700 dark:text-amber-400">{{ __('And :count more...', ['count' => $healthAlerts->count() - 8]) }}</span>
                    </div>
                @endif
            </div>
        @endif

        {{-- ── Quick Actions ──────────────────────────────────────── --}}
        <section aria-labelledby="sa-quick-actions-heading" class="dash-animate dash-animate-delay-3">
            <h2 id="sa-quick-actions-heading" class="text-sm font-semibold text-zinc-900 dark:text-white mb-3">{{ __('Quick Actions') }}</h2>
            <div class="grid grid-cols-3 gap-2 sm:grid-cols-3 lg:grid-cols-6">
                <a href="{{ route('super-admin.schools.create') }}" wire:navigate class="quick-action">
                    <div class="quick-action-icon bg-blue-100 dark:bg-blue-900/30">
                        <flux:icon.building-office-2 class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                    </div>
                    <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300 text-center">{{ __('New School') }}</span>
                </a>
                <a href="{{ route('super-admin.students.index') }}" wire:navigate class="quick-action">
                    <div class="quick-action-icon bg-emerald-100 dark:bg-emerald-900/30">
                        <flux:icon.academic-cap class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                    </div>
                    <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300 text-center">{{ __('Students') }}</span>
                </a>
                <a href="{{ route('super-admin.teachers.index') }}" wire:navigate class="quick-action">
                    <div class="quick-action-icon bg-purple-100 dark:bg-purple-900/30">
                        <flux:icon.user-group class="w-4 h-4 text-purple-600 dark:text-purple-400" />
                    </div>
                    <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300 text-center">{{ __('Teachers') }}</span>
                </a>
                <a href="{{ route('super-admin.parents.index') }}" wire:navigate class="quick-action">
                    <div class="quick-action-icon bg-pink-100 dark:bg-pink-900/30">
                        <flux:icon.users class="w-4 h-4 text-pink-600 dark:text-pink-400" />
                    </div>
                    <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300 text-center">{{ __('Parents') }}</span>
                </a>
                <a href="{{ route('super-admin.credits.index') }}" wire:navigate class="quick-action">
                    <div class="quick-action-icon bg-indigo-100 dark:bg-indigo-900/30">
                        <flux:icon.sparkles class="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
                    </div>
                    <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300 text-center">{{ __('AI Credits') }}</span>
                </a>
                <a href="{{ route('super-admin.schools.index') }}" wire:navigate class="quick-action">
                    <div class="quick-action-icon bg-zinc-100 dark:bg-zinc-700">
                        <flux:icon.cog-6-tooth class="w-4 h-4 text-zinc-600 dark:text-zinc-400" />
                    </div>
                    <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300 text-center">{{ __('All Schools') }}</span>
                </a>
            </div>
        </section>

        {{-- ── Top Schools + Recent Schools ───────────────────────── --}}
        <div class="grid gap-4 sm:gap-6 lg:grid-cols-2">
            <div class="dash-panel dash-animate dash-animate-delay-4">
                <div class="dash-panel-header">
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Top Schools') }}</h2>
                    <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('By student count') }}</span>
                </div>
                @if ($topSchools->isEmpty())
                    <div class="p-8 text-center">
                        <div class="w-12 h-12 mx-auto rounded-full bg-zinc-100 dark:bg-zinc-700 flex items-center justify-center mb-3">
                            <flux:icon.building-office-2 class="w-6 h-6 text-zinc-400" />
                        </div>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No schools yet.') }}</p>
                    </div>
                @else
                    <div class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
                        @foreach ($topSchools as $index => $school)
                            <a href="{{ route('super-admin.schools.show', $school) }}" wire:navigate class="activity-item group">
                                <span class="flex w-7 h-7 shrink-0 items-center justify-center rounded-full text-xs font-bold
                                    {{ $index === 0 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' :
                                       ($index === 1 ? 'bg-zinc-200 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-400' :
                                       ($index === 2 ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400' : 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400')) }}">
                                    {{ $index + 1 }}
                                </span>
                                <span class="min-w-0 flex-1 truncate text-sm font-medium text-zinc-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">{{ $school->name }}</span>
                                <span class="shrink-0 text-sm tabular-nums font-semibold text-zinc-600 dark:text-zinc-400">{{ number_format($school->student_count) }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="dash-panel dash-animate dash-animate-delay-5">
                <div class="dash-panel-header">
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Recent Schools') }}</h2>
                    <a href="{{ route('super-admin.schools.index') }}" wire:navigate class="text-xs font-medium text-blue-600 dark:text-blue-400 hover:underline">{{ __('View all') }}</a>
                </div>
                @if ($recentSchools->isEmpty())
                    <div class="p-8 text-center">
                        <div class="w-12 h-12 mx-auto rounded-full bg-zinc-100 dark:bg-zinc-700 flex items-center justify-center mb-3">
                            <flux:icon.plus class="w-6 h-6 text-zinc-400" />
                        </div>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No schools yet. Create your first school to get started.') }}</p>
                    </div>
                @else
                    <div class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
                        @foreach ($recentSchools as $school)
                            <a href="{{ route('super-admin.schools.show', $school) }}" wire:navigate class="activity-item group">
                                <div class="activity-dot bg-blue-100 dark:bg-blue-900/30">
                                    <flux:icon.building-office-2 class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-zinc-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors truncate">{{ $school->name }}</p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $school->custom_domain ?? $school->email }} &bull; {{ number_format($school->student_count) }} {{ __('students') }} &bull; {{ $school->created_at?->diffForHumans() ?? '' }}</p>
                                </div>
                                @if ($school->is_active)
                                    <span class="shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">{{ __('Active') }}</span>
                                @else
                                    <span class="shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-400">{{ __('Inactive') }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- ── Recent Purchases + Recent Activity ─────────────────── --}}
        <div class="grid gap-4 sm:gap-6 lg:grid-cols-2">
            <div class="dash-panel dash-animate dash-animate-delay-5">
                <div class="dash-panel-header">
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Recent Purchases') }}</h2>
                    <a href="{{ route('super-admin.credits.index') }}" wire:navigate class="text-xs font-medium text-blue-600 dark:text-blue-400 hover:underline">{{ __('View all') }}</a>
                </div>
                @if ($recentPurchases->isEmpty())
                    <div class="p-8 text-center">
                        <div class="w-12 h-12 mx-auto rounded-full bg-zinc-100 dark:bg-zinc-700 flex items-center justify-center mb-3">
                            <flux:icon.shopping-cart class="w-6 h-6 text-zinc-400" />
                        </div>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No credit purchases yet.') }}</p>
                    </div>
                @else
                    <div class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
                        @foreach ($recentPurchases as $purchase)
                            <div class="activity-item">
                                @if ($purchase->purchaser?->avatar_url)
                                    <img src="{{ $purchase->purchaser->avatar_url }}" alt="" class="w-9 h-9 rounded-full object-cover shrink-0">
                                @else
                                    <div class="w-9 h-9 rounded-full bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center text-white text-xs font-bold shrink-0">
                                        {{ $purchase->purchaser ? strtoupper(substr($purchase->purchaser->name, 0, 1)) : '?' }}
                                    </div>
                                @endif
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-zinc-900 dark:text-white truncate">{{ $purchase->school?->name ?? __('Unknown School') }}</p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $purchase->credits }} {{ __('credits') }} &bull; {{ $purchase->purchaser?->name ?? __('Unknown') }} &bull; {{ $purchase->created_at?->diffForHumans() ?? '' }}</p>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <span class="text-sm font-bold tabular-nums text-zinc-900 dark:text-white">₦{{ number_format((float) $purchase->amount_naira) }}</span>
                                    @if ($purchase->status === 'completed')
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">{{ __('Paid') }}</span>
                                    @elseif ($purchase->status === 'pending')
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">{{ __('Pending') }}</span>
                                    @else
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">{{ __('Failed') }}</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="dash-panel dash-animate dash-animate-delay-6">
                <div class="dash-panel-header">
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Recent Platform Activity') }}</h2>
                </div>
                @if ($recentActivity->isEmpty())
                    <div class="p-8 text-center">
                        <div class="w-12 h-12 mx-auto rounded-full bg-zinc-100 dark:bg-zinc-700 flex items-center justify-center mb-3">
                            <flux:icon.clock class="w-6 h-6 text-zinc-400" />
                        </div>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No activity recorded yet.') }}</p>
                    </div>
                @else
                    <div class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
                        @foreach ($recentActivity as $log)
                            <div class="activity-item">
                                @if ($log->user?->avatar_url)
                                    <img src="{{ $log->user->avatar_url }}" alt="" class="w-9 h-9 rounded-full object-cover shrink-0">
                                @elseif ($log->user)
                                    <div class="w-9 h-9 rounded-full bg-gradient-to-br from-zinc-500 to-zinc-600 ring-1 ring-zinc-300 dark:ring-zinc-500 flex items-center justify-center text-white text-xs font-bold shrink-0">
                                        {{ strtoupper(substr($log->user->name, 0, 1)) }}
                                    </div>
                                @else
                                    <div class="activity-dot bg-zinc-100 dark:bg-zinc-700">
                                        <flux:icon.cog-6-tooth class="w-4 h-4 text-zinc-500 dark:text-zinc-400" />
                                    </div>
                                @endif
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm text-zinc-700 dark:text-zinc-300">
                                        <span class="font-semibold text-zinc-900 dark:text-white">{{ $log->user?->name ?? __('System') }}</span>
                                        {{ str_replace('.', ' ', $log->action) }}
                                    </p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $log->school?->name ?? '' }}{{ $log->school ? ' · ' : '' }}{{ $log->created_at?->diffForHumans() ?? '' }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts::app>
