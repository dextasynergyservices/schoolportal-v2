<x-layouts::app :title="__('Parent Dashboard')">
    @include('partials.dashboard-styles')

    <div class="space-y-6">
        {{-- ── Welcome Banner ─────────────────────────────────────── --}}
        <div class="dash-welcome dash-animate" role="banner">
            <div class="relative z-10">
                <h1 class="text-xl sm:text-2xl font-bold text-white">
                    {{ __('Welcome, :name', ['name' => $parent->name]) }} 👋
                </h1>
                @if ($currentSession && $currentTerm)
                    <p class="mt-1 text-sm text-white/70">
                        {{ $currentSession->name }} &mdash; {{ $currentTerm->name }}
                    </p>
                @endif
            </div>
        </div>

        {{-- ── Family Summary Stats ───────────────────────────────── --}}
        @if ($children->isNotEmpty())
            <section aria-label="{{ __('Summary statistics') }}">
                <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-5">
                    <div class="stat-card stat-card-blue dash-animate dash-animate-delay-1">
                        <div class="flex items-center gap-3">
                            <div class="stat-icon bg-blue-500/15">
                                <flux:icon.users class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 truncate">{{ __('Children') }}</p>
                                <p class="stat-value text-zinc-900 dark:text-white">{{ $children->count() }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card stat-card-emerald dash-animate dash-animate-delay-2">
                        <div class="flex items-center gap-3">
                            <div class="stat-icon bg-emerald-500/15">
                                <flux:icon.document-text class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 truncate">{{ __('Total Results') }}</p>
                                <p class="stat-value text-zinc-900 dark:text-white">{{ number_format($totalResults) }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card stat-card-amber dash-animate dash-animate-delay-3">
                        <div class="flex items-center gap-3">
                            <div class="stat-icon bg-amber-500/15">
                                <flux:icon.academic-cap class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 truncate">{{ __('Quizzes Taken') }}</p>
                                <p class="stat-value text-zinc-900 dark:text-white">{{ number_format($totalQuizzesTaken) }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card stat-card-pink dash-animate dash-animate-delay-4">
                        <div class="flex items-center gap-3">
                            <div class="stat-icon bg-pink-500/15">
                                <flux:icon.puzzle-piece class="w-5 h-5 text-pink-600 dark:text-pink-400" />
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 truncate">{{ __('Games Played') }}</p>
                                <p class="stat-value text-zinc-900 dark:text-white">{{ number_format($totalGamesPlayed) }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card stat-card-purple dash-animate dash-animate-delay-4">
                        <div class="flex items-center gap-3">
                            <div class="stat-icon bg-purple-500/15">
                                <flux:icon.chart-bar class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 truncate">{{ __('Avg Quiz Score') }}</p>
                                <p class="stat-value text-zinc-900 dark:text-white">{{ $overallQuizAvg !== null ? $overallQuizAvg . '%' : '—' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        {{-- ── Children Section ───────────────────────────────────── --}}
        <section aria-label="{{ __('Your children') }}">
            <h2 class="text-sm font-semibold text-zinc-900 dark:text-white mb-3 dash-animate dash-animate-delay-3">{{ __('Your Children') }}</h2>

            @if ($children->isNotEmpty())
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($children as $child)
                        @php
                            $profile = $child->studentProfile;
                            $class = $profile?->class;
                            $stats = $childrenStats[$child->id] ?? [];
                        @endphp
                        <div class="child-card dash-animate dash-animate-delay-{{ min($loop->iteration + 2, 6) }}">
                            {{-- Child Header --}}
                            <div class="p-4 border-b border-zinc-100 dark:border-zinc-700/50">
                                <div class="flex items-center gap-3">
                                    <flux:avatar size="lg" :src="$child->avatar_url" :name="$child->name" :initials="$child->initials()" />
                                    <div class="min-w-0 flex-1">
                                        <p class="font-bold text-zinc-900 dark:text-white truncate">{{ $child->name }}</p>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                            {{ $class?->name ?? __('No class assigned') }}
                                        </p>
                                        @if ($class?->level)
                                            <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ $class->level->name }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- Per-Child Quick Stats --}}
                            <div class="grid grid-cols-4 divide-x divide-zinc-100 dark:divide-zinc-700/50 border-b border-zinc-100 dark:border-zinc-700/50">
                                <div class="p-3 text-center">
                                    <p class="text-lg font-bold text-zinc-900 dark:text-white">{{ $stats['results_count'] ?? 0 }}</p>
                                    <p class="text-[10px] uppercase tracking-wider font-medium text-zinc-500 dark:text-zinc-400">{{ __('Results') }}</p>
                                </div>
                                <div class="p-3 text-center">
                                    <p class="text-lg font-bold text-zinc-900 dark:text-white">{{ $stats['quizzes_taken'] ?? 0 }}</p>
                                    <p class="text-[10px] uppercase tracking-wider font-medium text-zinc-500 dark:text-zinc-400">{{ __('Quizzes') }}</p>
                                </div>
                                <div class="p-3 text-center">
                                    <p class="text-lg font-bold text-zinc-900 dark:text-white">{{ $stats['games_played'] ?? 0 }}</p>
                                    <p class="text-[10px] uppercase tracking-wider font-medium text-zinc-500 dark:text-zinc-400">{{ __('Games') }}</p>
                                </div>
                                <div class="p-3 text-center">
                                    @php $childAvg = $stats['quiz_avg'] ?? null; @endphp
                                    <p class="text-lg font-bold {{ $childAvg !== null && $childAvg >= 70 ? 'text-emerald-600 dark:text-emerald-400' : ($childAvg !== null && $childAvg >= 50 ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-900 dark:text-white') }}">
                                        {{ $childAvg !== null ? number_format((float) $childAvg, 0) . '%' : '—' }}
                                    </p>
                                    <p class="text-[10px] uppercase tracking-wider font-medium text-zinc-500 dark:text-zinc-400">{{ __('Avg') }}</p>
                                </div>
                            </div>

                            {{-- Latest Result --}}
                            @if (!empty($stats['latest_result']))
                                <div class="px-4 py-2 bg-zinc-50 dark:bg-zinc-800/50 border-b border-zinc-100 dark:border-zinc-700/50">
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ __('Latest result:') }}
                                        <span class="font-semibold text-zinc-700 dark:text-zinc-300">
                                            {{ $stats['latest_result']->session?->name }} &mdash; {{ $stats['latest_result']->term?->name }}
                                        </span>
                                    </p>
                                </div>
                            @endif

                            {{-- Class Teacher --}}
                            @if ($class?->teacher)
                                <div class="px-4 py-2 bg-zinc-50 dark:bg-zinc-800/50 border-b border-zinc-100 dark:border-zinc-700/50">
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ __('Class Teacher: :name', ['name' => $class->teacher->name]) }}
                                    </p>
                                </div>
                            @endif

                            {{-- Quick Actions --}}
                            <div class="p-3 grid grid-cols-2 gap-1.5">
                                <a href="{{ route('parent.children.results', $child) }}" wire:navigate class="quick-action !p-2 !gap-1.5">
                                    <div class="quick-action-icon !w-7 !h-7 bg-emerald-100 dark:bg-emerald-900/30">
                                        <flux:icon.document-text class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" />
                                    </div>
                                    <span class="text-[10px] font-medium text-zinc-600 dark:text-zinc-400">{{ __('Results') }}</span>
                                </a>
                                <a href="{{ route('parent.children.assignments', $child) }}" wire:navigate class="quick-action !p-2 !gap-1.5">
                                    <div class="quick-action-icon !w-7 !h-7 bg-purple-100 dark:bg-purple-900/30">
                                        <flux:icon.clipboard-document-list class="w-3.5 h-3.5 text-purple-600 dark:text-purple-400" />
                                    </div>
                                    <span class="text-[10px] font-medium text-zinc-600 dark:text-zinc-400">{{ __('Assignments') }}</span>
                                </a>
                                <a href="{{ route('parent.children.quizzes', $child) }}" wire:navigate class="quick-action !p-2 !gap-1.5">
                                    <div class="quick-action-icon !w-7 !h-7 bg-amber-100 dark:bg-amber-900/30">
                                        <flux:icon.academic-cap class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400" />
                                    </div>
                                    <span class="text-[10px] font-medium text-zinc-600 dark:text-zinc-400">{{ __('Quizzes') }}</span>
                                </a>
                                <a href="{{ route('parent.children.games', $child) }}" wire:navigate class="quick-action !p-2 !gap-1.5">
                                    <div class="quick-action-icon !w-7 !h-7 bg-pink-100 dark:bg-pink-900/30">
                                        <flux:icon.puzzle-piece class="w-3.5 h-3.5 text-pink-600 dark:text-pink-400" />
                                    </div>
                                    <span class="text-[10px] font-medium text-zinc-600 dark:text-zinc-400">{{ __('Games') }}</span>
                                </a>
                                <a href="{{ route('parent.children.show', $child) }}" wire:navigate class="quick-action !p-2 !gap-1.5 col-span-2">
                                    <div class="quick-action-icon !w-7 !h-7 bg-blue-100 dark:bg-blue-900/30">
                                        <flux:icon.user class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" />
                                    </div>
                                    <span class="text-[10px] font-medium text-zinc-600 dark:text-zinc-400">{{ __('View Profile') }}</span>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="dash-panel p-8 text-center dash-animate dash-animate-delay-3">
                    <div class="w-16 h-16 mx-auto rounded-full bg-zinc-100 dark:bg-zinc-700 flex items-center justify-center mb-4">
                        <flux:icon.users class="w-8 h-8 text-zinc-400" />
                    </div>
                    <p class="font-semibold text-zinc-900 dark:text-white">{{ __('No children linked') }}</p>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400 max-w-sm mx-auto">
                        {{ __('Your school admin will link your children to your account. Please contact the school if you believe this is an error.') }}
                    </p>
                </div>
            @endif
        </section>

        {{-- ── Latest Notices ─────────────────────────────────────── --}}
        @if ($recentNotices->isNotEmpty())
            <div class="dash-panel dash-animate dash-animate-delay-5" style="padding: 0;">
                <div class="dash-panel-header">
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('School Notices') }}</h2>
                    <a href="{{ route('parent.notices.index') }}" wire:navigate class="text-xs font-medium text-blue-600 dark:text-blue-400 hover:underline">{{ __('View all') }}</a>
                </div>
                <div class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
                    @foreach ($recentNotices as $notice)
                        <a href="{{ route('parent.notices.show', $notice) }}" wire:navigate class="flex items-start gap-3 p-4 hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition-colors">
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

        {{-- ── School Contact Info ────────────────────────────────── --}}
        <div class="dash-panel dash-animate dash-animate-delay-6">
            <div class="flex items-center gap-3 mb-3">
                <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-zinc-100 dark:bg-zinc-700">
                    @if ($school->logo_url)
                        <img src="{{ $school->logo_url }}" alt="{{ $school->name }}" class="w-8 h-8 rounded object-contain" />
                    @else
                        <flux:icon.building-office-2 class="w-5 h-5 text-zinc-500 dark:text-zinc-400" />
                    @endif
                </div>
                <div>
                    <p class="font-bold text-zinc-900 dark:text-white text-sm">{{ $school->name }}</p>
                    @if ($school->motto)
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 italic">{{ $school->motto }}</p>
                    @endif
                </div>
            </div>
            <div class="flex flex-wrap gap-x-6 gap-y-1.5 text-xs text-zinc-500 dark:text-zinc-400">
                @if ($school->phone)
                    <span class="flex items-center gap-1.5">
                        <flux:icon.phone class="w-3.5 h-3.5" />
                        <a href="tel:{{ $school->phone }}" class="hover:underline">{{ $school->phone }}</a>
                    </span>
                @endif
                @if ($school->email)
                    <span class="flex items-center gap-1.5">
                        <flux:icon.envelope class="w-3.5 h-3.5" />
                        <a href="mailto:{{ $school->email }}" class="hover:underline">{{ $school->email }}</a>
                    </span>
                @endif
                @if ($school->address)
                    <span class="flex items-center gap-1.5">
                        <flux:icon.map-pin class="w-3.5 h-3.5" />
                        {{ $school->address }}
                    </span>
                @endif
            </div>
        </div>
    </div>
</x-layouts::app>
