<x-layouts::app :title="$quiz->title">
    <div x-data="quizTaker()" class="space-y-4">
        {{-- Header with timer --}}
        <div class="sticky top-0 z-10 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-3 sm:p-4 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <h1 class="text-base sm:text-lg font-bold text-zinc-900 dark:text-white truncate">{{ $quiz->title }}</h1>
                    <p class="text-xs text-zinc-500">
                        {{ __('Question') }} <span x-text="currentIndex + 1"></span> {{ __('of') }} {{ $questions->count() }}
                    </p>
                </div>
                @if ($remainingSeconds !== null)
                    <div class="shrink-0 text-right">
                        <div :class="timeRemaining <= 60 ? 'text-red-600 animate-pulse' : timeRemaining <= 300 ? 'text-amber-600' : 'text-zinc-700 dark:text-zinc-300'"
                            class="text-lg sm:text-xl font-mono font-bold">
                            <span x-text="formatTime(timeRemaining)"></span>
                        </div>
                        <p class="text-xs text-zinc-500">{{ __('remaining') }}</p>
                    </div>
                @endif
            </div>

            {{-- Progress bar --}}
            <div class="mt-2 h-1.5 bg-zinc-200 dark:bg-zinc-700 rounded-full overflow-hidden">
                <div class="h-full bg-indigo-600 rounded-full transition-all duration-300"
                    :style="'width: ' + Math.round((answeredCount / totalQuestions) * 100) + '%'"></div>
            </div>

            {{-- Navigation dots --}}
            <div class="mt-2 flex flex-wrap gap-1.5">
                @foreach ($questions as $index => $question)
                    <button type="button" @click="goTo({{ $index }})"
                        :class="{
                            'bg-indigo-600 text-white': currentIndex === {{ $index }},
                            'bg-green-500 text-white': currentIndex !== {{ $index }} && answeredQuestions[{{ $question->id }}],
                            'bg-zinc-200 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400': currentIndex !== {{ $index }} && !answeredQuestions[{{ $question->id }}]
                        }"
                        class="size-7 rounded-full text-xs font-medium flex items-center justify-center transition">
                        {{ $index + 1 }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Questions --}}
        @foreach ($questions as $index => $question)
            <div x-show="currentIndex === {{ $index }}" x-cloak
                class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 sm:p-6">

                <div class="mb-1">
                    @if ($question->type === 'multiple_choice')
                        <flux:badge color="indigo" size="sm">{{ __('Multiple Choice') }}</flux:badge>
                    @elseif ($question->type === 'true_false')
                        <flux:badge color="purple" size="sm">{{ __('True/False') }}</flux:badge>
                    @else
                        <flux:badge color="amber" size="sm">{{ __('Fill in the Blank') }}</flux:badge>
                    @endif
                </div>

                <p class="text-base sm:text-lg text-zinc-900 dark:text-white font-medium mb-4">{{ $question->question_text }}</p>

                @if ($question->type === 'multiple_choice' || $question->type === 'true_false')
                    @php
                        $options = $quiz->shuffle_options && $question->type === 'multiple_choice'
                            ? collect($question->options)->shuffle()
                            : collect($question->options);
                    @endphp
                    <div class="space-y-2">
                        @foreach ($options as $option)
                            <label class="flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition"
                                :class="answeredQuestions[{{ $question->id }}] === '{{ addslashes($option) }}'
                                    ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-950/30'
                                    : 'border-zinc-200 dark:border-zinc-700 hover:border-indigo-300 hover:bg-zinc-50 dark:hover:bg-zinc-750'">
                                <input type="radio" name="answer_{{ $question->id }}" value="{{ $option }}"
                                    @change="selectAnswer({{ $question->id }}, '{{ addslashes($option) }}')"
                                    :checked="answeredQuestions[{{ $question->id }}] === '{{ addslashes($option) }}'"
                                    class="text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm text-zinc-800 dark:text-zinc-200">{{ $option }}</span>
                            </label>
                        @endforeach
                    </div>
                @else
                    {{-- Fill in the blank --}}
                    <input type="text" placeholder="{{ __('Type your answer...') }}"
                        :value="answeredQuestions[{{ $question->id }}] || ''"
                        @input.debounce.500ms="selectAnswer({{ $question->id }}, $event.target.value)"
                        class="w-full rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 text-sm">
                @endif
            </div>
        @endforeach

        {{-- Navigation buttons --}}
        <div class="flex items-center justify-between">
            <flux:button type="button" variant="subtle" @click="prev()" x-show="currentIndex > 0">
                <flux:icon name="arrow-left" class="size-4 mr-1" /> {{ __('Previous') }}
            </flux:button>
            <div x-show="currentIndex === 0"></div>

            <div class="flex items-center gap-2">
                <flux:button type="button" variant="subtle" @click="next()" x-show="currentIndex < totalQuestions - 1">
                    {{ __('Next') }} <flux:icon name="arrow-right" class="size-4 ml-1" />
                </flux:button>

                <div x-show="currentIndex === totalQuestions - 1 || answeredCount === totalQuestions">
                    <flux:modal.trigger name="submit-quiz-confirm">
                        <flux:button type="button" variant="primary" icon="paper-airplane">
                            {{ __('Submit Quiz') }}
                        </flux:button>
                    </flux:modal.trigger>
                </div>

                <flux:modal name="submit-quiz-confirm" class="md:w-[28rem]">
                    <div class="space-y-6">
                        <div>
                            <flux:heading size="lg">{{ __('Submit your quiz?') }}</flux:heading>
                            <flux:text class="mt-2">
                                <span x-show="answeredCount === totalQuestions">
                                    {{ __('You have answered all questions. Ready to submit?') }}
                                </span>
                                <span x-show="answeredCount < totalQuestions" class="text-amber-700 dark:text-amber-400">
                                    {{ __('You have') }}
                                    <span class="font-semibold" x-text="totalQuestions - answeredCount"></span>
                                    {{ __('unanswered question(s). Submit anyway?') }}
                                </span>
                            </flux:text>
                        </div>

                        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900 p-4 text-sm space-y-2">
                            <div class="flex items-center justify-between text-zinc-700 dark:text-zinc-300">
                                <span class="flex items-center gap-2">
                                    <flux:icon name="check-circle" class="size-4 text-green-500" />
                                    {{ __('Answered') }}
                                </span>
                                <span class="font-semibold" x-text="answeredCount + ' / ' + totalQuestions"></span>
                            </div>
                            <div class="flex items-center justify-between text-zinc-700 dark:text-zinc-300" x-show="answeredCount < totalQuestions">
                                <span class="flex items-center gap-2">
                                    <flux:icon name="exclamation-triangle" class="size-4 text-amber-500" />
                                    {{ __('Unanswered') }}
                                </span>
                                <span class="font-semibold text-amber-700 dark:text-amber-400" x-text="totalQuestions - answeredCount"></span>
                            </div>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 pt-2 border-t border-zinc-200 dark:border-zinc-700">
                                {{ __('You cannot change your answers after submitting.') }}
                            </p>
                        </div>

                        <form method="POST" action="{{ route('student.quizzes.submit', $attempt) }}" id="submitForm" class="flex justify-end gap-2">
                            @csrf
                            <flux:modal.close>
                                <flux:button type="button" variant="ghost">{{ __('Keep Reviewing') }}</flux:button>
                            </flux:modal.close>
                            <flux:button type="submit" variant="primary" icon="paper-airplane">
                                {{ __('Submit Now') }}
                            </flux:button>
                        </form>
                    </div>
                </flux:modal>
            </div>
        </div>

        {{-- Hidden forms for saving answers --}}
        @foreach ($questions as $question)
            <form method="POST" action="{{ route('student.quizzes.save-answer', $attempt) }}"
                id="answerForm_{{ $question->id }}" class="hidden">
                @csrf
                <input type="hidden" name="question_id" value="{{ $question->id }}">
                <input type="hidden" name="selected_answer" :value="answeredQuestions[{{ $question->id }}] || ''">
            </form>
        @endforeach
    </div>

    @push('scripts')
    <script>
        function quizTaker() {
            const initialAnswers = @json($answers);
            const totalQuestions = {{ $questions->count() }};
            const timeLimit = {{ $remainingSeconds ?? 'null' }};

            // Convert answers object: {question_id: selected_answer}
            const answeredQuestions = {};
            for (const [qId, answer] of Object.entries(initialAnswers)) {
                answeredQuestions[qId] = answer;
            }

            return {
                currentIndex: 0,
                totalQuestions,
                answeredQuestions,
                timeRemaining: timeLimit,
                timerInterval: null,

                get answeredCount() {
                    return Object.values(this.answeredQuestions).filter(a => a !== null && a !== '').length;
                },

                init() {
                    if (this.timeRemaining !== null) {
                        this.startTimer();
                    }
                },

                startTimer() {
                    this.timerInterval = setInterval(() => {
                        this.timeRemaining--;
                        if (this.timeRemaining <= 0) {
                            clearInterval(this.timerInterval);
                            document.getElementById('submitForm').submit();
                        }
                    }, 1000);
                },

                formatTime(seconds) {
                    if (seconds === null) return '';
                    const total = Math.max(0, Math.floor(seconds));
                    const m = Math.floor(total / 60);
                    const s = total % 60;
                    return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
                },

                selectAnswer(questionId, answer) {
                    this.answeredQuestions[questionId] = answer;
                    // Save answer via form submission in background
                    const form = document.getElementById('answerForm_' + questionId);
                    if (form) {
                        const input = form.querySelector('input[name="selected_answer"]');
                        input.value = answer;
                        fetch(form.action, {
                            method: 'POST',
                            body: new FormData(form),
                        }).catch(() => {});
                    }
                },

                goTo(index) {
                    this.currentIndex = index;
                },

                prev() {
                    if (this.currentIndex > 0) this.currentIndex--;
                },

                next() {
                    if (this.currentIndex < this.totalQuestions - 1) this.currentIndex++;
                },

                destroy() {
                    if (this.timerInterval) clearInterval(this.timerInterval);
                },
            };
        }
    </script>
    @endpush
</x-layouts::app>
