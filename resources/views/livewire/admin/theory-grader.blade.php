{{-- TheoryGrader — Beautiful split-panel manual grader --}}
<div
    class="min-h-screen flex flex-col"
    x-data="{
        scoreInput: @entangle('score'),
        maxPts: {{ $currentQuestion['points'] ?? 1 }},
        setScore(v) {
            this.scoreInput = Math.max(0, Math.min(v, this.maxPts));
            $wire.setScore(this.scoreInput);
        }
    }"
    @keydown.window="handleKey($event)"
    x-init="
        function handleKey(e) {
            // Number keys 0-9 set score (only when not in an input/textarea)
            if (['INPUT','TEXTAREA','SELECT'].includes(document.activeElement.tagName)) return;
            if (e.key >= '0' && e.key <= '9') {
                let v = parseInt(e.key);
                // Multi-digit: if score is already set and < 10, allow 1X
                $wire.setScore(v);
            }
            if (e.key === 'Enter') { e.preventDefault(); $wire.saveAndNext(); }
            if (e.key === 'ArrowRight' || e.key === 'n') { $wire.next(); }
            if (e.key === 'ArrowLeft' || e.key === 'p') { $wire.prev(); }
        }
    "
>

{{-- ═══ Top Bar ═══ --}}
<div class="sticky top-0 z-20 bg-white dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700 px-4 sm:px-6 py-3">
    <div class="flex flex-wrap items-center gap-3">

        {{-- Back link --}}
        <a
            href="{{ route($routePrefix . '.show', $exam['id']) }}"
            class="flex items-center gap-1 text-sm text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200 mr-2"
        >
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            {{ __('Back') }}
        </a>

        {{-- Exam info --}}
        <div class="flex-1 min-w-0">
            <div class="flex flex-wrap items-baseline gap-2">
                <span class="font-semibold text-zinc-900 dark:text-zinc-100 text-sm truncate">{{ $exam['title'] }}</span>
                <span class="text-xs text-zinc-400">{{ $exam['class_name'] }} · {{ $exam['subject_name'] }}</span>
            </div>
        </div>

        {{-- Progress pill --}}
        <div class="flex items-center gap-2">
            <div class="text-xs font-medium px-3 py-1 rounded-full bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-700">
                {{ $fullyGradedCount }} / {{ $totalStudents }} {{ __('fully graded') }}
            </div>
        </div>

        {{-- Mode toggle --}}
        <div class="flex rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden text-xs font-medium">
            <button
                wire:click="switchMode('by_student')"
                class="px-3 py-1.5 transition-colors {{ $mode === 'by_student' ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-700' }}"
            >{{ __('By Student') }}</button>
            <button
                wire:click="switchMode('by_question')"
                class="px-3 py-1.5 transition-colors {{ $mode === 'by_question' ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-700' }}"
            >{{ __('By Question') }}</button>
        </div>
    </div>

    {{-- Question pills (by_student: select question; by_question: select question) --}}
    @if ($totalQuestions > 0)
    <div class="flex flex-wrap gap-1.5 mt-3">
        @foreach ($theoryQuestions as $qi => $q)
            @php
                $isCurrentQ = $qi === $currentQuestionIndex;
                $gradedForQ = 0;
                // We'll just show active state; full per-question graded counts would require more queries
            @endphp
            <button
                wire:click="jumpToQuestion({{ $qi }})"
                class="text-xs px-2.5 py-1 rounded-md font-medium border transition-all focus:outline-none focus:ring-2 focus:ring-indigo-500 {{ $isCurrentQ ? 'bg-indigo-600 border-indigo-600 text-white shadow-sm' : 'bg-white dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-400 hover:border-indigo-300' }}"
                title="{{ Str::limit($q['question_text'], 60) }}"
            >Q{{ $qi + 1 }}</button>
        @endforeach
    </div>
    @endif
</div>

{{-- ═══ No data state ═══ --}}
@if (! $hasData)
<div class="flex-1 flex items-center justify-center py-24">
    <div class="text-center space-y-3">
        <div class="mx-auto h-16 w-16 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center">
            <svg class="size-8 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
        </div>
        <p class="text-zinc-500 text-sm">
            @if ($totalQuestions === 0)
                {{ __('This exam has no theory/short-answer questions.') }}
            @else
                {{ __('No submitted attempts to grade yet.') }}
            @endif
        </p>
        <a href="{{ route($routePrefix . '.show', $exam['id']) }}" class="inline-flex items-center text-sm text-indigo-600 hover:underline">
            ← {{ __('Back to exam') }}
        </a>
    </div>
</div>
@else

