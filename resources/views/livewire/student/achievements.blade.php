@php
    // Map color names to Tailwind class sets (needed because dynamic class interpolation doesn't work with Tailwind purging)
    $colorClasses = [
        'amber'   => ['bg' => 'bg-amber-50 dark:bg-amber-900/20', 'border' => 'border-amber-200/60 dark:border-amber-700/40', 'hover' => 'hover:bg-amber-100 dark:hover:bg-amber-900/30', 'text' => 'text-amber-700 dark:text-amber-400', 'fill' => 'bg-amber-500'],
        'yellow'  => ['bg' => 'bg-yellow-50 dark:bg-yellow-900/20', 'border' => 'border-yellow-200/60 dark:border-yellow-700/40', 'hover' => 'hover:bg-yellow-100 dark:hover:bg-yellow-900/30', 'text' => 'text-yellow-700 dark:text-yellow-400', 'fill' => 'bg-yellow-500'],
        'blue'    => ['bg' => 'bg-blue-50 dark:bg-blue-900/20', 'border' => 'border-blue-200/60 dark:border-blue-700/40', 'hover' => 'hover:bg-blue-100 dark:hover:bg-blue-900/30', 'text' => 'text-blue-700 dark:text-blue-400', 'fill' => 'bg-blue-500'],
        'indigo'  => ['bg' => 'bg-indigo-50 dark:bg-indigo-900/20', 'border' => 'border-indigo-200/60 dark:border-indigo-700/40', 'hover' => 'hover:bg-indigo-100 dark:hover:bg-indigo-900/30', 'text' => 'text-indigo-700 dark:text-indigo-400', 'fill' => 'bg-indigo-500'],
        'emerald' => ['bg' => 'bg-emerald-50 dark:bg-emerald-900/20', 'border' => 'border-emerald-200/60 dark:border-emerald-700/40', 'hover' => 'hover:bg-emerald-100 dark:hover:bg-emerald-900/30', 'text' => 'text-emerald-700 dark:text-emerald-400', 'fill' => 'bg-emerald-500'],
        'cyan'    => ['bg' => 'bg-cyan-50 dark:bg-cyan-900/20', 'border' => 'border-cyan-200/60 dark:border-cyan-700/40', 'hover' => 'hover:bg-cyan-100 dark:hover:bg-cyan-900/30', 'text' => 'text-cyan-700 dark:text-cyan-400', 'fill' => 'bg-cyan-500'],
        'pink'    => ['bg' => 'bg-pink-50 dark:bg-pink-900/20', 'border' => 'border-pink-200/60 dark:border-pink-700/40', 'hover' => 'hover:bg-pink-100 dark:hover:bg-pink-900/30', 'text' => 'text-pink-700 dark:text-pink-400', 'fill' => 'bg-pink-500'],
        'rose'    => ['bg' => 'bg-rose-50 dark:bg-rose-900/20', 'border' => 'border-rose-200/60 dark:border-rose-700/40', 'hover' => 'hover:bg-rose-100 dark:hover:bg-rose-900/30', 'text' => 'text-rose-700 dark:text-rose-400', 'fill' => 'bg-rose-500'],
        'purple'  => ['bg' => 'bg-purple-50 dark:bg-purple-900/20', 'border' => 'border-purple-200/60 dark:border-purple-700/40', 'hover' => 'hover:bg-purple-100 dark:hover:bg-purple-900/30', 'text' => 'text-purple-700 dark:text-purple-400', 'fill' => 'bg-purple-500'],
        'violet'  => ['bg' => 'bg-violet-50 dark:bg-violet-900/20', 'border' => 'border-violet-200/60 dark:border-violet-700/40', 'hover' => 'hover:bg-violet-100 dark:hover:bg-violet-900/30', 'text' => 'text-violet-700 dark:text-violet-400', 'fill' => 'bg-violet-500'],
    ];
