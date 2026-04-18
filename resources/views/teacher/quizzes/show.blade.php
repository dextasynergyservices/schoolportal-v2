<x-layouts::app :title="$quiz->title">
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <flux:button variant="subtle" size="sm" href="{{ route('teacher.quizzes.index') }}" wire:navigate class="mb-2">
                    <flux:icon name="arrow-left" class="size-4 mr-1" /> {{ __('Back to Quizzes') }}
                </flux:button>
                <h1 class="text-xl font-bold text-zinc-900 dark:text-white">{{ $quiz->title }}</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    {{ $quiz->class?->name }} &middot; {{ $quiz->session?->name }} / {{ $quiz->term?->name }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                @if ($quiz->status === 'approved' && $quiz->is_published)
                    <flux:badge color="green" size="sm">{{ __('Published') }}</flux:badge>
                @elseif ($quiz->status === 'approved')
                    <flux:badge color="blue" size="sm">{{ __('Approved') }}</flux:badge>
                @elseif ($quiz->status === 'pending')
                    <flux:badge color="yellow" size="sm">{{ __('Pending Approval') }}</flux:badge>
                @elseif ($quiz->status === 'rejected')
                    <flux:badge color="red" size="sm">{{ __('Rejected') }}</flux:badge>
                @else
                    <flux:badge color="zinc" size="sm">{{ __('Draft') }}</flux:badge>
                @endif
            </div>
        </div>

        {{-- Quiz Info --}}
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 sm:p-5">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                <div>
                    <span class="text-zinc-500 dark:text-zinc-400">{{ __('Questions') }}</span>
                    <p class="font-semibold text-zinc-900 dark:text-white">{{ $quiz->total_questions }}</p>
                </div>
                <div>
                    <span class="text-zinc-500 dark:text-zinc-400">{{ __('Time Limit') }}</span>
                    <p class="font-semibold text-zinc-900 dark:text-white">{{ $quiz->time_limit_minutes ? $quiz->time_limit_minutes . ' min' : __('None') }}</p>
                </div>
                <div>
                    <span class="text-zinc-500 dark:text-zinc-400">{{ __('Passing Score') }}</span>
                    <p class="font-semibold text-zinc-900 dark:text-white">{{ $quiz->passing_score }}%</p>
                </div>
                <div>
                    <span class="text-zinc-500 dark:text-zinc-400">{{ __('Max Attempts') }}</span>
                    <p class="font-semibold text-zinc-900 dark:text-white">{{ $quiz->max_attempts }}</p>
                </div>
            </div>
            @if ($quiz->description)
                <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-300">{{ $quiz->description }}</p>
            @endif
        </div>

        {{-- Questions Preview --}}
        <div class="space-y-4">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Questions') }}</h2>

            @foreach ($quiz->questions as $index => $question)
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <span class="text-sm font-semibold text-zinc-900 dark:text-white">
                            {{ __('Q:num', ['num' => $index + 1]) }}.
                            @if ($question->type === 'multiple_choice')
                                <flux:badge color="indigo" size="sm">{{ __('Multiple Choice') }}</flux:badge>
                            @elseif ($question->type === 'true_false')
                                <flux:badge color="purple" size="sm">{{ __('True/False') }}</flux:badge>
                            @else
                                <flux:badge color="amber" size="sm">{{ __('Fill in Blank') }}</flux:badge>
                            @endif
                        </span>
                    </div>

                    <p class="text-sm text-zinc-800 dark:text-zinc-200 mb-3">{{ $question->question_text }}</p>

                    @if ($question->type === 'multiple_choice' || $question->type === 'true_false')
                        <div class="space-y-1.5">
                            @foreach ($question->options as $option)
                                <div class="flex items-center gap-2 text-sm {{ $option === $question->correct_answer ? 'text-green-700 dark:text-green-400 font-medium' : 'text-zinc-600 dark:text-zinc-400' }}">
                                    @if ($option === $question->correct_answer)
                                        <flux:icon name="check-circle" class="size-4 text-green-600 shrink-0" />
                                    @else
                                        <span class="size-4 shrink-0 rounded-full border border-zinc-300 dark:border-zinc-600 inline-block"></span>
                                    @endif
                                    {{ $option }}
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-green-700 dark:text-green-400 font-medium">
                            {{ __('Answer:') }} {{ $question->correct_answer }}
                        </p>
                    @endif

                    @if ($question->explanation)
                        <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400 italic">
                            {{ __('Explanation:') }} {{ $question->explanation }}
                        </p>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Actions --}}
        <div class="flex flex-wrap items-center gap-3">
            @if (in_array($quiz->status, ['draft', 'pending', 'rejected']))
                <flux:button variant="primary" size="sm" href="{{ route('teacher.quizzes.edit', $quiz) }}" wire:navigate>
                    {{ __('Edit Quiz') }}
                </flux:button>
            @endif
            @if ($quiz->status === 'approved' || ($quiz->status === 'approved' && $quiz->is_published))
                <flux:button variant="filled" size="sm" href="{{ route('teacher.quizzes.results', $quiz) }}" wire:navigate>
                    {{ __('View Results') }}
                </flux:button>
            @endif
        </div>
    </div>
</x-layouts::app>
