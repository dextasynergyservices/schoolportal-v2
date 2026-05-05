<x-layouts::app :title="__('My Achievements')">
    @php
        $colorClasses = [
            'amber'   => ['bg' => 'bg-amber-50 dark:bg-amber-900/20',   'border' => 'border-amber-200/60 dark:border-amber-700/40',   'text' => 'text-amber-700 dark:text-amber-400',   'fill' => 'bg-amber-500',   'pill' => 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400'],
            'yellow'  => ['bg' => 'bg-yellow-50 dark:bg-yellow-900/20', 'border' => 'border-yellow-200/60 dark:border-yellow-700/40', 'text' => 'text-yellow-700 dark:text-yellow-400', 'fill' => 'bg-yellow-500', 'pill' => 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400'],
            'blue'    => ['bg' => 'bg-blue-50 dark:bg-blue-900/20',     'border' => 'border-blue-200/60 dark:border-blue-700/40',     'text' => 'text-blue-700 dark:text-blue-400',     'fill' => 'bg-blue-500',   'pill' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400'],
            'indigo'  => ['bg' => 'bg-indigo-50 dark:bg-indigo-900/20', 'border' => 'border-indigo-200/60 dark:border-indigo-700/40', 'text' => 'text-indigo-700 dark:text-indigo-400', 'fill' => 'bg-indigo-500', 'pill' => 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400'],
            'emerald' => ['bg' => 'bg-emerald-50 dark:bg-emerald-900/20','border' => 'border-emerald-200/60 dark:border-emerald-700/40','text' => 'text-emerald-700 dark:text-emerald-400','fill' => 'bg-emerald-500','pill' => 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400'],
            'cyan'    => ['bg' => 'bg-cyan-50 dark:bg-cyan-900/20',     'border' => 'border-cyan-200/60 dark:border-cyan-700/40',     'text' => 'text-cyan-700 dark:text-cyan-400',     'fill' => 'bg-cyan-500',   'pill' => 'bg-cyan-100 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-400'],
            'pink'    => ['bg' => 'bg-pink-50 dark:bg-pink-900/20',     'border' => 'border-pink-200/60 dark:border-pink-700/40',     'text' => 'text-pink-700 dark:text-pink-400',     'fill' => 'bg-pink-500',   'pill' => 'bg-pink-100 dark:bg-pink-900/30 text-pink-700 dark:text-pink-400'],
            'rose'    => ['bg' => 'bg-rose-50 dark:bg-rose-900/20',     'border' => 'border-rose-200/60 dark:border-rose-700/40',     'text' => 'text-rose-700 dark:text-rose-400',     'fill' => 'bg-rose-500',   'pill' => 'bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400'],
            'purple'  => ['bg' => 'bg-purple-50 dark:bg-purple-900/20', 'border' => 'border-purple-200/60 dark:border-purple-700/40', 'text' => 'text-purple-700 dark:text-purple-400', 'fill' => 'bg-purple-500', 'pill' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400'],
            'violet'  => ['bg' => 'bg-violet-50 dark:bg-violet-900/20', 'border' => 'border-violet-200/60 dark:border-violet-700/40', 'text' => 'text-violet-700 dark:text-violet-400', 'fill' => 'bg-violet-500', 'pill' => 'bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-400'],
        ];
    @endphp

    <div class="space-y-6">
        <x-admin-header
            :title="__('My Achievements')"
            :description="__('Your earned badges, streaks, and goals')"
        />

        {{-- ── Summary Bar ─────────────────────────────────────────── --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            {{-- Progress --}}
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 text-center">
                <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $unlockedCount }}<span class="text-sm font-normal text-zinc-400 ml-0.5">/{{ $totalCount }}</span></p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">{{ __('Badges Earned') }}</p>
            </div>

            @if ($loginStreak >= 1)
                <div class="rounded-lg border border-amber-200/60 dark:border-amber-700/40 bg-amber-50 dark:bg-amber-900/20 p-4 text-center">
                    <p class="text-2xl font-bold text-amber-700 dark:text-amber-400">{{ $loginStreak }}</p>
                    <p class="text-xs text-amber-600 dark:text-amber-500 mt-0.5">{{ __('Day Login Streak') }} 🔥</p>
                </div>
            @else
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 text-center">
                    <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $loginStreak }}</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">{{ __('Day Login Streak') }}</p>
                </div>
            @endif

            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 text-center">
                <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $bestLoginStreak }}</p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">{{ __('Best Streak') }} ⭐</p>
            </div>

            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 text-center">
                <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $quizPassStreak }}</p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">{{ __('Quiz Pass Streak') }} 🎯</p>
            </div>
        </div>

        {{-- Overall progress bar --}}
        @if ($totalCount > 0)
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-5 py-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Overall Progress') }}</span>
                    <span class="text-sm font-bold text-indigo-600 dark:text-indigo-400">{{ number_format(($unlockedCount / $totalCount) * 100, 0) }}%</span>
                </div>
                <div class="h-2.5 bg-zinc-200 dark:bg-zinc-700 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-indigo-500 to-indigo-600 rounded-full transition-all duration-700"
                         style="width: {{ number_format(($unlockedCount / $totalCount) * 100, 0) }}%"></div>
                </div>
                <p class="mt-1.5 text-xs text-zinc-500 dark:text-zinc-400">
                    {{ $unlockedCount }} {{ __('of') }} {{ $totalCount }} {{ __('badges unlocked') }}
                    @if ($unlockedCount < $totalCount)
                        &mdash; {{ $totalCount - $unlockedCount }} {{ __('to go!') }}
                    @else
                        &mdash; 🏆 {{ __('All badges unlocked!') }}
                    @endif
                </p>
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2 space-y-6">

                {{-- ── Earned Badges ──────────────────────────────── --}}
                @if (count($unlocked) > 0)
                    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800">
                        <div class="border-b border-zinc-200 dark:border-zinc-700 px-5 py-3 flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">
                                {{ __('Earned Badges') }}
                            </h3>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400">
                                {{ $unlockedCount }}
                            </span>
                        </div>
                        <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @foreach ($unlocked as $badge)
                                @php $c = $colorClasses[$badge['color']] ?? $colorClasses['amber']; @endphp
                                <div class="flex items-start gap-3 rounded-lg border p-3 {{ $c['bg'] }} {{ $c['border'] }}">
                                    <div class="shrink-0 flex items-center justify-center w-10 h-10 rounded-full bg-white/60 dark:bg-zinc-900/30 text-2xl leading-none">
                                        {{ $badge['icon'] }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold {{ $c['text'] }} truncate">{{ $badge['name'] }}</p>
                                        <p class="text-xs text-zinc-600 dark:text-zinc-400 leading-snug">{{ $badge['description'] }}</p>
                                        <p class="mt-1 text-[10px] text-zinc-400 dark:text-zinc-500">
                                            {{ __('Earned') }} {{ $badge['unlocked_at_formatted'] }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-8 text-center">
                        <div class="text-4xl mb-3">🏆</div>
                        <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('No badges yet') }}</p>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Complete quizzes and games to start earning badges!') }}</p>
                    </div>
                @endif

                {{-- ── Locked Badges ───────────────────────────────── --}}
                @if (count($locked) > 0)
                    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800">
                        <div class="border-b border-zinc-200 dark:border-zinc-700 px-5 py-3 flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-zinc-500 dark:text-zinc-400">
                                {{ __('Locked Badges') }}
                            </h3>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-zinc-100 dark:bg-zinc-700 text-zinc-500 dark:text-zinc-400">
                                {{ count($locked) }}
                            </span>
                        </div>
                        <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @foreach ($locked as $badge)
                                <div class="flex items-start gap-3 rounded-lg border border-zinc-200/60 dark:border-zinc-700/40 p-3 bg-zinc-50 dark:bg-zinc-800/50 opacity-60">
                                    <div class="shrink-0 flex items-center justify-center w-10 h-10 rounded-full bg-zinc-200 dark:bg-zinc-700 text-2xl leading-none grayscale">
                                        {{ $badge['icon'] }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-zinc-500 dark:text-zinc-400 truncate flex items-center gap-1.5">
                                            <svg class="size-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                                            {{ $badge['name'] }}
                                        </p>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-500 leading-snug">{{ $badge['description'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

            </div>

            {{-- ── Sidebar: Next Goals ─────────────────────────────── --}}
            <div class="space-y-6">
                @if (count($nextGoals) > 0)
                    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800">
                        <div class="border-b border-zinc-200 dark:border-zinc-700 px-5 py-3">
                            <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Next Goals') }}</h3>
                        </div>
                        <div class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
                            @foreach ($nextGoals as $goal)
                                @php $c = $colorClasses[$goal['color']] ?? $colorClasses['amber']; @endphp
                                <div class="px-4 py-3 flex items-center gap-3">
                                    <div class="flex items-center justify-center w-9 h-9 rounded-full bg-zinc-100 dark:bg-zinc-700/50 opacity-60 shrink-0">
                                        <span class="text-lg leading-none grayscale">{{ $goal['icon'] }}</span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300 truncate">{{ $goal['name'] }}</span>
                                            <span class="text-[10px] font-medium text-zinc-500 dark:text-zinc-400 shrink-0 ml-2">{{ $goal['progress'] }}</span>
                                        </div>
                                        <div class="h-1.5 bg-zinc-200 dark:bg-zinc-700 rounded-full overflow-hidden">
                                            <div class="h-full {{ $c['fill'] }} rounded-full transition-all duration-700"
                                                 style="width: {{ $goal['percent'] }}%"></div>
                                        </div>
                                    </div>
                                    @if (($goal['category'] ?? '') === 'quiz')
                                        <a href="{{ route('student.quizzes.index') }}"
                                           class="shrink-0 flex items-center justify-center w-7 h-7 rounded-full bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100 transition-colors"
                                           title="{{ __('Go to Quizzes') }}" aria-label="{{ __('Go to Quizzes') }}">
                                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                                        </a>
                                    @elseif (($goal['category'] ?? '') === 'game')
                                        <a href="{{ route('student.games.index') }}"
                                           class="shrink-0 flex items-center justify-center w-7 h-7 rounded-full bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-100 transition-colors"
                                           title="{{ __('Go to Games') }}" aria-label="{{ __('Go to Games') }}">
                                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                                        </a>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Quick links --}}
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800">
                    <div class="border-b border-zinc-200 dark:border-zinc-700 px-5 py-3">
                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Quick Links') }}</h3>
                    </div>
                    <div class="p-3 space-y-1">
                        <a href="{{ route('student.quizzes.index') }}"
                           class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors">
                            <flux:icon.academic-cap class="size-4 text-amber-500 shrink-0" />
                            {{ __('Browse Quizzes') }}
                        </a>
                        <a href="{{ route('student.games.index') }}"
                           class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors">
                            <flux:icon.puzzle-piece class="size-4 text-emerald-500 shrink-0" />
                            {{ __('Browse Games') }}
                        </a>
                        <a href="{{ route('student.profile') }}"
                           class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors">
                            <flux:icon.user class="size-4 text-indigo-500 shrink-0" />
                            {{ __('My Profile') }}
                        </a>
                        <a href="{{ route('student.dashboard') }}"
                           class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors">
                            <flux:icon.home class="size-4 text-zinc-500 shrink-0" />
                            {{ __('Back to Dashboard') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts::app>
