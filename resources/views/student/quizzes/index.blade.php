<x-layouts::app :title="__('My Quizzes')">
    <div class="space-y-6">
        <h1 class="text-xl font-bold text-zinc-900 dark:text-white">{{ __('My Quizzes') }}</h1>

        {{-- Available Quizzes --}}
        @if ($available->count())
            <div>
                <h2 class="text-base font-semibold text-zinc-700 dark:text-zinc-300 mb-3">{{ __('Available Quizzes') }}</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($available as $quiz)
                        @php
                            $studentId = auth()->id();
                            $attemptsDone = $quiz->attemptsForStudent($studentId);
                            $bestAttempt = $quiz->bestAttemptForStudent($studentId);
                        @endphp
                        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
                            <h3 class="font-semibold text-zinc-900 dark:text-white">{{ $quiz->title }}</h3>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                                {{ $quiz->total_questions }} {{ __('questions') }}
                                @if ($quiz->time_limit_minutes)
                                    &middot; {{ $quiz->time_limit_minutes }} {{ __('min') }}
                                @endif
                                @if ($quiz->expires_at)
                                    &middot; {{ __('Due:') }} {{ $quiz->expires_at->format('M j') }}
                                @endif
                            </p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                                {{ __('Attempts:') }} {{ $attemptsDone }}/{{ $quiz->max_attempts }}
                                @if ($bestAttempt)
                                    &middot; {{ __('Best:') }} {{ number_format($bestAttempt->percentage, 0) }}%
                                @endif
                            </p>
                            <div class="mt-3">
                                <flux:modal.trigger name="start-quiz-{{ $quiz->id }}">
                                    <flux:button variant="primary" size="sm" icon="play">
                                        {{ $attemptsDone > 0 ? __('Retake Quiz') : __('Start Quiz') }}
                                    </flux:button>
                                </flux:modal.trigger>
                            </div>

                            <flux:modal name="start-quiz-{{ $quiz->id }}" class="md:w-[28rem]">
                                <div class="space-y-6">
                                    <div>
                                        <flux:heading size="lg">{{ $attemptsDone > 0 ? __('Retake this quiz?') : __('Start this quiz?') }}</flux:heading>
                                        <flux:text class="mt-2">
                                            <span class="font-medium text-zinc-900 dark:text-white">{{ $quiz->title }}</span>
                                        </flux:text>
                                    </div>

                                    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900 p-4 text-sm space-y-2">
                                        <div class="flex items-center gap-2 text-zinc-700 dark:text-zinc-300">
                                            <flux:icon name="question-mark-circle" class="size-4 text-zinc-400" />
                                            {{ $quiz->total_questions }} {{ __('questions') }}
                                        </div>
                                        @if ($quiz->time_limit_minutes)
                                            <div class="flex items-center gap-2 text-amber-700 dark:text-amber-400">
                                                <flux:icon name="clock" class="size-4" />
                                                {{ __(':min minute time limit — auto-submits when time is up.', ['min' => $quiz->time_limit_minutes]) }}
                                            </div>
                                        @else
                                            <div class="flex items-center gap-2 text-zinc-700 dark:text-zinc-300">
                                                <flux:icon name="clock" class="size-4 text-zinc-400" />
                                                {{ __('No time limit.') }}
                                            </div>
                                        @endif
                                        <div class="flex items-center gap-2 text-zinc-700 dark:text-zinc-300">
                                            <flux:icon name="check-badge" class="size-4 text-zinc-400" />
                                            {{ __(':score% to pass.', ['score' => $quiz->passing_score]) }}
                                        </div>
                                        <div class="flex items-center gap-2 text-zinc-700 dark:text-zinc-300">
                                            <flux:icon name="arrow-path" class="size-4 text-zinc-400" />
                                            {{ __('Attempt :n of :max.', ['n' => $attemptsDone + 1, 'max' => $quiz->max_attempts]) }}
                                        </div>
                                    </div>

                                    <form method="POST" action="{{ route('student.quizzes.start', $quiz) }}" class="flex justify-end gap-2">
                                        @csrf
                                        <flux:modal.close>
                                            <flux:button type="button" variant="ghost">{{ __('Cancel') }}</flux:button>
                                        </flux:modal.close>
                                        <flux:button type="submit" variant="primary" icon="play">
                                            {{ $attemptsDone > 0 ? __('Retake Now') : __('Start Now') }}
                                        </flux:button>
                                    </form>
                                </div>
                            </flux:modal>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Completed Quizzes --}}
        @if ($completed->count())
            <div>
                <h2 class="text-base font-semibold text-zinc-700 dark:text-zinc-300 mb-3">{{ __('Completed Quizzes') }}</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($completed as $quiz)
                        @php
                            $bestAttempt = $quiz->bestAttemptForStudent(auth()->id());
                        @endphp
                        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h3 class="font-semibold text-zinc-900 dark:text-white">{{ $quiz->title }}</h3>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                                        {{ $quiz->total_questions }} {{ __('questions') }}
                                    </p>
                                </div>
                                @if ($bestAttempt)
                                    <div class="text-right">
                                        <span class="text-lg font-bold {{ $bestAttempt->passed ? 'text-green-600' : 'text-red-600' }}">
                                            {{ number_format($bestAttempt->percentage, 0) }}%
                                        </span>
                                        <p class="text-xs {{ $bestAttempt->passed ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $bestAttempt->passed ? __('Passed') : __('Failed') }}
                                        </p>
                                    </div>
                                @endif
                            </div>
                            @if ($bestAttempt)
                                <div class="mt-3">
                                    <flux:button variant="subtle" size="sm" href="{{ route('student.quizzes.results', $bestAttempt) }}" wire:navigate>
                                        {{ __('View Results') }}
                                    </flux:button>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($available->isEmpty() && $completed->isEmpty())
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-8 text-center">
                <flux:icon name="academic-cap" class="mx-auto h-12 w-12 text-zinc-400" />
                <h3 class="mt-2 text-sm font-semibold text-zinc-900 dark:text-white">{{ __('No quizzes available') }}</h3>
                <p class="mt-1 text-sm text-zinc-500">{{ __('Your teacher has not published any quizzes for your class yet.') }}</p>
            </div>
        @endif

        @if ($quizzes->hasPages())
            <div class="mt-4">{{ $quizzes->links() }}</div>
        @endif
    </div>
</x-layouts::app>