{{-- ═══ Student navigation strip (by_student: across top; by_question: side scroller) ═══ --}}
<div class="bg-zinc-50 dark:bg-zinc-800/60 border-b border-zinc-200 dark:border-zinc-700 px-4 sm:px-6 py-2">
    <div class="flex items-center gap-2 overflow-x-auto pb-1">
        <span class="text-xs text-zinc-400 shrink-0">{{ __('Students:') }}</span>
        @foreach ($attempts as $si => $attempt)
            @php
                $isCurrS = $si === $currentStudentIndex;
                $gradeBg = $attempt['all_graded']
                    ? 'bg-green-100 dark:bg-green-900/30 border-green-300 dark:border-green-700 text-green-700 dark:text-green-300'
                    : ($attempt['graded_count'] > 0
                        ? 'bg-amber-50 dark:bg-amber-900/20 border-amber-300 dark:border-amber-700 text-amber-700 dark:text-amber-300'
                        : 'bg-white dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-400');
                $activeBg = 'ring-2 ring-indigo-500 ring-offset-1';
            @endphp
            <button
                wire:click="jumpToStudent({{ $si }})"
                class="shrink-0 flex items-center gap-1.5 px-2.5 py-1 rounded-lg border text-xs font-medium transition-all focus:outline-none {{ $gradeBg }} {{ $isCurrS ? $activeBg : 'hover:border-indigo-300' }}"
                title="{{ $attempt['student_name'] }} — {{ $attempt['graded_count'] }}/{{ $attempt['total_theory'] }} graded"
            >
                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-current/10 font-bold text-[10px]">
                    {{ $attempt['initial'] }}
                </span>
                <span class="hidden sm:inline truncate max-w-[80px]">{{ $attempt['student_name'] }}</span>
                @if ($attempt['all_graded'])
                    <svg class="size-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                @elseif ($attempt['graded_count'] > 0)
                    <span class="shrink-0 text-[9px]">{{ $attempt['graded_count'] }}/{{ $attempt['total_theory'] }}</span>
                @endif
            </button>
        @endforeach
    </div>
</div>

