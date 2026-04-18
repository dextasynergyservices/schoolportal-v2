<x-layouts::app :title="__('Student Dashboard')">
    @include('partials.dashboard-styles')

    <div class="space-y-6">
        {{-- ── Welcome Banner ─────────────────────────────────────── --}}
        <div class="dash-welcome dash-animate" role="banner">
            <div class="relative z-10">
                <h1 class="text-xl sm:text-2xl font-bold text-white">
                    {{ __('Welcome back, :name', ['name' => $student->name]) }} 👋
                </h1>
                @if ($currentSession && $currentTerm)
                    <p class="mt-1 text-sm text-white/70">
                        {{ $currentSession->name }} &mdash; {{ $currentTerm->name }}
                    </p>
                @else
                    <p class="mt-1 text-sm text-amber-300">
                        {{ __('No active session/term set.') }}
                    </p>
                @endif
            </div>
        </div>

        {{-- ── Class Info Card ────────────────────────────────────── --}}
        @if ($class)
            <div class="dash-panel dash-animate dash-animate-delay-1" style="padding: 0;">
                <div class="p-4 flex items-center gap-4">
                    <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-blue-100 dark:bg-blue-900/30 shrink-0">
                        <flux:icon.building-library class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="font-bold text-zinc-900 dark:text-white">{{ $class->name }}</p>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $class->level?->name }}
                            @if ($class->teacher)
                                &mdash; {{ __('Class Teacher: :name', ['name' => $class->teacher->name]) }}
                            @endif
                        </p>
                    </div>
                    @if ($profile?->admission_number)
                        <span class="hidden sm:inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300">
                            {{ $profile->admission_number }}
                        </span>
                    @endif
                </div>
            </div>
        @else
            <div class="dash-alert dash-alert-amber dash-animate dash-animate-delay-1" role="alert">
                <flux:icon.exclamation-triangle class="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0" />
                <p class="text-sm text-amber-700 dark:text-amber-300">
                    {{ __('You have not been assigned to a class yet. Please contact your school admin.') }}
                </p>
            </div>
        @endif

        {{-- ── Stats Cards ────────────────────────────────────────── --}}
        <section aria-label="{{ __('Your statistics') }}">
            <div class="grid grid-cols-3 gap-3 sm:gap-4 lg:grid-cols-5">
                <a href="{{ route('student.results.index') }}" wire:navigate class="stat-card stat-card-emerald dash-animate dash-animate-delay-1 block">
                    <div class="flex items-center gap-2.5">
                        <div class="stat-icon bg-emerald-500/15">
                            <flux:icon.document-text class="w-4 h-4 sm:w-5 sm:h-5 text-emerald-600 dark:text-emerald-400" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] sm:text-xs font-medium text-zinc-500 dark:text-zinc-400 truncate">{{ __('Results') }}</p>
                            <p class="stat-value text-zinc-900 dark:text-white">{{ number_format($resultsCount) }}</p>
                        </div>
                    </div>
                </a>

                <a href="{{ route('student.assignments.index') }}" wire:navigate class="stat-card stat-card-purple dash-animate dash-animate-delay-2 block">
                    <div class="flex items-center gap-2.5">
                        <div class="stat-icon bg-purple-500/15">
                            <flux:icon.clipboard-document-list class="w-4 h-4 sm:w-5 sm:h-5 text-purple-600 dark:text-purple-400" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] sm:text-xs font-medium text-zinc-500 dark:text-zinc-400 truncate">{{ __('Assignments') }}</p>
                            <p class="stat-value text-zinc-900 dark:text-white">{{ number_format($assignmentsCount) }}</p>
                        </div>
                    </div>
                </a>

                <a href="{{ route('student.notices.index') }}" wire:navigate class="stat-card stat-card-cyan dash-animate dash-animate-delay-3 block">
                    <div class="flex items-center gap-2.5">
                        <div class="stat-icon bg-cyan-500/15">
                            <flux:icon.megaphone class="w-4 h-4 sm:w-5 sm:h-5 text-cyan-600 dark:text-cyan-400" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] sm:text-xs font-medium text-zinc-500 dark:text-zinc-400 truncate">{{ __('Notices') }}</p>
                            <p class="stat-value text-zinc-900 dark:text-white">{{ number_format($noticesCount) }}</p>
                        </div>
                    </div>
                </a>

                <a href="{{ route('student.quizzes.index') }}" wire:navigate class="stat-card stat-card-amber dash-animate dash-animate-delay-4 block">
                    <div class="flex items-center gap-2.5">
                        <div class="stat-icon bg-amber-500/15">
                            <flux:icon.academic-cap class="w-4 h-4 sm:w-5 sm:h-5 text-amber-600 dark:text-amber-400" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] sm:text-xs font-medium text-zinc-500 dark:text-zinc-400 truncate">{{ __('Quizzes') }}</p>
                            <p class="stat-value text-zinc-900 dark:text-white">{{ number_format($availableQuizzes) }}</p>
                        </div>
                    </div>
                </a>

                <a href="{{ route('student.games.index') }}" wire:navigate class="stat-card stat-card-pink dash-animate dash-animate-delay-5 block col-span-3 sm:col-span-1 lg:col-span-1">
                    <div class="flex items-center gap-2.5">
                        <div class="stat-icon bg-pink-500/15">
                            <flux:icon.puzzle-piece class="w-4 h-4 sm:w-5 sm:h-5 text-pink-600 dark:text-pink-400" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] sm:text-xs font-medium text-zinc-500 dark:text-zinc-400 truncate">{{ __('Games') }}</p>
                            <p class="stat-value text-zinc-900 dark:text-white">{{ number_format($availableGames) }}</p>
                        </div>
                    </div>
                </a>
            </div>
        </section>

        {{-- ── Quiz Performance Summary ───────────────────────────── --}}
        @if ($quizzesTaken > 0)
            <div class="dash-panel dash-animate dash-animate-delay-2" style="padding: 0;">
                <div class="dash-panel-header">
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('My Quiz Performance') }}</h2>
                </div>
                <div class="grid grid-cols-3 divide-x divide-zinc-100 dark:divide-zinc-700/50 p-1">
                    <div class="p-3 text-center">
                        <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $quizzesTaken }}</p>
                        <p class="text-[10px] sm:text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400 mt-1">{{ __('Taken') }}</p>
                    </div>
                    <div class="p-3 text-center">
                        <p class="text-2xl font-bold {{ $quizAvgScore >= 70 ? 'text-emerald-600 dark:text-emerald-400' : ($quizAvgScore >= 50 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400') }}">
                            {{ number_format((float) $quizAvgScore, 0) }}%
                        </p>
                        <p class="text-[10px] sm:text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400 mt-1">{{ __('Average') }}</p>
                    </div>
                    <div class="p-3 text-center">
                        <p class="text-2xl font-bold {{ $quizPassRate >= 70 ? 'text-emerald-600 dark:text-emerald-400' : ($quizPassRate >= 50 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400') }}">
                            {{ $quizPassRate }}%
                        </p>
                        <p class="text-[10px] sm:text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400 mt-1">{{ __('Pass Rate') }}</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- ── Upcoming Deadlines ─────────────────────────────────── --}}
        @if ($upcomingDeadlines->isNotEmpty())
            <div class="dash-panel dash-animate dash-animate-delay-3" style="padding: 0;">
                <div class="dash-panel-header">
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Upcoming Deadlines') }}</h2>
                </div>
                <div class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
                    @foreach ($upcomingDeadlines as $deadline)
                        @php
                            $daysLeft = (int) now()->diffInDays($deadline->due_date, false);
                            $urgent = $daysLeft <= 2;
                        @endphp
                        <div class="activity-item">
                            <div class="activity-dot {{ $urgent ? 'bg-red-100 dark:bg-red-900/30' : 'bg-blue-100 dark:bg-blue-900/30' }}">
                                <flux:icon.calendar class="w-4 h-4 {{ $urgent ? 'text-red-600 dark:text-red-400' : 'text-blue-600 dark:text-blue-400' }}" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-zinc-900 dark:text-white truncate">
                                    {{ $deadline->title ?? __('Week :week Assignment', ['week' => $deadline->week_number]) }}
                                </p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-sm font-semibold {{ $urgent ? 'text-red-600 dark:text-red-400' : 'text-zinc-700 dark:text-zinc-300' }}">
                                    {{ $deadline->due_date->format('M j') }}
                                </p>
                                <p class="text-xs {{ $urgent ? 'text-red-500 dark:text-red-400' : 'text-zinc-500 dark:text-zinc-400' }}">
                                    @if ($daysLeft == 0) {{ __('Today') }}
                                    @elseif ($daysLeft == 1) {{ __('Tomorrow') }}
                                    @else {{ __(':days days', ['days' => $daysLeft]) }}
                                    @endif
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ── Available Quizzes + Available Games ────────────────── --}}
        @if ($upcomingQuizzes->isNotEmpty() || $upcomingGames->isNotEmpty())
            <div class="grid gap-4 sm:gap-6 lg:grid-cols-2">
                @if ($upcomingQuizzes->isNotEmpty())
                    <div class="dash-panel dash-animate dash-animate-delay-3" style="padding: 0;">
                        <div class="dash-panel-header">
                            <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Available Quizzes') }}</h2>
                            <a href="{{ route('student.quizzes.index') }}" wire:navigate class="text-xs font-medium text-blue-600 dark:text-blue-400 hover:underline">{{ __('View all') }}</a>
                        </div>
                        <div class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
                            @foreach ($upcomingQuizzes as $quiz)
                                <div class="p-4 flex items-start justify-between gap-3">
                                    <div class="min-w-0 flex-1">
                                        <p class="font-medium text-sm text-zinc-900 dark:text-white truncate">{{ $quiz->title }}</p>
                                        <div class="flex flex-wrap gap-x-2 gap-y-0.5 mt-1">
                                            <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $quiz->total_questions }} {{ __('questions') }}</span>
                                            @if ($quiz->time_limit_minutes)
                                                <span class="text-xs text-zinc-500 dark:text-zinc-400">&middot; {{ $quiz->time_limit_minutes }} {{ __('min') }}</span>
                                            @endif
                                            @if ($quiz->expires_at)
                                                <span class="text-xs text-amber-600 dark:text-amber-400">&middot; {{ __('Due :date', ['date' => $quiz->expires_at->format('M j')]) }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <a href="{{ route('student.quizzes.index') }}" wire:navigate class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors shrink-0">
                                        {{ __('Start') }}
                                        <flux:icon.arrow-right class="w-3 h-3" />
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($upcomingGames->isNotEmpty())
                    <div class="dash-panel dash-animate dash-animate-delay-4" style="padding: 0;">
                        <div class="dash-panel-header">
                            <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Available Games') }}</h2>
                            <a href="{{ route('student.games.index') }}" wire:navigate class="text-xs font-medium text-blue-600 dark:text-blue-400 hover:underline">{{ __('View all') }}</a>
                        </div>
                        <div class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
                            @foreach ($upcomingGames as $game)
                                <div class="p-4 flex items-start justify-between gap-3">
                                    <div class="min-w-0 flex-1">
                                        <p class="font-medium text-sm text-zinc-900 dark:text-white truncate">{{ $game->title }}</p>
                                        <div class="flex flex-wrap gap-1.5 mt-1.5">
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">{{ $game->gameTypeLabel() }}</span>
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300 capitalize">{{ $game->difficulty }}</span>
                                        </div>
                                    </div>
                                    <a href="{{ route('student.games.play', $game) }}" wire:navigate class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-white bg-pink-600 hover:bg-pink-700 rounded-lg transition-colors shrink-0">
                                        {{ __('Play') }}
                                        <flux:icon.arrow-right class="w-3 h-3" />
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif

        {{-- ── Recent Results + Recent Assignments ────────────────── --}}
        <div class="grid gap-4 sm:gap-6 lg:grid-cols-2">
            <div class="dash-panel dash-animate dash-animate-delay-4" style="padding: 0;">
                <div class="dash-panel-header">
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Recent Results') }}</h2>
                    @if ($resultsCount > 0)
                        <a href="{{ route('student.results.index') }}" wire:navigate class="text-xs font-medium text-blue-600 dark:text-blue-400 hover:underline">{{ __('View all') }}</a>
                    @endif
                </div>
                <div class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
                    @forelse ($recentResults as $result)
                        <a href="{{ route('student.results.show', $result) }}" wire:navigate class="activity-item hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition-colors">
                            <div class="activity-dot bg-emerald-100 dark:bg-emerald-900/30">
                                <flux:icon.document-text class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-zinc-900 dark:text-white">
                                    {{ $result->session?->name }} &mdash; {{ $result->term?->name }}
                                </p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $result->created_at->format('M j, Y') }}</p>
                            </div>
                            <flux:icon.chevron-right class="w-4 h-4 text-zinc-400 shrink-0" />
                        </a>
                    @empty
                        <div class="p-8 text-center">
                            <div class="w-12 h-12 mx-auto rounded-full bg-zinc-100 dark:bg-zinc-700 flex items-center justify-center mb-3">
                                <flux:icon.document-text class="w-6 h-6 text-zinc-400" />
                            </div>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No results available yet.') }}</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="dash-panel dash-animate dash-animate-delay-5" style="padding: 0;">
                <div class="dash-panel-header">
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Recent Assignments') }}</h2>
                    @if ($assignmentsCount > 0)
                        <a href="{{ route('student.assignments.index') }}" wire:navigate class="text-xs font-medium text-blue-600 dark:text-blue-400 hover:underline">{{ __('View all') }}</a>
                    @endif
                </div>
                <div class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
                    @forelse ($recentAssignments as $assignment)
                        <div class="activity-item">
                            <div class="activity-dot bg-purple-100 dark:bg-purple-900/30">
                                <flux:icon.clipboard-document-list class="w-4 h-4 text-purple-600 dark:text-purple-400" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-zinc-900 dark:text-white">
                                    {{ $assignment->title ?? __('Week :week Assignment', ['week' => $assignment->week_number]) }}
                                </p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ __('Week :week', ['week' => $assignment->week_number]) }}
                                    @if ($assignment->due_date)
                                        &mdash; {{ __('Due: :date', ['date' => $assignment->due_date->format('M j, Y')]) }}
                                    @endif
                                </p>
                            </div>
                            @if ($assignment->file_url)
                                <a href="{{ $assignment->file_url }}" target="_blank" rel="noopener noreferrer" class="text-blue-600 dark:text-blue-400 hover:text-blue-700 shrink-0">
                                    <flux:icon.arrow-down-tray class="w-4 h-4" />
                                    <span class="sr-only">{{ __('Download') }}</span>
                                </a>
                            @endif
                        </div>
                    @empty
                        <div class="p-8 text-center">
                            <div class="w-12 h-12 mx-auto rounded-full bg-zinc-100 dark:bg-zinc-700 flex items-center justify-center mb-3">
                                <flux:icon.clipboard-document-list class="w-6 h-6 text-zinc-400" />
                            </div>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No assignments for this term yet.') }}</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ── Latest Notices ─────────────────────────────────────── --}}
        @if ($recentNotices->isNotEmpty())
            <div class="dash-panel dash-animate dash-animate-delay-5" style="padding: 0;">
                <div class="dash-panel-header">
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Latest Notices') }}</h2>
                    <a href="{{ route('student.notices.index') }}" wire:navigate class="text-xs font-medium text-blue-600 dark:text-blue-400 hover:underline">{{ __('View all') }}</a>
                </div>
                <div class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
                    @foreach ($recentNotices as $notice)
                        <a href="{{ route('student.notices.show', $notice) }}" wire:navigate class="flex items-start gap-3 p-4 hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition-colors">
                            @if ($notice->image_url)
                                <img src="{{ $notice->image_url }}" alt="" class="w-12 h-12 rounded-lg object-cover shrink-0" loading="lazy" />
                            @else
                                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-cyan-100 dark:bg-cyan-900/30 shrink-0">
                                    <flux:icon.megaphone class="w-5 h-5 text-cyan-600 dark:text-cyan-400" />
                                </div>
                            @endif
                            <div class="min-w-0 flex-1">
                                <p class="font-medium text-sm text-zinc-900 dark:text-white">{{ $notice->title }}</p>
                                <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-0.5">{{ $notice->published_at->format('M j, Y') }}</p>
                            </div>
                            <flux:icon.chevron-right class="w-4 h-4 text-zinc-400 mt-1 shrink-0" />
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-layouts::app>