@endphp
<div>
    <div class="dash-panel" style="padding: 0;">
        {{-- Header --}}
        <div class="dash-panel-header">
            <h2 class="text-sm font-semibold text-zinc-900 dark:text-white flex items-center gap-2">
                🏆 {{ __('Achievements') }}
                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                    {{ $unlockedCount }}/{{ $totalCount }}
                </span>
            </h2>
        </div>

        {{-- Streak Banner --}}
        @if ($loginStreak >= 2 || $quizPassStreak >= 2)
            <div class="px-4 py-3 border-b border-zinc-100 dark:border-zinc-700/50">
                <div class="flex flex-wrap items-center gap-3">
                    @if ($loginStreak >= 2)
                        <div class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 border border-amber-200/60 dark:border-amber-700/40">
                            <span class="text-lg leading-none" aria-hidden="true">🔥</span>
                            <span class="text-sm font-bold text-amber-700 dark:text-amber-400">{{ $loginStreak }}-{{ __('day streak') }}!</span>
                        </div>
                    @endif
                    @if ($quizPassStreak >= 2)
                        <div class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-gradient-to-r from-cyan-50 to-blue-50 dark:from-cyan-900/20 dark:to-blue-900/20 border border-cyan-200/60 dark:border-cyan-700/40">
                            <span class="text-lg leading-none" aria-hidden="true">🎯</span>
                            <span class="text-sm font-bold text-cyan-700 dark:text-cyan-400">{{ $quizPassStreak }} {{ __('quizzes passed') }}!</span>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Recently Unlocked --}}
        @if (count($recentAchievements) > 0)
            <div class="px-4 py-3 {{ count($nextGoals) > 0 ? 'border-b border-zinc-100 dark:border-zinc-700/50' : '' }}">
                <p class="text-[10px] font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-2">
                    {{ __('Recently Earned') }}
                </p>
                <div class="flex flex-wrap gap-2">
                    @foreach ($recentAchievements as $achievement)
                        @php $c = $colorClasses[$achievement['color']] ?? $colorClasses['amber']; @endphp
                        <div class="group relative inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg transition-colors {{ $c['bg'] }} border {{ $c['border'] }} {{ $c['hover'] }}">
                            <span class="text-base leading-none" aria-hidden="true">{{ $achievement['icon'] }}</span>
                            <span class="text-xs font-semibold {{ $c['text'] }}">{{ $achievement['name'] }}</span>
                            {{-- Tooltip --}}
                            <span class="absolute -top-8 left-1/2 -translate-x-1/2 px-2 py-1 text-[10px] font-medium text-white bg-zinc-800 dark:bg-zinc-600 rounded shadow-lg whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-10">
                                {{ $achievement['description'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Next Goals --}}
        @if (count($nextGoals) > 0)
            <div class="px-4 py-3">
                <p class="text-[10px] font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-2">
                    {{ __('Next Goals') }}
                </p>
                <div class="space-y-2.5">
                    @foreach ($nextGoals as $goal)
                        @php $c = $colorClasses[$goal['color']] ?? $colorClasses['amber']; @endphp
                        <div class="flex items-center gap-3">
                            {{-- Icon badge (locked style) --}}
                            <div class="flex items-center justify-center w-8 h-8 rounded-full bg-zinc-100 dark:bg-zinc-700/50 opacity-60 shrink-0">
                                <span class="text-sm leading-none grayscale" aria-hidden="true">{{ $goal['icon'] }}</span>
                            </div>
                            {{-- Goal info + progress bar --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-0.5">
                                    <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300 truncate">{{ $goal['name'] }}</span>
                                    <span class="text-[10px] font-medium text-zinc-500 dark:text-zinc-400 shrink-0 ml-2">{{ $goal['progress'] }}</span>
                                </div>
                                <div class="progress-track" style="height: 4px;">
                                    <div class="progress-fill {{ $c['fill'] }}" style="width: {{ $goal['percent'] }}%; transition: width 0.6s ease;"></div>
                                </div>
                            </div>
                            {{-- Quick link for quiz/game goals --}}
                            @if (($goal['category'] ?? '') === 'quiz')
                                <a href="{{ route('student.quizzes.index') }}" class="shrink-0 flex items-center justify-center w-7 h-7 rounded-full bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100 dark:hover:bg-indigo-900/40 transition-colors" title="{{ __('Go to Quizzes') }}" aria-label="{{ __('Go to Quizzes') }}">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                                </a>
                            @elseif (($goal['category'] ?? '') === 'game')
                                <a href="{{ route('student.games.index') }}" class="shrink-0 flex items-center justify-center w-7 h-7 rounded-full bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-100 dark:hover:bg-emerald-900/40 transition-colors" title="{{ __('Go to Games') }}" aria-label="{{ __('Go to Games') }}">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Empty state --}}
        @if (count($recentAchievements) === 0 && count($nextGoals) === 0)
            <div class="p-6 text-center">
                <div class="text-3xl mb-2">🏆</div>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('Start completing quizzes and games to earn achievements!') }}
                </p>
            </div>
        @endif
    </div>
</div>
