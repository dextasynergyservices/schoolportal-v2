<x-layouts::app :title="$exam->title">
    <div x-data="examTaker()" x-init="init()" @visibilitychange.window="handleVisibilityChange()" class="space-y-4">

        {{-- Fullscreen suggestion overlay --}}
        <template x-if="showFullscreenPrompt">
            <div class="fixed inset-0 z-50 bg-zinc-900/80 flex items-center justify-center p-4">
                <div class="bg-white dark:bg-zinc-800 rounded-xl p-6 max-w-md w-full text-center space-y-4 shadow-2xl">
                    <flux:icon name="arrows-pointing-out" class="mx-auto size-12 text-indigo-500" />
                    <h2 class="text-lg font-bold text-zinc-900 dark:text-white">{{ __('Enter Fullscreen?') }}</h2>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">
                        {{ __('For the best experience and to avoid accidental tab switches, we recommend fullscreen mode.') }}
                    </p>
                    <div class="flex gap-2 justify-center">
                        <flux:button variant="ghost" @click="showFullscreenPrompt = false">
                            {{ __('Skip') }}
                        </flux:button>
                        <flux:button variant="primary" icon="arrows-pointing-out" @click="enterFullscreen()">
                            {{ __('Go Fullscreen') }}
                        </flux:button>
                    </div>
                </div>
            </div>
        </template>

        {{-- Sticky Header with Timer --}}
        <div class="sticky top-0 z-10 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-3 sm:p-4 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <h1 class="text-base sm:text-lg font-bold text-zinc-900 dark:text-white truncate">{{ $exam->title }}</h1>
                    <p class="text-xs text-zinc-500">
                        {{ __('Question') }} <span x-text="currentIndex + 1"></span> {{ __('of') }} {{ $questions->count() }}
                        @if ($exam->max_tab_switches)
                            <span class="ml-2 text-zinc-400">&middot;</span>
                            <span :class="tabSwitches >= {{ $exam->max_tab_switches }} - 1 ? 'text-red-500' : 'text-zinc-500'"
                                class="ml-1">
                                <span x-text="'{{ __('Tabs:') }} ' + tabSwitches + '/{{ $exam->max_tab_switches }}'"></span>
                            </span>
                        @endif
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
                            'ring-2 ring-indigo-500 bg-indigo-600 text-white': currentIndex === {{ $index }},
                            'bg-green-500 text-white': currentIndex !== {{ $index }} && answeredQuestions[{{ $question->id }}],
                            'bg-zinc-200 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400': currentIndex !== {{ $index }} && !answeredQuestions[{{ $question->id }}]
                        }"
                        class="relative size-7 rounded-full text-xs font-medium flex items-center justify-center transition">
                        {{ $index + 1 }}
                        {{-- Flag indicator --}}
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
                    <button type="button" @click="toggleFlag({{ $question->id }})"
                        :class="flaggedQuestions[{{ $question->id }}] ? 'text-orange-500' : 'text-zinc-400 hover:text-orange-400'"
                        class="transition" :title="flaggedQuestions[{{ $question->id }}] ? '{{ __('Remove flag') }}' : '{{ __('Flag for review') }}'">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5" :fill="flaggedQuestions[{{ $question->id }}] ? 'currentColor' : 'none'" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0 2.77-.693a9 9 0 0 1 6.208.682l.108.054a9 9 0 0 0 6.086.71l3.114-.732a48.524 48.524 0 0 1-.005-10.499l-3.11.732a9 9 0 0 1-6.085-.711l-.108-.054a9 9 0 0 0-6.208-.682L3 4.5M3 15V4.5" />
                        </svg>
                    </button>
                </div>

                {{-- Question text --}}
                <p class="text-base sm:text-lg text-zinc-900 dark:text-white font-medium mb-4 select-none"
                    @contextmenu.prevent>{{ $question->question_text }}</p>

                {{-- Question image --}}
                @if ($question->question_image_url)
                    <div class="mb-4">
                        <img src="{{ $question->question_image_url }}" alt="{{ __('Question image') }}"
                            class="max-h-64 rounded-lg object-contain select-none" draggable="false"
                            @contextmenu.prevent>
                    </div>
                @endif

                {{-- Answer input based on type --}}
                @if ($question->type === 'multiple_choice')
                    @php
                        $options = $exam->shuffle_options
                            ? collect($question->options)->shuffle(crc32($attempt->id . '-' . $question->id))
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
                                <span class="text-sm text-zinc-800 dark:text-zinc-200 select-none">{{ $option }}</span>
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
                        @input.debounce.500ms="selectAnswer({{ $question->id }}, $event.target.value)"
                        class="w-full rounded-lg border border-zinc-200 border-b-zinc-300/80 dark:border-white/10 bg-white dark:bg-white/10 shadow-xs px-3 py-2 text-sm text-zinc-700 dark:text-zinc-300 placeholder-zinc-400 dark:placeholder-zinc-500">

                @elseif ($question->type === 'short_answer')
                    <div>
                        <textarea rows="3" placeholder="{{ __('Type your short answer...') }}"
                            @input.debounce.500ms="selectAnswer({{ $question->id }}, $event.target.value)"
                            class="w-full rounded-lg border border-zinc-200 border-b-zinc-300/80 dark:border-white/10 bg-white dark:bg-white/10 shadow-xs px-3 py-2 text-sm text-zinc-700 dark:text-zinc-300 placeholder-zinc-400 dark:placeholder-zinc-500"
                            x-text="answeredQuestions[{{ $question->id }}] || ''">{{ $answers[$question->id] ?? '' }}</textarea>
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
                            @input.debounce.500ms="selectAnswer({{ $question->id }}, $event.target.value)"
                            class="w-full rounded-lg border border-zinc-200 border-b-zinc-300/80 dark:border-white/10 bg-white dark:bg-white/10 shadow-xs px-3 py-2 text-sm text-zinc-700 dark:text-zinc-300 placeholder-zinc-400 dark:placeholder-zinc-500"
                            x-text="answeredQuestions[{{ $question->id }}] || ''">{{ $answers[$question->id] ?? '' }}</textarea>
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
                    <div x-data="matchingQuestion({{ $question->id }}, {{ json_encode($leftItems) }}, {{ json_encode($rightItems) }})"
                        class="space-y-3">
                        <p class="text-xs text-zinc-500">{{ __('Select an answer from the dropdown for each item.') }}</p>
                        @foreach ($leftItems as $i => $leftItem)
                            <div class="flex items-center gap-3">
                                <span class="flex-1 p-2 rounded bg-zinc-50 dark:bg-zinc-900 text-sm text-zinc-800 dark:text-zinc-200 select-none">
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

                <div x-show="currentIndex === totalQuestions - 1 || answeredCount === totalQuestions">
                    <flux:modal.trigger name="submit-exam-confirm">
                        <flux:button type="button" variant="primary" icon="paper-airplane">
                            {{ __('Submit') }}
                        </flux:button>
                    </flux:modal.trigger>
                </div>

                <flux:modal name="submit-exam-confirm" class="md:w-[28rem]">
                    <div class="space-y-6">
                        <div>
                            <flux:heading size="lg">{{ __('Submit your :label?', ['label' => Str::lower($label)]) }}</flux:heading>
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

                        {{-- Flagged questions warning --}}
                        <template x-if="flaggedCount > 0">
                            <div class="rounded-lg border border-orange-200 dark:border-orange-800 bg-orange-50 dark:bg-orange-950/20 p-3 text-sm text-orange-700 dark:text-orange-400">
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="currentColor" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0 2.77-.693a9 9 0 0 1 6.208.682l.108.054a9 9 0 0 0 6.086.71l3.114-.732a48.524 48.524 0 0 1-.005-10.499l-3.11.732a9 9 0 0 1-6.085-.711l-.108-.054a9 9 0 0 0-6.208-.682L3 4.5M3 15V4.5" />
                                    </svg>
                                    <span>{{ __('You have') }} <span class="font-semibold" x-text="flaggedCount"></span> {{ __('flagged question(s).') }}</span>
                                </div>
                            </div>
                        </template>

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

                        <form method="POST" action="{{ route($routePrefix . '.submit', $attempt) }}" id="submitForm"
                            @submit="injectAnswers()" class="flex justify-end gap-2">
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

        {{-- Tab switch warning overlay --}}
        <template x-if="showTabWarning">
            <div class="fixed inset-0 z-50 bg-red-900/90 flex items-center justify-center p-4">
                <div class="bg-white dark:bg-zinc-800 rounded-xl p-6 max-w-md w-full text-center space-y-4 shadow-2xl">
                    <flux:icon name="exclamation-triangle" class="mx-auto size-12 text-red-500" />
                    <h2 class="text-lg font-bold text-zinc-900 dark:text-white">{{ __('Tab Switch Detected!') }}</h2>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">
                        {{ __('You switched away from this :label. Please stay on this page.', ['label' => Str::lower($label)]) }}
                    </p>
                    <p class="text-sm font-medium" :class="tabSwitches >= {{ $exam->max_tab_switches ?? 999 }} - 1 ? 'text-red-600' : 'text-amber-600'">
                        <span x-text="'{{ __('Switches:') }} ' + tabSwitches + ' / {{ $exam->max_tab_switches ?? '∞' }}'"></span>
                    </p>
                    <flux:button variant="primary" @click="showTabWarning = false" class="w-full">
                        {{ __('Return to :label', ['label' => $label]) }}
                    </flux:button>
                </div>
            </div>
        </template>
    </div>

    @push('scripts')
    <script>
        function examTaker() {
            const initialAnswers = @json($answers);
            const totalQuestions = {{ $questions->count() }};
            const timeLimit = {{ $remainingSeconds ?? 'null' }};
            const maxTabSwitches = {{ $exam->max_tab_switches ?? 'null' }};
            const preventTabSwitch = {{ $exam->prevent_tab_switch ? 'true' : 'false' }};
            const preventCopyPaste = {{ $exam->prevent_copy_paste ? 'true' : 'false' }};
            const tabSwitchUrl = '{{ route($routePrefix . ".tab-switch", $attempt) }}';
            const csrfToken = '{{ csrf_token() }}';
            const attemptId = '{{ $attempt->id }}';

            const answeredQuestions = {};
            for (const [qId, answer] of Object.entries(initialAnswers)) {
                if (answer !== null && answer !== '') {
                    answeredQuestions[qId] = answer;
                }
            }

            return {
                currentIndex: 0,
                totalQuestions,
                answeredQuestions,
                flaggedQuestions: {},
                timeRemaining: timeLimit,
                timerInterval: null,
                tabSwitches: {{ $attempt->tab_switches ?? 0 }},
                showTabWarning: false,
                showFullscreenPrompt: preventTabSwitch,

                get answeredCount() {
                    return Object.values(this.answeredQuestions).filter(a => a !== null && a !== '').length;
                },

                get flaggedCount() {
                    return Object.values(this.flaggedQuestions).filter(Boolean).length;
                },

                init() {
                    if (this.timeRemaining !== null) {
                        this.startTimer();
                    }

                    // --- Anti-cheat: Copy/Cut/Paste prevention ---
                    if (preventCopyPaste) {
                        document.addEventListener('copy', (e) => e.preventDefault());
                        document.addEventListener('cut', (e) => e.preventDefault());
                        document.addEventListener('paste', (e) => e.preventDefault());
                    }

                    // --- Anti-cheat: Block dev tools & clipboard shortcuts ---
                    document.addEventListener('keydown', (e) => {
                        // Block F12 (dev tools)
                        if (e.key === 'F12') {
                            e.preventDefault();
                            return;
                        }
                        // Block Ctrl+Shift+I / Ctrl+Shift+J / Ctrl+Shift+C (dev tools)
                        if (e.ctrlKey && e.shiftKey && ['I', 'J', 'C'].includes(e.key.toUpperCase())) {
                            e.preventDefault();
                            return;
                        }
                        // Block Ctrl+U (view source)
                        if (e.ctrlKey && e.key.toUpperCase() === 'U') {
                            e.preventDefault();
                            return;
                        }
                        // Block clipboard shortcuts only if prevent_copy_paste is on
                        if (preventCopyPaste) {
                            if (e.ctrlKey && ['C', 'V', 'X', 'A'].includes(e.key.toUpperCase())) {
                                e.preventDefault();
                                return;
                            }
                        }
                        // Block Ctrl+S (save page)
                        if (e.ctrlKey && e.key.toUpperCase() === 'S') {
                            e.preventDefault();
                            return;
                        }
                        // Block Ctrl+P (print)
                        if (e.ctrlKey && e.key.toUpperCase() === 'P') {
                            e.preventDefault();
                            return;
                        }
                    });

                    // --- Anti-cheat: Prevent accidental navigation ---
                    this._beforeUnloadHandler = (e) => {
                        e.preventDefault();
                        e.returnValue = '';
                    };
                    window.addEventListener('beforeunload', this._beforeUnloadHandler);

                    // --- Anti-cheat: Multi-tab detection via BroadcastChannel ---
                    try {
                        this._bc = new BroadcastChannel('exam_attempt_' + attemptId);
                        // Announce this tab
                        this._bc.postMessage({ type: 'ping', ts: Date.now() });
                        this._bc.onmessage = (event) => {
                            if (event.data.type === 'ping') {
                                // Another tab opened the same exam — warn and count as tab switch
                                if (preventTabSwitch && maxTabSwitches !== null) {
                                    this.tabSwitches++;
                                    this.showTabWarning = true;
                                    this.notifyTabSwitch();
                                }
                                // Reply so the other tab knows we exist
                                this._bc.postMessage({ type: 'pong', ts: Date.now() });
                            }
                        };
                    } catch (e) {
                        // BroadcastChannel not supported — graceful degradation
                    }

                    // --- Anti-cheat: Detect right-click ---
                    document.addEventListener('contextmenu', (e) => {
                        e.preventDefault();
                    });

                    // --- Anti-cheat: Detect print screen (best effort) ---
                    document.addEventListener('keyup', (e) => {
                        if (e.key === 'PrintScreen') {
                            if (preventCopyPaste) {
                                navigator.clipboard.writeText('').catch(() => {});
                            }
                        }
                    });
                },

                startTimer() {
                    this.timerInterval = setInterval(() => {
                        this.timeRemaining--;
                        if (this.timeRemaining <= 0) {
                            clearInterval(this.timerInterval);
                            this.forceSubmit();
                        }
                    }, 1000);
                },

                handleVisibilityChange() {
                    if (document.hidden && preventTabSwitch && maxTabSwitches !== null) {
                        this.tabSwitches++;
                        this.showTabWarning = true;
                        this.notifyTabSwitch();
                    }
                },

                notifyTabSwitch() {
                    fetch(tabSwitchUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ tab_switches: this.tabSwitches }),
                    }).then(r => r.json()).then(data => {
                        if (data.auto_submitted) {
                            // Remove beforeunload so redirect works
                            window.removeEventListener('beforeunload', this._beforeUnloadHandler);
                            window.location.href = data.redirect;
                        }
                    }).catch(() => {});
                },

                toggleFlag(questionId) {
                    this.flaggedQuestions[questionId] = !this.flaggedQuestions[questionId];
                },

                selectAnswer(questionId, answer) {
                    this.answeredQuestions[questionId] = answer;
                    this.saveAnswerToServer(questionId, answer);
                },

                saveAnswerToServer(questionId, answer) {
                    const url = '{{ route($routePrefix . ".save-answer", $attempt) }}';
                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            question_id: questionId,
                            selected_answer: answer,
                        }),
                    }).catch(() => {});
                },

                injectAnswers() {
                    // Remove beforeunload so form submission goes through
                    window.removeEventListener('beforeunload', this._beforeUnloadHandler);
                    const form = document.getElementById('submitForm');
                    form.querySelectorAll('.injected-answer').forEach(el => el.remove());
                    for (const [questionId, answer] of Object.entries(this.answeredQuestions)) {
                        if (answer !== null && answer !== '') {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'answers[' + questionId + ']';
                            input.value = answer;
                            input.className = 'injected-answer';
                            form.appendChild(input);
                        }
                    }
                },

                forceSubmit() {
                    // Remove beforeunload so submission goes through
                    window.removeEventListener('beforeunload', this._beforeUnloadHandler);
                    this.injectAnswers();
                    document.getElementById('submitForm').submit();
                },

                formatTime(seconds) {
                    if (seconds === null) return '';
                    const total = Math.max(0, Math.floor(seconds));
                    const h = Math.floor(total / 3600);
                    const m = Math.floor((total % 3600) / 60);
                    const s = total % 60;
                    if (h > 0) {
                        return String(h) + ':' + String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
                    }
                    return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
                },

                goTo(index) { this.currentIndex = index; },
                prev() { if (this.currentIndex > 0) this.currentIndex--; },
                next() { if (this.currentIndex < this.totalQuestions - 1) this.currentIndex++; },

                enterFullscreen() {
                    this.showFullscreenPrompt = false;
                    const el = document.documentElement;
                    if (el.requestFullscreen) {
                        el.requestFullscreen().catch(() => {});
                    } else if (el.webkitRequestFullscreen) {
                        el.webkitRequestFullscreen();
                    } else if (el.msRequestFullscreen) {
                        el.msRequestFullscreen();
                    }
                },

                destroy() {
                    if (this.timerInterval) clearInterval(this.timerInterval);
                    if (this._bc) this._bc.close();
                },
            };
        }

        function matchingQuestion(questionId, leftItems, rightItems) {
            return {
                matches: {},
                matchItem(index, value) {
                    this.matches[index] = value;
                    // Serialize matches as JSON string for the parent answer
                    const serialized = JSON.stringify(this.matches);
                    const parent = this.$el.closest('[x-data*="examTaker"]');
                    if (parent && parent.__x) {
                        parent.__x.$data.selectAnswer(questionId, serialized);
                    } else {
                        // Fallback: use Alpine.$data
                        Alpine.evaluate(this.$el.closest('[x-data]'), `selectAnswer(${questionId}, '${serialized.replace(/'/g, "\\'")}')`);
                    }
                },
            };
        }
    </script>
    @endpush
</x-layouts::app>
