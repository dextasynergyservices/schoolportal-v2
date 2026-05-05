<x-layouts::app :title="__(':label Preview: :title', ['label' => $label, 'title' => $exam->title])">
    {{-- Preview banner (always visible, sticky at very top) --}}
    <div class="sticky top-0 z-20 bg-amber-500 text-amber-950 px-4 py-2 flex items-center justify-between text-sm font-medium shadow-md">
        <div class="flex items-center gap-2">
            <flux:icon name="eye" class="size-4 shrink-0" />
            <span>{{ __('Preview Mode — This is exactly how students will see this :label.', ['label' => Str::lower($label)]) }}</span>
        </div>
        <flux:button
            variant="ghost"
            size="sm"
            href="{{ route($routePrefix . '.show', $exam) }}"
            wire:navigate
            class="shrink-0 !text-amber-950 hover:!bg-amber-600/30">
            {{ __('Exit Preview') }}
        </flux:button>
    </div>

    <div x-data="examPreview()" class="space-y-4 mt-4">

        {{-- Sticky Header (same layout as the real take view) --}}
        <div class="sticky top-10 z-10 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-3 sm:p-4 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <h1 class="text-base sm:text-lg font-bold text-zinc-900 dark:text-white truncate">{{ $exam->title }}</h1>
                    <p class="text-xs text-zinc-500">
                        {{ __('Question') }} <span x-text="currentIndex + 1"></span> {{ __('of') }} {{ $questions->count() }}
                    </p>
                </div>
                {{-- Static time limit display --}}
                @if ($exam->time_limit_minutes)
                    <div class="shrink-0 text-right">
                        <div class="text-lg sm:text-xl font-mono font-bold text-zinc-700 dark:text-zinc-300">
                            {{ $exam->time_limit_minutes < 60
                                ? $exam->time_limit_minutes . ':00'
                                : floor($exam->time_limit_minutes / 60) . ':' . str_pad($exam->time_limit_minutes % 60, 2, '0', STR_PAD_LEFT) . ':00'
                            }}
                        </div>
                        <p class="text-xs text-zinc-500">{{ __('time limit') }}</p>
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
                            'ring-2 ring-indigo-500 bg-indigo-600 text-white': currentIndex === {{ $index }},
                            'bg-green-500 text-white': currentIndex !== {{ $index }} && answeredQuestions[{{ $question->id }}],
                            'bg-zinc-200 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400': currentIndex !== {{ $index }} && !answeredQuestions[{{ $question->id }}]
                        }"
                        class="relative size-7 rounded-full text-xs font-medium flex items-center justify-center transition">
                        {{ $index + 1 }}
                        <span x-show="flaggedQuestions[{{ $question->id }}]" x-cloak
                            class="absolute -top-1 -right-1 size-3 bg-orange-500 rounded-full border border-white dark:border-zinc-800"></span>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Questions --}}
        @foreach ($questions as $index => $question)
            <div x-show="currentIndex === {{ $index }}" x-cloak
                class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 sm:p-6">

                {{-- Question type badge & flag button --}}
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        @php
                            $badgeColors = [
                                'multiple_choice' => 'indigo',
                                'true_false' => 'purple',
                                'fill_blank' => 'amber',
                                'short_answer' => 'cyan',
                                'theory' => 'emerald',
                                'matching' => 'pink',
                            ];
                            $typeLabels = [
                                'multiple_choice' => 'Multiple Choice',
                                'true_false' => 'True/False',
                                'fill_blank' => 'Fill in the Blank',
                                'short_answer' => 'Short Answer',
                                'theory' => 'Theory',
                                'matching' => 'Matching',
                            ];
                        @endphp
                        <flux:badge :color="$badgeColors[$question->type] ?? 'zinc'" size="sm">
                            {{ $typeLabels[$question->type] ?? ucfirst($question->type) }}
                        </flux:badge>
                        @if ($question->section_label)
                            <span class="text-xs text-zinc-400">{{ $question->section_label }}</span>
                        @endif
                        <span class="text-xs text-zinc-400">{{ $question->points }} {{ Str::plural('pt', $question->points) }}</span>
                    </div>
                    {{-- Flag button (works locally in preview) --}}
                    <button type="button" @click="toggleFlag({{ $question->id }})"
                        :class="flaggedQuestions[{{ $question->id }}] ? 'text-orange-500' : 'text-zinc-400 hover:text-orange-400'"
                        class="transition" :title="flaggedQuestions[{{ $question->id }}] ? '{{ __('Remove flag') }}' : '{{ __('Flag for review') }}'">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5" :fill="flaggedQuestions[{{ $question->id }}] ? 'currentColor' : 'none'" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0 2.77-.693a9 9 0 0 1 6.208.682l.108.054a9 9 0 0 0 6.086.71l3.114-.732a48.524 48.524 0 0 1-.005-10.499l-3.11.732a9 9 0 0 1-6.085-.711l-.108-.054a9 9 0 0 0-6.208-.682L3 4.5M3 15V4.5" />
                        </svg>
                    </button>
                </div>

                {{-- Question text --}}
                <p class="text-base sm:text-lg text-zinc-900 dark:text-white font-medium mb-4">{{ $question->question_text }}</p>

                {{-- Question image --}}
                @if ($question->question_image_url)
                    <div class="mb-4">
                        <img src="{{ $question->question_image_url }}" alt="{{ __('Question image') }}"
                            class="max-h-64 rounded-lg object-contain">
                    </div>
                @endif

                {{-- Answer input based on type (identical to take view, answers saved locally only) --}}
                @if ($question->type === 'multiple_choice')
                    @php
                        $options = $exam->shuffle_options
                            ? collect($question->options)->shuffle(crc32((string) ($exam->id . '-' . $question->id)))
                            : collect($question->options);
                    @endphp
                    <div class="space-y-2">
                        @foreach ($options as $option)
                            <label class="flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition"
                                :class="answeredQuestions[{{ $question->id }}] === {{ json_encode($option) }}
                                    ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-950/30'
                                    : 'border-zinc-200 dark:border-zinc-700 hover:border-indigo-300 hover:bg-zinc-50 dark:hover:bg-zinc-700/70'">
                                <input type="radio" name="answer_{{ $question->id }}" :value="{{ json_encode($option) }}"
                                    @change="selectAnswer({{ $question->id }}, {{ json_encode($option) }})"
                                    :checked="answeredQuestions[{{ $question->id }}] === {{ json_encode($option) }}"
                                    class="text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm text-zinc-800 dark:text-zinc-200">{{ $option }}</span>
                            </label>
                        @endforeach
                    </div>

                @elseif ($question->type === 'true_false')
                    <div class="space-y-2">
                        @foreach (['True', 'False'] as $option)
                            <label class="flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition"
                                :class="answeredQuestions[{{ $question->id }}] === '{{ $option }}'
                                    ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-950/30'
                                    : 'border-zinc-200 dark:border-zinc-700 hover:border-indigo-300 hover:bg-zinc-50 dark:hover:bg-zinc-700/70'">
                                <input type="radio" name="answer_{{ $question->id }}" value="{{ $option }}"
                                    @change="selectAnswer({{ $question->id }}, '{{ $option }}')"
                                    :checked="answeredQuestions[{{ $question->id }}] === '{{ $option }}'"
                                    class="text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm text-zinc-800 dark:text-zinc-200">{{ __($option) }}</span>
                            </label>
                        @endforeach
                    </div>

                @elseif ($question->type === 'fill_blank')
                    <input type="text" placeholder="{{ __('Type your answer...') }}"
                        :value="answeredQuestions[{{ $question->id }}] || ''"
                        @input.debounce.300ms="selectAnswer({{ $question->id }}, $event.target.value)"
                        class="w-full rounded-lg border border-zinc-200 border-b-zinc-300/80 dark:border-white/10 bg-white dark:bg-white/10 shadow-xs px-3 py-2 text-sm text-zinc-700 dark:text-zinc-300 placeholder-zinc-400 dark:placeholder-zinc-500">

                @elseif ($question->type === 'short_answer')
                    <div>
                        <textarea rows="3" placeholder="{{ __('Type your short answer...') }}"
                            @input.debounce.300ms="selectAnswer({{ $question->id }}, $event.target.value)"
                            class="w-full rounded-lg border border-zinc-200 border-b-zinc-300/80 dark:border-white/10 bg-white dark:bg-white/10 shadow-xs px-3 py-2 text-sm text-zinc-700 dark:text-zinc-300 placeholder-zinc-400 dark:placeholder-zinc-500"></textarea>
                        @if ($question->min_words || $question->max_words)
                            <p class="text-xs text-zinc-500 mt-1">
                                @if ($question->min_words && $question->max_words)
                                    {{ __(':min – :max words', ['min' => $question->min_words, 'max' => $question->max_words]) }}
                                @elseif ($question->min_words)
                                    {{ __('Minimum :min words', ['min' => $question->min_words]) }}
                                @else
                                    {{ __('Maximum :max words', ['max' => $question->max_words]) }}
                                @endif
                            </p>
                        @endif
                    </div>

                @elseif ($question->type === 'theory')
                    <div>
                        <textarea rows="8" placeholder="{{ __('Write your answer here...') }}"
                            @input.debounce.300ms="selectAnswer({{ $question->id }}, $event.target.value)"
                            class="w-full rounded-lg border border-zinc-200 border-b-zinc-300/80 dark:border-white/10 bg-white dark:bg-white/10 shadow-xs px-3 py-2 text-sm text-zinc-700 dark:text-zinc-300 placeholder-zinc-400 dark:placeholder-zinc-500"></textarea>
                        @if ($question->min_words || $question->max_words)
                            <p class="text-xs text-zinc-500 mt-1">
                                @if ($question->min_words && $question->max_words)
                                    {{ __(':min – :max words', ['min' => $question->min_words, 'max' => $question->max_words]) }}
                                @elseif ($question->min_words)
                                    {{ __('Minimum :min words', ['min' => $question->min_words]) }}
                                @else
                                    {{ __('Maximum :max words', ['max' => $question->max_words]) }}
                                @endif
                            </p>
                        @endif
                    </div>

                @elseif ($question->type === 'matching')
                    @php
                        $options = $question->options ?? [];
                        $leftItems = collect($options)->pluck('left')->toArray();
                        $rightItems = collect($options)->pluck('right')->shuffle()->toArray();
                    @endphp
                    <div x-data="matchingPreview({{ $question->id }}, {{ json_encode($leftItems) }}, {{ json_encode($rightItems) }})"
                        class="space-y-3">
                        <p class="text-xs text-zinc-500">{{ __('Select an answer from the dropdown for each item.') }}</p>
                        @foreach ($leftItems as $i => $leftItem)
                            <div class="flex items-center gap-3">
                                <span class="flex-1 p-2 rounded bg-zinc-50 dark:bg-zinc-900 text-sm text-zinc-800 dark:text-zinc-200">
                                    {{ $leftItem }}
                                </span>
                                <span class="text-zinc-400">→</span>
                                <select @change="matchItem({{ $i }}, $event.target.value)"
                                    class="flex-1 rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/10 shadow-xs px-2 py-1 text-sm text-zinc-700 dark:text-zinc-300">
                                    <option value="">{{ __('Select...') }}</option>
                                    @foreach ($rightItems as $rightItem)
                                        <option value="{{ $rightItem }}">{{ $rightItem }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endforeach
                    </div>
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

                {{-- On last question show a disabled placeholder to mirror where the real submit button would be --}}
                <div x-show="currentIndex === totalQuestions - 1 || answeredCount === totalQuestions">
                    <flux:button type="button" variant="primary" icon="eye" disabled class="opacity-60 cursor-not-allowed">
                        {{ __('Submit (Preview Only)') }}
                    </flux:button>
                </div>
            </div>
        </div>

        {{-- Preview footer note --}}
        <div class="rounded-lg border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/20 p-4 text-sm text-amber-800 dark:text-amber-400">
            <div class="flex items-start gap-2">
                <flux:icon name="information-circle" class="size-5 shrink-0 mt-0.5" />
                <div class="space-y-1">
                    <p class="font-medium">{{ __('Preview Mode — No data is saved') }}</p>
                    <ul class="text-xs space-y-0.5 list-disc list-inside">
                        <li>{{ __('Answers entered here are not recorded') }}</li>
                        <li>{{ __('The submit button is disabled in preview') }}</li>
                        <li>{{ __('Anti-cheat restrictions are not active in preview') }}</li>
                        @if ($exam->time_limit_minutes)
                            <li>{{ __('The timer shows the configured limit (:min min) but does not count down', ['min' => $exam->time_limit_minutes]) }}</li>
                        @endif
                        @if ($exam->shuffle_questions)
                            <li>{{ __('Questions are shown in a fixed order in preview; students will see them shuffled') }}</li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function examPreview() {
            const totalQuestions = {{ $questions->count() }};

            return {
                currentIndex: 0,
                totalQuestions,
                answeredQuestions: {},
                flaggedQuestions: {},

                get answeredCount() {
                    return Object.values(this.answeredQuestions).filter(a => a !== null && a !== '').length;
                },

                get flaggedCount() {
                    return Object.values(this.flaggedQuestions).filter(Boolean).length;
                },

                selectAnswer(questionId, answer) {
                    // Local state only — nothing is saved to the server in preview
                    this.answeredQuestions[questionId] = answer;
                },

                toggleFlag(questionId) {
                    this.flaggedQuestions[questionId] = !this.flaggedQuestions[questionId];
                },

                goTo(index) { this.currentIndex = index; },
                prev() { if (this.currentIndex > 0) this.currentIndex--; },
                next() { if (this.currentIndex < this.totalQuestions - 1) this.currentIndex++; },
            };
        }

        function matchingPreview(questionId, leftItems, rightItems) {
            return {
                matches: {},
                matchItem(index, value) {
                    this.matches[index] = value;
                    // Update answered state in parent (examPreview) via Alpine
                    const serialized = JSON.stringify(this.matches);
                    Alpine.evaluate(
                        document.querySelector('[x-data*="examPreview"]'),
                        `selectAnswer(${questionId}, '${serialized.replace(/'/g, "\\'")}')`
                    );
                },
            };
        }
    </script>
    @endpush
</x-layouts::app>
