<x-layouts::app :title="__('Grade :student', ['student' => $attempt->student->name])">
    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold">{{ __('Grade Theory Answers') }}</h1>
                <p class="text-sm text-zinc-500">
                    {{ $exam->title }} · {{ $attempt->student->name }} ({{ $attempt->student->username }})
                </p>
            </div>
            <flux:button href="{{ route($routePrefix . '.results', $exam) }}" variant="ghost" icon="arrow-left" wire:navigate>{{ __('Back to Results') }}</flux:button>
        </div>

        @if (session('success'))
            <flux:callout variant="success" icon="check-circle">{{ session('success') }}</flux:callout>
        @endif

        {{-- Attempt Summary --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <flux:card class="p-4">
                <span class="text-xs text-zinc-500 block">{{ __('Status') }}</span>
                @php
                    $statusMap = [
                        'grading' => ['color' => 'amber', 'label' => 'Pending Grading'],
                        'graded' => ['color' => 'blue', 'label' => 'Graded'],
                        'submitted' => ['color' => 'green', 'label' => 'Submitted'],
                        'timed_out' => ['color' => 'amber', 'label' => 'Timed Out'],
                    ];
                    $s = $statusMap[$attempt->status] ?? ['color' => 'zinc', 'label' => ucfirst($attempt->status)];
                @endphp
                <flux:badge :color="$s['color']" size="sm" class="mt-1">{{ __($s['label']) }}</flux:badge>
            </flux:card>
            <flux:card class="p-4">
                <span class="text-xs text-zinc-500 block">{{ __('Objective Score') }}</span>
                <span class="font-semibold">{{ $objectiveScore }} / {{ $objectiveTotal }}</span>
            </flux:card>
            <flux:card class="p-4">
                <span class="text-xs text-zinc-500 block">{{ __('Submitted') }}</span>
                <span class="font-semibold text-sm">{{ $attempt->submitted_at?->format('M d, Y H:i') ?? '—' }}</span>
            </flux:card>
            <flux:card class="p-4">
                <span class="text-xs text-zinc-500 block">{{ __('Time Spent') }}</span>
                <span class="font-semibold">
                    @if ($attempt->time_spent_seconds)
                        {{ floor($attempt->time_spent_seconds / 60) }}m {{ $attempt->time_spent_seconds % 60 }}s
                    @else
                        —
                    @endif
                </span>
            </flux:card>
        </div>

        {{-- Grading Form --}}
        <form action="{{ route($routePrefix . '.save-grade', [$exam, $attempt]) }}" method="POST" class="space-y-6">
            @csrf

            @foreach ($theoryQuestions as $idx => $question)
                @php
                    $answer = $answers->get($question->id);
                    $isGraded = $answer && $answer->graded_at;
                @endphp
                <flux:card class="p-6 {{ $isGraded ? 'border-l-4 border-l-green-500' : 'border-l-4 border-l-amber-400' }}">
                    <div class="space-y-4">
                        {{-- Question --}}
                        <div>
                            <div class="flex items-start justify-between">
                                <h3 class="font-semibold text-base">
                                    {{ __('Q:number', ['number' => $idx + 1]) }}
                                    <flux:badge size="sm" color="zinc" class="ml-1">{{ ucfirst(str_replace('_', ' ', $question->type)) }}</flux:badge>
                                    <span class="text-zinc-400 font-normal text-sm ml-2">({{ $question->points }} {{ __('pts') }})</span>
                                </h3>
                                @if ($isGraded)
                                    <flux:badge color="green" size="sm">{{ __('Graded') }}</flux:badge>
                                @else
                                    <flux:badge color="amber" size="sm">{{ __('Ungraded') }}</flux:badge>
                                @endif
                            </div>
                            <p class="mt-2 text-sm whitespace-pre-wrap">{{ $question->question_text }}</p>
                        </div>

                        {{-- Marking Guide / Sample Answer --}}
                        @if ($question->marking_guide || $question->sample_answer)
                            <div class="bg-blue-50 dark:bg-blue-950/30 p-3 rounded-lg space-y-2">
                                @if ($question->marking_guide)
                                    <div>
                                        <span class="text-xs font-semibold text-blue-700 dark:text-blue-400">{{ __('Marking Guide') }}</span>
                                        <p class="text-sm text-blue-800 dark:text-blue-300 whitespace-pre-wrap">{{ $question->marking_guide }}</p>
                                    </div>
                                @endif
                                @if ($question->sample_answer)
                                    <div>
                                        <span class="text-xs font-semibold text-blue-700 dark:text-blue-400">{{ __('Sample Answer') }}</span>
                                        <p class="text-sm text-blue-800 dark:text-blue-300 whitespace-pre-wrap">{{ $question->sample_answer }}</p>
                                    </div>
                                @endif
                            </div>
                        @endif

                        {{-- Student's Answer --}}
                        <div class="bg-zinc-50 dark:bg-zinc-800/50 p-3 rounded-lg">
                            <span class="text-xs font-semibold text-zinc-500 block mb-1">{{ __("Student's Answer") }}</span>
                            @if ($answer && $answer->theory_answer)
                                <p class="text-sm whitespace-pre-wrap">{{ $answer->theory_answer }}</p>
                                @if ($question->min_words || $question->max_words)
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

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <flux:label for="points_{{ $idx }}">
                                        {{ __('Points') }} <span class="text-zinc-400">({{ __('max :max', ['max' => $question->points]) }})</span>
                                    </flux:label>
                                    <flux:input
                                        type="number"
                                        id="points_{{ $idx }}"
                                        name="grades[{{ $idx }}][points]"
                                        value="{{ old('grades.' . $idx . '.points', $answer->points_earned ?? 0) }}"
                                        min="0"
                                        max="{{ $question->points }}"
                                        required
                                    />
                                    @error("grades.{$idx}.points")
                                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <flux:label for="comment_{{ $idx }}">{{ __('Feedback (optional)') }}</flux:label>
                                    <flux:textarea
                                        id="comment_{{ $idx }}"
                                        name="grades[{{ $idx }}][comment]"
                                        rows="2"
                                        placeholder="{{ __('Optional feedback for student...') }}"
                                    >{{ old('grades.' . $idx . '.comment', $answer->teacher_comment ?? '') }}</flux:textarea>
                                </div>
                            </div>
                        @else
                            <p class="text-sm text-zinc-400 italic">{{ __('Student did not answer this question — no grading needed.') }}</p>
                        @endif
                    </div>
                </flux:card>
            @endforeach

            <div class="flex justify-end gap-3">
                <flux:button href="{{ route($routePrefix . '.results', $exam) }}" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary" icon="check">{{ __('Save Grades') }}</flux:button>
            </div>
        </form>
    </div>
</x-layouts::app>
