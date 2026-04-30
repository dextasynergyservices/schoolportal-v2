<x-layouts::app :title="$exam->title">
    <div class="space-y-6">
        <div>
            <flux:button variant="subtle" size="sm" href="{{ route($routePrefix . '.index') }}" wire:navigate class="mb-2">
                <flux:icon name="arrow-left" class="size-4 mr-1" /> {{ __('Back to :label', ['label' => Str::plural($label)]) }}
            </flux:button>
        </div>

        {{-- Exam Info Card --}}
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div>
                    <h1 class="text-xl font-bold text-zinc-900 dark:text-white">{{ $exam->title }}</h1>
                    @if ($exam->subject)
                        <p class="text-sm text-indigo-600 dark:text-indigo-400 font-medium mt-1">{{ $exam->subject->name }}</p>
                    @endif
                </div>
                @php
                    $typeColors = ['assessment' => 'sky', 'assignment' => 'amber', 'exam' => 'indigo'];
                @endphp
                <flux:badge :color="$typeColors[$exam->category] ?? 'zinc'" size="sm">{{ $label }}</flux:badge>
            </div>

            @if ($exam->description)
                <div class="mt-4 text-sm text-zinc-600 dark:text-zinc-400">
                    {{ $exam->description }}
                </div>
            @endif

            {{-- Exam Details Grid --}}
            <div class="mt-6 grid grid-cols-2 sm:grid-cols-3 gap-4">
                <div class="rounded-lg bg-zinc-50 dark:bg-zinc-900 p-3 text-center">
                    <div class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $exam->questions()->count() }}</div>
                    <div class="text-xs text-zinc-500">{{ __('Questions') }}</div>
                </div>
                <div class="rounded-lg bg-zinc-50 dark:bg-zinc-900 p-3 text-center">
                    <div class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $totalPoints }}</div>
                    <div class="text-xs text-zinc-500">{{ __('Total Points') }}</div>
                </div>
                <div class="rounded-lg bg-zinc-50 dark:bg-zinc-900 p-3 text-center">
                    <div class="text-2xl font-bold text-zinc-900 dark:text-white">
                        {{ $exam->time_limit_minutes ? $exam->time_limit_minutes . 'm' : '∞' }}
                    </div>
                    <div class="text-xs text-zinc-500">{{ __('Time Limit') }}</div>
                </div>
                <div class="rounded-lg bg-zinc-50 dark:bg-zinc-900 p-3 text-center">
                    <div class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $exam->passing_score }}%</div>
                    <div class="text-xs text-zinc-500">{{ __('Pass Mark') }}</div>
                </div>
                <div class="rounded-lg bg-zinc-50 dark:bg-zinc-900 p-3 text-center">
                    <div class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $completedAttempts }}/{{ $exam->max_attempts }}</div>
                    <div class="text-xs text-zinc-500">{{ __('Attempts Used') }}</div>
                </div>
                @if ($exam->available_until)
                    <div class="rounded-lg bg-zinc-50 dark:bg-zinc-900 p-3 text-center">
                        <div class="text-sm font-bold text-zinc-900 dark:text-white">{{ $exam->available_until->format('M j, g:i A') }}</div>
                        <div class="text-xs text-zinc-500">{{ __('Due Date') }}</div>
                    </div>
                @endif
            </div>

            {{-- Question Type Breakdown --}}
            @if (count($questionTypeCounts) > 1)
                <div class="mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                    <h3 class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">{{ __('Question Types') }}</h3>
                    <div class="flex flex-wrap gap-2">
                        @php
                            $typeBadgeColors = [
                                'multiple_choice' => 'indigo',
                                'true_false' => 'purple',
                                'fill_blank' => 'amber',
                                'short_answer' => 'cyan',
                                'theory' => 'emerald',
                                'matching' => 'pink',
                            ];
                            $typeNames = [
                                'multiple_choice' => 'Multiple Choice',
                                'true_false' => 'True/False',
                                'fill_blank' => 'Fill in the Blank',
                                'short_answer' => 'Short Answer',
                                'theory' => 'Theory',
                                'matching' => 'Matching',
                            ];
                        @endphp
                        @foreach ($questionTypeCounts as $type => $count)
                            <flux:badge :color="$typeBadgeColors[$type] ?? 'zinc'" size="sm">
                                {{ $typeNames[$type] ?? ucfirst($type) }} ({{ $count }})
                            </flux:badge>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- Rules & Instructions --}}
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <h2 class="text-base font-semibold text-zinc-900 dark:text-white mb-3">{{ __('Rules & Instructions') }}</h2>
            <div class="space-y-2 text-sm text-zinc-600 dark:text-zinc-400">
                @if ($exam->time_limit_minutes)
                    <div class="flex items-start gap-2">
                        <flux:icon name="clock" class="size-4 text-amber-500 shrink-0 mt-0.5" />
                        <span>{{ __('You have :min minutes to complete this :label. It will auto-submit when time expires.', ['min' => $exam->time_limit_minutes, 'label' => Str::lower($label)]) }}</span>
                    </div>
                @endif
                @if ($exam->max_tab_switches)
                    <div class="flex items-start gap-2">
                        <flux:icon name="eye" class="size-4 text-red-500 shrink-0 mt-0.5" />
                        <span>{{ __('Tab switching is monitored. You are allowed :n tab switches. Exceeding this will auto-submit your :label.', ['n' => $exam->max_tab_switches, 'label' => Str::lower($label)]) }}</span>
                    </div>
                @endif
                <div class="flex items-start gap-2">
                    <flux:icon name="document-text" class="size-4 text-zinc-400 shrink-0 mt-0.5" />
                    <span>{{ __('You can navigate between questions freely and change your answers before submitting.') }}</span>
                </div>
                <div class="flex items-start gap-2">
                    <flux:icon name="cloud-arrow-up" class="size-4 text-zinc-400 shrink-0 mt-0.5" />
                    <span>{{ __('Your answers are auto-saved as you go.') }}</span>
                </div>
                @if ($exam->shuffle_questions)
                    <div class="flex items-start gap-2">
                        <flux:icon name="arrows-right-left" class="size-4 text-zinc-400 shrink-0 mt-0.5" />
                        <span>{{ __('Questions will appear in a random order.') }}</span>
                    </div>
                @endif
                @if ($exam->show_correct_answers)
                    <div class="flex items-start gap-2">
                        <flux:icon name="check-badge" class="size-4 text-green-500 shrink-0 mt-0.5" />
                        <span>{{ __('Correct answers will be shown after submission.') }}</span>
                    </div>
                @endif
                @if ($hasTheoryQuestions)
                    <div class="flex items-start gap-2">
                        <flux:icon name="pencil-square" class="size-4 text-indigo-500 shrink-0 mt-0.5" />
                        <span>{{ __('This :label contains theory/essay questions that will be graded manually by your teacher.', ['label' => Str::lower($label)]) }}</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Previous Attempts --}}
        @if ($previousAttempts->count())
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                <h2 class="text-base font-semibold text-zinc-900 dark:text-white mb-3">{{ __('Previous Attempts') }}</h2>
                <div class="space-y-2">
                    @foreach ($previousAttempts as $prevAttempt)
                        <div class="flex items-center justify-between p-3 rounded-lg bg-zinc-50 dark:bg-zinc-900">
                            <div>
                                <span class="text-sm font-medium text-zinc-900 dark:text-white">
                                    {{ __('Attempt :n', ['n' => $prevAttempt->attempt_number]) }}
                                </span>
                                <span class="text-xs text-zinc-500 ml-2">
                                    {{ $prevAttempt->submitted_at?->diffForHumans() ?? $prevAttempt->started_at->diffForHumans() }}
                                </span>
                            </div>
                            <div class="flex items-center gap-3">
                                @if ($prevAttempt->status === 'grading')
                                    <flux:badge color="amber" size="sm">{{ __('Grading') }}</flux:badge>
                                @elseif ($prevAttempt->percentage !== null)
                                    <span class="text-sm font-bold {{ $prevAttempt->passed ? 'text-green-600' : 'text-red-600' }}">
                                        {{ number_format($prevAttempt->percentage, 0) }}%
                                    </span>
                                @endif
                                <flux:button variant="subtle" size="sm" href="{{ route($routePrefix . '.results', $prevAttempt) }}" wire:navigate>
                                    {{ __('View') }}
                                </flux:button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Start / Resume Button --}}
        <div class="flex justify-center">
            @if ($canAttempt)
                @if ($inProgressAttempt)
                    <form method="POST" action="{{ route($routePrefix . '.start', $exam) }}">
                        @csrf
                        <flux:button type="submit" variant="primary" icon="play">
                            {{ __('Resume :label', ['label' => $label]) }}
                        </flux:button>
                    </form>
                @else
                    <flux:modal.trigger name="start-exam-confirm">
                        <flux:button variant="primary" icon="play">
                            {{ $completedAttempts > 0 ? __('Retake :label', ['label' => $label]) : __('Start :label', ['label' => $label]) }}
                        </flux:button>
                    </flux:modal.trigger>

                    <flux:modal name="start-exam-confirm" class="md:w-[28rem]">
                        <div class="space-y-6">
                            <div>
                                <flux:heading size="lg">{{ $completedAttempts > 0 ? __('Retake this :label?', ['label' => Str::lower($label)]) : __('Start this :label?', ['label' => Str::lower($label)]) }}</flux:heading>
                                <flux:text class="mt-2">
                                    <span class="font-medium text-zinc-900 dark:text-white">{{ $exam->title }}</span>
                                </flux:text>
                            </div>

                            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900 p-4 text-sm space-y-2">
                                <div class="flex items-center gap-2 text-zinc-700 dark:text-zinc-300">
                                    <flux:icon name="question-mark-circle" class="size-4 text-zinc-400" />
                                    {{ $exam->questions()->count() }} {{ __('questions') }} &middot; {{ $totalPoints }} {{ __('points') }}
                                </div>
                                @if ($exam->time_limit_minutes)
                                    <div class="flex items-center gap-2 text-amber-700 dark:text-amber-400">
                                        <flux:icon name="clock" class="size-4" />
                                        {{ __(':min minute time limit — auto-submits when time is up.', ['min' => $exam->time_limit_minutes]) }}
                                    </div>
                                @else
                                    <div class="flex items-center gap-2 text-zinc-700 dark:text-zinc-300">
                                        <flux:icon name="clock" class="size-4 text-zinc-400" />
                                        {{ __('No time limit.') }}
                                    </div>
                                @endif
                                <div class="flex items-center gap-2 text-zinc-700 dark:text-zinc-300">
                                    <flux:icon name="check-badge" class="size-4 text-zinc-400" />
                                    {{ __(':score% to pass.', ['score' => $exam->passing_score]) }}
                                </div>
                                <div class="flex items-center gap-2 text-zinc-700 dark:text-zinc-300">
                                    <flux:icon name="arrow-path" class="size-4 text-zinc-400" />
                                    {{ __('Attempt :n of :max.', ['n' => $completedAttempts + 1, 'max' => $exam->max_attempts]) }}
                                </div>
                                @if ($exam->max_tab_switches)
                                    <div class="flex items-center gap-2 text-red-700 dark:text-red-400">
                                        <flux:icon name="eye" class="size-4" />
                                        {{ __('Tab switching limited to :n switches.', ['n' => $exam->max_tab_switches]) }}
                                    </div>
                                @endif
                                @if ($exam->prevent_copy_paste)
                                    <div class="flex items-center gap-2 text-red-700 dark:text-red-400">
                                        <flux:icon name="shield-check" class="size-4" />
                                        {{ __('Copy & paste is disabled during this :label.', ['label' => Str::lower($label)]) }}
                                    </div>
                                @endif
                                @if ($exam->prevent_tab_switch)
                                    <div class="flex items-center gap-2 text-red-700 dark:text-red-400">
                                        <flux:icon name="eye-slash" class="size-4" />
                                        {{ __('Leaving this page will be tracked and may auto-submit your :label.', ['label' => Str::lower($label)]) }}
                                    </div>
                                @endif
                            </div>

                            <form method="POST" action="{{ route($routePrefix . '.start', $exam) }}" class="flex justify-end gap-2">
                                @csrf
                                <flux:modal.close>
                                    <flux:button type="button" variant="ghost">{{ __('Cancel') }}</flux:button>
                                </flux:modal.close>
                                <flux:button type="submit" variant="primary" icon="play">
                                    {{ $completedAttempts > 0 ? __('Retake Now') : __('Start Now') }}
                                </flux:button>
                            </form>
                        </div>
                    </flux:modal>
                @endif
            @else
                <div class="text-center text-sm text-zinc-500 dark:text-zinc-400">
                    <flux:icon name="lock-closed" class="mx-auto h-8 w-8 text-zinc-400 mb-2" />
                    <p>{{ __('You have used all :max attempts for this :label.', ['max' => $exam->max_attempts, 'label' => Str::lower($label)]) }}</p>
                </div>
            @endif
        </div>
    </div>
</x-layouts::app>
