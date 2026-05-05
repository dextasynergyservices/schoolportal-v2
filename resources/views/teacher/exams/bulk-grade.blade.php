<x-layouts::app :title="__('Bulk Grade Theory')">
    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold">{{ __('Bulk Grade — :title', ['title' => $exam->title]) }}</h1>
                <p class="text-sm text-zinc-500">
                    {{ $exam->subject?->name }} · {{ $exam->class?->name }}
                    · {{ __('Grading one question across all students') }}
                </p>
            </div>
            <flux:button href="{{ route($routePrefix . '.results', $exam) }}" variant="ghost" icon="arrow-left" wire:navigate>{{ __('Back to Results') }}</flux:button>
        </div>

        @if (session('success'))
            <flux:callout variant="success" icon="check-circle">{{ session('success') }}</flux:callout>
        @endif

        {{-- Question Navigation Tabs --}}
        <div class="flex flex-wrap gap-2">
            @foreach ($theoryQuestions as $qIdx => $q)
                @php $isActive = $q->id === $currentQuestion->id; @endphp
                <a
                    href="{{ route($routePrefix . '.bulk-grade', ['exam' => $exam, 'question' => $q->id]) }}"
                    class="px-3 py-1.5 text-sm rounded-lg border transition {{ $isActive ? 'bg-blue-600 text-white border-blue-600' : 'bg-white dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 border-zinc-300 dark:border-zinc-600 hover:bg-zinc-50 dark:hover:bg-zinc-700' }}"
                    wire:navigate
                >
                    {{ __('Q:number', ['number' => $qIdx + 1]) }}
                    <span class="text-xs {{ $isActive ? 'text-blue-200' : 'text-zinc-400' }}">
                        ({{ ucfirst(str_replace('_', ' ', $q->type)) }})
                    </span>
                </a>
            @endforeach
        </div>

        {{-- Current Question --}}
        <flux:card class="p-6 border-l-4 border-l-blue-500">
            <div class="space-y-3">
                <div class="flex items-start justify-between">
                    <h3 class="font-semibold text-base">
                        {{ $currentQuestion->question_text }}
                    </h3>
                    <span class="text-sm text-zinc-400 whitespace-nowrap ml-4">{{ $currentQuestion->points }} {{ __('pts') }}</span>
                </div>

                @if ($currentQuestion->marking_guide || $currentQuestion->sample_answer)
                    <div class="bg-blue-50 dark:bg-blue-950/30 p-3 rounded-lg space-y-2">
                        @if ($currentQuestion->marking_guide)
                            <div>
                                <span class="text-xs font-semibold text-blue-700 dark:text-blue-400">{{ __('Marking Guide') }}</span>
                                <p class="text-sm text-blue-800 dark:text-blue-300 whitespace-pre-wrap">{{ $currentQuestion->marking_guide }}</p>
                            </div>
                        @endif
                        @if ($currentQuestion->sample_answer)
                            <div>
                                <span class="text-xs font-semibold text-blue-700 dark:text-blue-400">{{ __('Sample Answer') }}</span>
                                <p class="text-sm text-blue-800 dark:text-blue-300 whitespace-pre-wrap">{{ $currentQuestion->sample_answer }}</p>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </flux:card>

        {{-- Grading Form: All Students --}}
        <form action="{{ route($routePrefix . '.save-bulk-grade', $exam) }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="question_id" value="{{ $currentQuestion->id }}">

            @forelse ($attempts as $idx => $attempt)
                @php
                    $answer = $answersForQuestion->get($attempt->id);
                    $isGraded = $answer && $answer->graded_at;
                @endphp
                <flux:card class="p-4 {{ $isGraded ? 'border-l-4 border-l-green-500' : 'border-l-4 border-l-amber-400' }}">
                    <div class="space-y-3">
                        {{-- Student Info --}}
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="font-medium">{{ $attempt->student?->name }}</span>
                                <span class="text-xs text-zinc-400 ml-2">{{ $attempt->student?->username }}</span>
                            </div>
                            @if ($isGraded)
                                <flux:badge color="green" size="sm">{{ __('Graded') }} — {{ $answer->points_earned }}/{{ $currentQuestion->points }}</flux:badge>
                            @else
                                <flux:badge color="amber" size="sm">{{ __('Ungraded') }}</flux:badge>
                            @endif
                        </div>

                        {{-- Student Answer --}}
                        <div class="bg-zinc-50 dark:bg-zinc-800/50 p-3 rounded-lg">
                            @if ($answer && $answer->theory_answer)
                                <p class="text-sm whitespace-pre-wrap">{{ $answer->theory_answer }}</p>
                                @if ($currentQuestion->min_words || $currentQuestion->max_words)
                                    @php $wordCount = str_word_count($answer->theory_answer); @endphp
                                    <span class="text-xs text-zinc-400 mt-1 block">{{ __(':count words', ['count' => $wordCount]) }}</span>
                                @endif
                            @else
                                <p class="text-sm text-zinc-400 italic">{{ __('No answer provided') }}</p>
                            @endif
                        </div>

                        {{-- Grading Inputs --}}
                        @if ($answer)
                            <input type="hidden" name="grades[{{ $idx }}][answer_id]" value="{{ $answer->id }}">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <flux:label for="bulk_points_{{ $idx }}">
                                        {{ __('Points') }} <span class="text-zinc-400">({{ __('max :max', ['max' => $currentQuestion->points]) }})</span>
                                    </flux:label>
                                    <flux:input
                                        type="number"
                                        id="bulk_points_{{ $idx }}"
                                        name="grades[{{ $idx }}][points]"
                                        value="{{ old('grades.' . $idx . '.points', $answer->points_earned ?? 0) }}"
                                        min="0"
                                        max="{{ $currentQuestion->points }}"
                                        required
                                    />
                                </div>
                                <div>
                                    <flux:label for="bulk_comment_{{ $idx }}">{{ __('Feedback') }}</flux:label>
                                    <flux:input
                                        type="text"
                                        id="bulk_comment_{{ $idx }}"
                                        name="grades[{{ $idx }}][comment]"
                                        value="{{ old('grades.' . $idx . '.comment', $answer->teacher_comment ?? '') }}"
                                        placeholder="{{ __('Optional feedback...') }}"
                                    />
                                </div>
                            </div>
                        @endif
                    </div>
                </flux:card>
            @empty
                <flux:card class="p-8 text-center text-zinc-500">
                    <p>{{ __('No student submissions for this question.') }}</p>
                </flux:card>
            @endforelse

            @if ($attempts->isNotEmpty())
                <div class="flex justify-end gap-3">
                    <flux:button href="{{ route($routePrefix . '.results', $exam) }}" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
                    <flux:button type="submit" variant="primary" icon="check">
                        {{ __('Save & Continue') }}
                    </flux:button>
                </div>
            @endif
        </form>
    </div>
</x-layouts::app>
