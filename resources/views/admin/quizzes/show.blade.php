<x-layouts::app :title="$quiz->title">
    <div class="space-y-6">
        <div>
            <flux:button variant="subtle" size="sm" href="{{ route('admin.quizzes.index') }}" wire:navigate class="mb-2">
                <flux:icon name="arrow-left" class="size-4 mr-1" /> {{ __('Back to Quizzes') }}
            </flux:button>
            <div class="flex items-center gap-3">
                <h1 class="text-xl font-bold text-zinc-900 dark:text-white">{{ $quiz->title }}</h1>
                @if ($quiz->status === 'approved' && $quiz->is_published)
                    <flux:badge color="green" size="sm">{{ __('Published') }}</flux:badge>
                @elseif ($quiz->status === 'approved')
                    <flux:badge color="blue" size="sm">{{ __('Approved') }}</flux:badge>
                @elseif ($quiz->status === 'pending')
                    <flux:badge color="yellow" size="sm">{{ __('Pending') }}</flux:badge>
                @elseif ($quiz->status === 'rejected')
                    <flux:badge color="red" size="sm">{{ __('Rejected') }}</flux:badge>
                @endif
            </div>
            <p class="text-sm text-zinc-500 mt-1">
                {{ $quiz->class?->name }} &middot; {{ __('By') }} {{ $quiz->creator?->name }}
                &middot; {{ $quiz->questions->count() }} {{ __('questions') }}
                @if ($quiz->time_limit_minutes)
                    &middot; {{ $quiz->time_limit_minutes }} {{ __('min') }}
                @endif
            </p>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-3 text-center">
                <p class="text-lg font-bold text-zinc-900 dark:text-white">{{ $quiz->questions->count() }}</p>
                <p class="text-xs text-zinc-500">{{ __('Questions') }}</p>
            </div>
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-3 text-center">
                <p class="text-lg font-bold text-zinc-900 dark:text-white">{{ $quiz->time_limit_minutes ?? '-' }}</p>
                <p class="text-xs text-zinc-500">{{ __('Time Limit') }}</p>
            </div>
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-3 text-center">
                <p class="text-lg font-bold text-zinc-900 dark:text-white">{{ $quiz->passing_score }}%</p>
                <p class="text-xs text-zinc-500">{{ __('Pass Score') }}</p>
            </div>
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-3 text-center">
                <p class="text-lg font-bold text-zinc-900 dark:text-white">{{ $quiz->max_attempts }}</p>
                <p class="text-xs text-zinc-500">{{ __('Max Attempts') }}</p>
            </div>
        </div>

        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 sm:p-5">
            <h3 class="text-base font-semibold text-zinc-900 dark:text-white mb-4">{{ __('Questions') }}</h3>
            <div class="space-y-4">
                @foreach ($quiz->questions as $i => $question)
                    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-3">
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $i + 1 }}. {{ $question->question_text }}</p>
                            <flux:badge size="sm" :color="$question->type === 'multiple_choice' ? 'indigo' : ($question->type === 'true_false' ? 'purple' : 'amber')">
                                {{ str_replace('_', ' ', ucfirst($question->type)) }}
                            </flux:badge>
                        </div>
                        @if ($question->options && is_array($question->options))
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-1 mt-2">
                                @foreach ($question->options as $option)
                                    <div class="flex items-center gap-2 text-sm {{ $option === $question->correct_answer ? 'text-green-600 dark:text-green-400 font-medium' : 'text-zinc-600 dark:text-zinc-400' }}">
                                        @if ($option === $question->correct_answer)
                                            <flux:icon name="check-circle" class="size-4" />
                                        @else
                                            <flux:icon name="minus-circle" class="size-4 text-zinc-300 dark:text-zinc-600" />
                                        @endif
                                        {{ $option }}
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-green-600 dark:text-green-400 mt-1">{{ __('Answer:') }} {{ $question->correct_answer }}</p>
                        @endif
                        @if ($question->explanation)
                            <p class="text-xs text-zinc-500 italic mt-2">{{ $question->explanation }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <div class="flex items-center gap-3">
            @if ($quiz->status === 'approved' && ! $quiz->is_published)
                <form method="POST" action="{{ route('admin.quizzes.publish', $quiz) }}">
                    @csrf
                    <flux:button type="submit" variant="primary">{{ __('Publish Quiz') }}</flux:button>
                </form>
            @endif
            @if ($quiz->is_published)
                <form method="POST" action="{{ route('admin.quizzes.unpublish', $quiz) }}">
                    @csrf
                    <flux:button type="submit" variant="subtle">{{ __('Unpublish') }}</flux:button>
                </form>
                <flux:button variant="subtle" href="{{ route('admin.quizzes.results', $quiz) }}" wire:navigate>{{ __('View Results') }}</flux:button>
            @endif
        </div>
    </div>
</x-layouts::app>
