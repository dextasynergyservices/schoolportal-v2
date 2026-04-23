<div>
    @if ($availableQuizCount === 0 && $availableGameCount === 0)
        {{-- ── No content to analyze ──────────────────────────────── --}}
        <div class="dash-panel">
            <div class="dash-panel-header">
                <div class="flex items-center gap-2">
                    <flux:icon.magnifying-glass class="w-4 h-4 text-zinc-400" />
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Student Insights') }}</h2>
                </div>
            </div>
            <div class="p-8 text-center">
                <div class="w-12 h-12 mx-auto rounded-full bg-zinc-100 dark:bg-zinc-700 flex items-center justify-center mb-3">
                    <flux:icon.chart-bar class="w-6 h-6 text-zinc-400" />
                </div>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('Publish quizzes or games to start tracking student performance.') }}
                </p>
            </div>
        </div>
    @elseif (empty($insights))
        {{-- ── All students on track ──────────────────────────────── --}}
        <div class="dash-panel">
            <div class="dash-panel-header">
                <div class="flex items-center gap-2">
                    <flux:icon.magnifying-glass class="w-4 h-4 text-emerald-500" />
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Student Insights') }}</h2>
                </div>
            </div>
            <div class="p-8 text-center">
                <div class="w-14 h-14 mx-auto rounded-full bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center mb-3 ring-4 ring-emerald-100 dark:ring-emerald-900/30">
                    <flux:icon.check-circle class="w-8 h-8 text-emerald-500" />
                </div>
                <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ __('All students are on track!') }}</p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                    {{ trans_choice(':count student is|:count students are', $totalStudents, ['count' => $totalStudents]) }}
                    {{ __('performing well across :quizzes quizzes and :games games.', ['quizzes' => $availableQuizCount, 'games' => $availableGameCount]) }}
                </p>
            </div>
        </div>
    @else
        {{-- ── Needs Attention panel ──────────────────────────────── --}}
        <div class="dash-panel">
            <div class="dash-panel-header">
                <div class="flex items-center gap-2">
                    <div class="w-5 h-5 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center shrink-0">
                        <flux:icon.exclamation-triangle class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400" />
                    </div>
                    <div>
                        <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Needs Attention') }}</h2>
                    </div>
                </div>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                    {{ trans_choice(':count student|:count students', $flaggedCount, ['count' => $flaggedCount]) }}
                </span>
            </div>

            {{-- ── Summary bar ─────────────────────────────────────── --}}
            <div class="px-4 pt-3 pb-2 sm:px-5">
                <div class="flex items-center gap-3 text-xs">
                    <div class="flex-1">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-zinc-500 dark:text-zinc-400">{{ __('Class health') }}</span>
                            <span class="font-semibold text-zinc-700 dark:text-zinc-300">
                                {{ $onTrackCount }}/{{ $totalStudents }} {{ __('on track') }}
                            </span>
                        </div>
                        @php $healthPercent = $totalStudents > 0 ? round(($onTrackCount / $totalStudents) * 100) : 100; @endphp
                        <div class="h-1.5 w-full rounded-full bg-zinc-200 dark:bg-zinc-700 overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-500 {{ $healthPercent >= 80 ? 'bg-emerald-500' : ($healthPercent >= 60 ? 'bg-amber-500' : 'bg-red-500') }}"
                                 style="width: {{ $healthPercent }}%"></div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 shrink-0 text-zinc-500 dark:text-zinc-400 border-l border-zinc-200 dark:border-zinc-700 pl-3">
                        @if ($availableQuizCount > 0)
                            <span class="flex items-center gap-1" title="{{ __('Published quizzes') }}">
                                <flux:icon.academic-cap class="w-3.5 h-3.5" /> {{ $availableQuizCount }}
                            </span>
                        @endif
                        @if ($availableGameCount > 0)
                            <span class="flex items-center gap-1" title="{{ __('Published games') }}">
                                <flux:icon.puzzle-piece class="w-3.5 h-3.5" /> {{ $availableGameCount }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ── Student list ────────────────────────────────────── --}}
            <div class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
                @foreach ($insights as $index => $student)
                    @if ($compact && $index >= 3)
                        @break
                    @endif
                    @php
                        $severityColor = match(true) {
                            $student['severity'] >= 4 => 'red',
                            $student['severity'] >= 2 => 'amber',
                            default => 'blue',
                        };
                    @endphp
                    <div class="px-4 py-3 sm:px-5 flex items-start gap-3 group hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition-colors">
                        {{-- Severity indicator + Avatar --}}
                        <div class="relative shrink-0">
                            @if ($student['avatar_url'])
                                <img src="{{ $student['avatar_url'] }}" alt=""
                                     class="w-8 h-8 rounded-full object-cover ring-2 ring-{{ $severityColor }}-200 dark:ring-{{ $severityColor }}-800">
                            @else
                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold ring-2
                                            bg-{{ $severityColor }}-50 text-{{ $severityColor }}-600 ring-{{ $severityColor }}-200
                                            dark:bg-{{ $severityColor }}-900/30 dark:text-{{ $severityColor }}-400 dark:ring-{{ $severityColor }}-800">
                                    {{ $student['initials'] }}
                                </div>
                            @endif
                            {{-- Severity dot --}}
                            <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full border-2 border-white dark:border-zinc-800
                                        bg-{{ $severityColor }}-500"
                                  title="{{ $severityColor === 'red' ? __('Critical') : ($severityColor === 'amber' ? __('Warning') : __('Info')) }}">
                            </span>
                        </div>

                        {{-- Student info --}}
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-zinc-900 dark:text-white truncate">{{ $student['name'] }}</p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $student['class'] }}</p>
                                </div>

                                {{-- Mini score indicators --}}
                                <div class="flex items-center gap-2 shrink-0">
                                    @if ($availableQuizCount > 0)
                                        <div class="text-center" title="{{ __('Quiz average') }}">
                                            <p class="text-[10px] uppercase tracking-wide text-zinc-400 dark:text-zinc-500 leading-none mb-0.5">{{ __('Quiz') }}</p>
                                            @if ($student['quiz_avg'] !== null)
                                                <p class="text-xs font-bold {{ $student['quiz_avg'] < 40 ? 'text-red-600 dark:text-red-400' : ($student['quiz_avg'] < 60 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400') }}">
                                                    {{ $student['quiz_avg'] }}%
                                                </p>
                                            @else
                                                <p class="text-xs text-zinc-300 dark:text-zinc-600 font-medium">&mdash;</p>
                                            @endif
                                        </div>
                                    @endif
                                    @if ($availableGameCount > 0)
                                        <div class="text-center" title="{{ __('Game average') }}">
                                            <p class="text-[10px] uppercase tracking-wide text-zinc-400 dark:text-zinc-500 leading-none mb-0.5">{{ __('Game') }}</p>
                                            @if ($student['game_avg'] !== null)
                                                <p class="text-xs font-bold {{ $student['game_avg'] < 40 ? 'text-red-600 dark:text-red-400' : ($student['game_avg'] < 60 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400') }}">
                                                    {{ $student['game_avg'] }}%
                                                </p>
                                            @else
                                                <p class="text-xs text-zinc-300 dark:text-zinc-600 font-medium">&mdash;</p>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>

                            {{-- Concern badges --}}
                            <div class="mt-1.5 flex flex-wrap gap-1">
                                @foreach ($student['concerns'] as $concern)
                                    @php
                                        $badgeClasses = match($concern['type']) {
                                            'low_quiz', 'low_game' => 'bg-red-50 text-red-700 ring-red-200 dark:bg-red-900/20 dark:text-red-400 dark:ring-red-800',
                                            'mid_quiz', 'mid_game' => 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-900/20 dark:text-amber-400 dark:ring-amber-800',
                                            'no_quiz', 'no_game' => 'bg-zinc-100 text-zinc-600 ring-zinc-200 dark:bg-zinc-700/50 dark:text-zinc-400 dark:ring-zinc-600',
                                            'missing_quiz' => 'bg-blue-50 text-blue-700 ring-blue-200 dark:bg-blue-900/20 dark:text-blue-400 dark:ring-blue-800',
                                            default => 'bg-zinc-100 text-zinc-600 ring-zinc-200 dark:bg-zinc-700/50 dark:text-zinc-400 dark:ring-zinc-600',
                                        };
                                        $badgeIcon = match($concern['type']) {
                                            'low_quiz', 'mid_quiz' => 'arrow-trending-down',
                                            'low_game', 'mid_game' => 'arrow-trending-down',
                                            'no_quiz' => 'academic-cap',
                                            'no_game' => 'puzzle-piece',
                                            'missing_quiz' => 'minus-circle',
                                            default => 'information-circle',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-md text-[11px] font-medium ring-1 ring-inset {{ $badgeClasses }}">
                                        <x-dynamic-component :component="'flux::icon.' . $badgeIcon" class="w-3 h-3" />
                                        {{ $concern['label'] }}
                                    </span>
                                @endforeach
                            </div>

                            {{-- Mini progress bars --}}
                            @if ($student['quiz_avg'] !== null || $student['game_avg'] !== null)
                                <div class="mt-2 flex items-center gap-3">
                                    @if ($availableQuizCount > 0 && $student['quiz_avg'] !== null)
                                        <div class="flex-1 flex items-center gap-1.5">
                                            <span class="text-[10px] text-zinc-400 dark:text-zinc-500 w-7 shrink-0">{{ __('Q') }}</span>
                                            <div class="flex-1 h-1 rounded-full bg-zinc-200 dark:bg-zinc-700 overflow-hidden">
                                                <div class="h-full rounded-full transition-all duration-500 {{ $student['quiz_avg'] < 40 ? 'bg-red-500' : ($student['quiz_avg'] < 60 ? 'bg-amber-500' : 'bg-emerald-500') }}"
                                                     style="width: {{ $student['quiz_avg'] }}%"></div>
                                            </div>
                                        </div>
                                    @endif
                                    @if ($availableGameCount > 0 && $student['game_avg'] !== null)
                                        <div class="flex-1 flex items-center gap-1.5">
                                            <span class="text-[10px] text-zinc-400 dark:text-zinc-500 w-7 shrink-0">{{ __('G') }}</span>
                                            <div class="flex-1 h-1 rounded-full bg-zinc-200 dark:bg-zinc-700 overflow-hidden">
                                                <div class="h-full rounded-full transition-all duration-500 {{ $student['game_avg'] < 40 ? 'bg-red-500' : ($student['game_avg'] < 60 ? 'bg-amber-500' : 'bg-emerald-500') }}"
                                                     style="width: {{ $student['game_avg'] }}%"></div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endif

                            {{-- Per-quiz/game detail (full mode only) --}}
                            @unless ($compact)
                                @if (!empty($student['quiz_details'] ?? []) || !empty($student['quiz_missed'] ?? []) || !empty($student['game_details'] ?? []) || !empty($student['game_missed'] ?? []))
                                    <div x-data="{ detail: false }" class="mt-2">
                                        <button @click="detail = !detail" type="button"
                                                class="inline-flex items-center gap-1 text-[11px] font-medium text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 transition-colors">
                                            <flux:icon.chevron-right class="w-3 h-3 transition-transform duration-200" ::class="detail && 'rotate-90'" />
                                            <span x-text="detail ? '{{ __('Hide details') }}' : '{{ __('View details') }}'"></span>
                                        </button>
                                        <div x-show="detail" x-collapse class="mt-1.5 space-y-2.5 pl-4 border-l-2 border-zinc-200 dark:border-zinc-700">
                                            @if (!empty($student['quiz_details'] ?? []) || !empty($student['quiz_missed'] ?? []))
                                                <div>
                                                    <p class="text-[10px] uppercase tracking-wide font-semibold text-zinc-400 dark:text-zinc-500 mb-1 flex items-center gap-1">
                                                        <flux:icon.academic-cap class="w-3 h-3" /> {{ __('Quizzes') }}
                                                    </p>
                                                    <div class="space-y-1">
                                                        @foreach ($student['quiz_details'] as $qd)
                                                            <div class="flex items-center justify-between gap-2 text-xs">
                                                                <span class="text-zinc-600 dark:text-zinc-300 truncate">{{ $qd['title'] }}</span>
                                                                <span class="shrink-0 ml-2 font-semibold {{ $qd['score'] < 40 ? 'text-red-600 dark:text-red-400' : ($qd['score'] < 60 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400') }}">
                                                                    {{ $qd['score'] }}%
                                                                </span>
                                                            </div>
                                                        @endforeach
                                                        @foreach ($student['quiz_missed'] as $qm)
                                                            <div class="flex items-center justify-between gap-2 text-xs">
                                                                <span class="text-zinc-600 dark:text-zinc-300 truncate">{{ $qm['title'] }}</span>
                                                                <span class="shrink-0 ml-2 text-zinc-400 dark:text-zinc-500 italic text-[11px]">{{ __('Not attempted') }}</span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                            @if (!empty($student['game_details'] ?? []) || !empty($student['game_missed'] ?? []))
                                                <div>
                                                    <p class="text-[10px] uppercase tracking-wide font-semibold text-zinc-400 dark:text-zinc-500 mb-1 flex items-center gap-1">
                                                        <flux:icon.puzzle-piece class="w-3 h-3" /> {{ __('Games') }}
                                                    </p>
                                                    <div class="space-y-1">
                                                        @foreach ($student['game_details'] as $gd)
                                                            <div class="flex items-center justify-between gap-2 text-xs">
                                                                <span class="text-zinc-600 dark:text-zinc-300 truncate">{{ $gd['title'] }}</span>
                                                                <span class="shrink-0 ml-2 font-semibold {{ $gd['score'] < 40 ? 'text-red-600 dark:text-red-400' : ($gd['score'] < 60 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400') }}">
                                                                    {{ $gd['score'] }}%
                                                                </span>
                                                            </div>
                                                        @endforeach
                                                        @foreach ($student['game_missed'] as $gm)
                                                            <div class="flex items-center justify-between gap-2 text-xs">
                                                                <span class="text-zinc-600 dark:text-zinc-300 truncate">{{ $gm['title'] }}</span>
                                                                <span class="shrink-0 ml-2 text-zinc-400 dark:text-zinc-500 italic text-[11px]">{{ __('Not played') }}</span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            @endunless
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- ── Footer summary ──────────────────────────────────── --}}
            @if ($onTrackCount > 0 && !$compact)
                <div class="px-4 py-3 sm:px-5 border-t border-zinc-100 dark:border-zinc-700/50">
                    <div class="flex items-center gap-2">
                        <flux:icon.check-circle class="w-4 h-4 text-emerald-500 shrink-0" />
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                            {{ trans_choice(':count other student is on track|:count other students are on track', $onTrackCount, ['count' => $onTrackCount]) }}
                        </p>
                    </div>
                </div>
            @endif

            {{-- ── View All link (compact mode) ───────────────────── --}}
            @if ($compact)
                <div class="px-4 py-3 sm:px-5 border-t border-zinc-100 dark:border-zinc-700/50 text-center">
                    <a href="{{ route('teacher.insights') }}" wire:navigate
                       class="inline-flex items-center gap-1.5 text-xs font-medium text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 transition-colors">
                        <flux:icon.magnifying-glass class="w-3.5 h-3.5" />
                        {{ __('View All Insights') }}
                        <flux:icon.arrow-right class="w-3.5 h-3.5" />
                    </a>
                </div>
            @endif
        </div>
    @endif
</div>