{{-- ═══ Main Split-Panel ═══ --}}
<div class="flex-1 grid grid-cols-1 lg:grid-cols-2 divide-y lg:divide-y-0 lg:divide-x divide-zinc-200 dark:divide-zinc-700">

    {{-- ════ LEFT: Question Panel ════ --}}
    <div class="lg:sticky lg:top-[108px] lg:self-start overflow-y-auto max-h-[calc(100vh-108px)] bg-white dark:bg-zinc-900 p-6 space-y-5">

        @if ($currentQuestion)
            {{-- Question header --}}
            <div class="flex flex-wrap items-start justify-between gap-2">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold px-2 py-0.5 rounded bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-700">
                            {{ $currentQuestion['type_label'] }}
                        </span>
                        @if ($currentQuestion['section_label'])
                            <span class="text-xs text-zinc-400">{{ $currentQuestion['section_label'] }}</span>
                        @endif
                    </div>
                    <h3 class="text-sm font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide">
                        {{ __('Question :n of :total', ['n' => $currentQuestionIndex + 1, 'total' => $totalQuestions]) }}
                    </h3>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ $currentQuestion['points'] }}</div>
                    <div class="text-xs text-zinc-400">{{ Str::plural('mark', $currentQuestion['points']) }}</div>
                </div>
            </div>

            {{-- Question text --}}
            <div class="prose prose-sm dark:prose-invert max-w-none bg-zinc-50 dark:bg-zinc-800/50 rounded-xl p-4 text-zinc-900 dark:text-zinc-100 font-medium leading-relaxed text-base">
                {!! nl2br(e($currentQuestion['question_text'])) !!}
            </div>

            @if ($currentQuestion['min_words'] || $currentQuestion['max_words'])
                <div class="flex gap-4 text-xs text-zinc-500">
                    @if ($currentQuestion['min_words'])
                        <span>Min words: <strong>{{ $currentQuestion['min_words'] }}</strong></span>
                    @endif
                    @if ($currentQuestion['max_words'])
                        <span>Max words: <strong>{{ $currentQuestion['max_words'] }}</strong></span>
                    @endif
                </div>
            @endif

            {{-- Marking Guide (collapsible) --}}
            @if ($currentQuestion['marking_guide'])
                <details class="group rounded-xl border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-950/30 overflow-hidden">
                    <summary class="flex items-center gap-2 px-4 py-3 cursor-pointer text-sm font-semibold text-blue-700 dark:text-blue-300 select-none list-none">
                        <svg class="size-4 transition-transform group-open:rotate-90" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                        {{ __('Marking Guide') }}
                    </summary>
                    <div class="px-4 pb-4 text-sm text-blue-800 dark:text-blue-200 whitespace-pre-line leading-relaxed">{{ $currentQuestion['marking_guide'] }}</div>
                </details>
            @endif

            {{-- Sample Answer (collapsible) --}}
            @if ($currentQuestion['sample_answer'])
                <details class="group rounded-xl border border-teal-200 dark:border-teal-800 bg-teal-50 dark:bg-teal-950/30 overflow-hidden">
                    <summary class="flex items-center gap-2 px-4 py-3 cursor-pointer text-sm font-semibold text-teal-700 dark:text-teal-300 select-none list-none">
                        <svg class="size-4 transition-transform group-open:rotate-90" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                        {{ __('Sample Answer') }}
                    </summary>
                    <div class="px-4 pb-4 text-sm text-teal-800 dark:text-teal-200 whitespace-pre-line leading-relaxed">{{ $currentQuestion['sample_answer'] }}</div>
                </details>
            @endif

        @else
            <div class="text-center py-12 text-zinc-400">{{ __('No question selected.') }}</div>
        @endif
    </div>

    {{-- ════ RIGHT: Student Answer + Grading Panel ════ --}}
    <div class="bg-white dark:bg-zinc-900 flex flex-col">

        @if ($currentAttempt && $currentQuestion)

            {{-- Student Info --}}
            <div class="px-6 pt-6 pb-4 border-b border-zinc-100 dark:border-zinc-800">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 font-bold text-sm">
                        {{ $currentAttempt['initial'] }}
                    </div>
                    <div>
                        <div class="font-semibold text-zinc-900 dark:text-zinc-100 text-sm">{{ $currentAttempt['student_name'] }}</div>
                        <div class="text-xs text-zinc-400">
                            @{{ $currentAttempt['student_username'] }}
                            &nbsp;·&nbsp;
                            {{ __('Student :n of :total', ['n' => $currentStudentIndex + 1, 'total' => $totalStudents]) }}
                        </div>
                    </div>

                    {{-- Graded badge --}}
                    @if ($currentAnswer && $currentAnswer['is_graded'])
                        <div class="ml-auto flex items-center gap-1 text-xs font-semibold text-green-600 dark:text-green-400">
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            {{ __('Graded') }}
                        </div>
                    @elseif ($currentAnswer === null)
                        <div class="ml-auto text-xs text-amber-500 font-medium">{{ __('No answer submitted') }}</div>
                    @endif
                </div>
            </div>

            {{-- Student Answer --}}
            <div class="px-6 py-5 flex-1 overflow-y-auto">
                @if ($currentAnswer && $currentAnswer['theory_answer'])
                    <div class="mb-2 flex items-center justify-between">
                        <span class="text-xs font-semibold text-zinc-400 uppercase tracking-wide">{{ __("Student's Answer") }}</span>
                        <span class="text-xs text-zinc-400">
                            {{ $currentAnswer['word_count'] }} {{ Str::plural('word', $currentAnswer['word_count']) }}
                        </span>
                    </div>
                    <div class="min-h-[120px] max-h-[300px] overflow-y-auto rounded-xl bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200 dark:border-zinc-700 px-4 py-3 text-sm text-zinc-800 dark:text-zinc-200 whitespace-pre-line leading-relaxed">{{ $currentAnswer['theory_answer'] }}</div>
                @else
                    <div class="flex items-center justify-center rounded-xl bg-zinc-50 dark:bg-zinc-800/60 border border-dashed border-zinc-300 dark:border-zinc-700 h-32">
                        <span class="text-sm text-zinc-400 italic">{{ __('No answer was submitted for this question.') }}</span>
                    </div>
                @endif

                {{-- ── SCORE ENTRY ── --}}
                <div class="mt-6 space-y-3">
                    <div class="flex items-center justify-between">
                        <label class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">{{ __('Score') }}</label>
                        <span class="text-sm text-zinc-400">{{ __('out of :max', ['max' => $currentQuestion['points']]) }}</span>
                    </div>

                    {{-- Quick score pills --}}
                    @php
                        $max = $currentQuestion['points'];
                        $pills = $max <= 10 ? range(0, $max) : [0, (int)round($max*0.25), (int)round($max*0.5), (int)round($max*0.75), $max];
                    @endphp
                    <div class="flex flex-wrap gap-2">
                        @foreach ($pills as $pill)
                            <button
                                type="button"
                                wire:click="setScore({{ $pill }})"
                                class="h-9 w-9 rounded-full text-sm font-bold border-2 transition-all focus:outline-none focus:ring-2 focus:ring-indigo-500 {{ $score == $pill ? 'bg-indigo-600 border-indigo-600 text-white shadow-md scale-110' : 'bg-white dark:bg-zinc-800 border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-400 hover:border-indigo-400' }}"
                            >{{ $pill }}</button>
                        @endforeach
                    </div>

                    {{-- Fine input --}}
                    <div class="flex items-center gap-3">
                        <input
                            type="number"
                            wire:model.live="score"
                            min="0"
                            max="{{ $currentQuestion['points'] }}"
                            class="w-20 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2 text-center font-bold text-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                        />
                        <div class="flex-1 h-2 rounded-full bg-zinc-200 dark:bg-zinc-700 overflow-hidden">
                            <div
                                class="h-full rounded-full transition-all duration-300 {{ $score >= $max ? 'bg-green-500' : ($score >= $max * 0.5 ? 'bg-amber-400' : 'bg-rose-400') }}"
                                style="width: {{ $max > 0 ? min(100, round($score / $max * 100)) : 0 }}%"
                            ></div>
                        </div>
                        <span class="text-sm font-semibold text-zinc-500 w-12 text-right">{{ $max > 0 ? round($score / $max * 100) : 0 }}%</span>
                    </div>
                </div>

                {{-- Comment --}}
                <div class="mt-5 space-y-1.5">
                    <label class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                        {{ __('Teacher Comment') }}
                        <span class="text-xs font-normal text-zinc-400">({{ __('shown to student') }})</span>
                    </label>
                    <textarea
                        wire:model="comment"
                        rows="3"
                        placeholder="{{ __('Well written. However, you missed the point about…') }}"
                        class="w-full rounded-xl border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none resize-none"
                    ></textarea>
                </div>
            </div>

            {{-- ── Action Bar ── --}}
            <div class="px-6 py-4 border-t border-zinc-100 dark:border-zinc-800 bg-zinc-50/80 dark:bg-zinc-800/40">
                {{-- Keyboard hint --}}
                <div class="flex flex-wrap gap-x-4 gap-y-1 mb-3 text-xs text-zinc-400">
                    <span><kbd class="rounded border border-zinc-300 dark:border-zinc-600 px-1 font-mono text-[10px]">0–9</kbd> set score</span>
                    <span><kbd class="rounded border border-zinc-300 dark:border-zinc-600 px-1 font-mono text-[10px]">Enter</kbd> save & next</span>
                    <span><kbd class="rounded border border-zinc-300 dark:border-zinc-600 px-1 font-mono text-[10px]">←/→</kbd> navigate</span>
                </div>

                {{-- Saved feedback --}}
                @if ($savedMessage)
                    <div class="mb-3 flex items-center gap-2 text-sm font-medium text-green-600 dark:text-green-400">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                        {{ $savedMessage }}
                    </div>
                @endif

                <div class="flex items-center gap-2">
                    {{-- Prev --}}
                    <button
                        type="button"
                        wire:click="prev"
                        class="flex items-center gap-1 px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 text-sm font-medium text-zinc-600 dark:text-zinc-400 bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors focus:outline-none focus:ring-2 focus:ring-zinc-400"
                        wire:loading.attr="disabled"
                    >
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                        {{ __('Prev') }}
                    </button>

                    {{-- Save --}}
                    <button
                        type="button"
                        wire:click="save"
                        class="flex items-center gap-1.5 px-5 py-2 rounded-lg bg-zinc-100 dark:bg-zinc-700 text-sm font-semibold text-zinc-700 dark:text-zinc-200 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors focus:outline-none focus:ring-2 focus:ring-zinc-400"
                        wire:loading.attr="disabled"
                    >
                        <svg wire:loading.remove wire:target="save,saveAndNext" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <svg wire:loading wire:target="save,saveAndNext" class="size-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        {{ __('Save') }}
                    </button>

                    {{-- Save & Next --}}
                    <button
                        type="button"
                        wire:click="saveAndNext"
                        @if ($isAtEnd) disabled @endif
                        class="flex flex-1 items-center justify-center gap-1.5 px-5 py-2 rounded-lg text-sm font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 {{ $isAtEnd ? 'bg-zinc-100 dark:bg-zinc-700 text-zinc-400 cursor-not-allowed' : 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm' }}"
                        wire:loading.attr="disabled"
                    >
                        {{ $isAtEnd ? __('Last entry') : __('Save & Next') }}
                        @unless ($isAtEnd)
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                        @endunless
                    </button>
                </div>
            </div>

        @else
            <div class="flex flex-1 items-center justify-center py-24 text-zinc-400">
                {{ __('Select a student and question to start grading.') }}
            </div>
        @endif
    </div>

</div>
{{-- end hasData --}}
@endif

</div>
