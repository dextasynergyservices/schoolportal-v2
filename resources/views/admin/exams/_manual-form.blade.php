{{-- Shared manual exam creation form (used by create and review pages) --}}
@props([
    'classes',
    'subjects',
    'scoreComponents',
    'currentSession' => null,
    'currentTerm' => null,
    'questions' => [],
    'selectedClassId' => null,
    'selectedSubjectId' => null,
    'selectedScoreComponentId' => null,
    'sourceType' => 'manual',
    'sourcePrompt' => null,
    'sourceDocumentUrl' => null,
    'sourceDocumentPublicId' => null,
    'difficulty' => 'medium',
    'exam' => null,
    'storeRoute' => null,
    'updateRoute' => null,
    'indexRoute' => null,
])

@php
    $questionsData = collect($questions)->map(fn ($q, $i) => [
        'id' => $i,
        'type' => $q['type'] ?? 'multiple_choice',
        'question_text' => $q['question_text'] ?? $q['question'] ?? '',
        'options' => $q['options'] ?? ['', '', '', ''],
        'correct_answer' => $q['correct_answer'] ?? '',
        'marking_guide' => $q['marking_guide'] ?? '',
        'sample_answer' => $q['sample_answer'] ?? '',
        'min_words' => $q['min_words'] ?? null,
        'max_words' => $q['max_words'] ?? null,
        'explanation' => $q['explanation'] ?? '',
        'points' => $q['points'] ?? 1,
        'section_label' => $q['section_label'] ?? '',
    ])->values()->toArray();
@endphp

<flux:card>
    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/30">
            <div class="flex items-center gap-2 text-sm font-medium text-red-700 dark:text-red-300">
                <svg class="size-5 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" /></svg>
                {{ __('Please fix the following errors:') }}
            </div>
            <ul class="mt-2 list-disc list-inside text-sm text-red-600 dark:text-red-400 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form
        method="POST"
        action="{{ $exam ? ($updateRoute ?? route(($routePrefix ?? 'admin.exams') . '.update', $exam)) : ($storeRoute ?? route(($routePrefix ?? 'admin.exams') . '.store')) }}"
        x-data="examEditor(@js($questionsData))"
        x-on:submit="submitting = true"
        novalidate
        class="space-y-6"
    >
        @csrf
        @if ($exam) @method('PUT') @endif

        <input type="hidden" name="source_type" value="{{ $sourceType }}">
        <input type="hidden" name="source_prompt" value="{{ $sourcePrompt }}">
        <input type="hidden" name="source_document_url" value="{{ $sourceDocumentUrl }}">
        <input type="hidden" name="source_document_public_id" value="{{ $sourceDocumentPublicId }}">

        {{-- Exam Settings --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:input name="title" label="{{ __('Title') }}" value="{{ $exam?->title ?? old('title', '') }}" required />

            <flux:select name="class_id" label="{{ __('Class') }}" required>
                <option value="">{{ __('Select class...') }}</option>
                @foreach ($classes as $class)
                    <option value="{{ $class->id }}" @selected(old('class_id', $selectedClassId ?? $exam?->class_id) == $class->id)>{{ $class->name }}</option>
                @endforeach
            </flux:select>

            <div>
                <div class="flex items-end gap-2">
                    <div class="flex-1">
                        <flux:label>{{ __('Subject') }} *</flux:label>
                        <flux:select name="subject_id" required x-ref="subjectSelect">
                            <option value="">{{ __('Select subject...') }}</option>
                            @foreach ($subjects as $subject)
                                <option value="{{ $subject->id }}" @selected(old('subject_id', $selectedSubjectId ?? $exam?->subject_id) == $subject->id)>{{ $subject->name }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                    <flux:button type="button" variant="subtle" size="sm" icon="plus" x-on:click="$refs.newSubjectModal.showModal()" aria-label="{{ __('Add Subject') }}" />
                </div>

                {{-- Inline Subject Creation Modal --}}
                <dialog x-ref="newSubjectModal" class="rounded-xl shadow-xl p-0 backdrop:bg-black/50 w-full max-w-md">
                    <div x-data="{
                        saving: false, error: '', subjectName: '', shortName: '',
                        async saveSubject() {
                            this.saving = true; this.error = '';
                            try {
                                const r = await fetch('{{ route(($routePrefix ?? 'admin.exams') . '.store-subject') }}', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                                    body: JSON.stringify({ name: this.subjectName.trim(), short_name: this.shortName.trim() })
                                });
                                const data = await r.json();
                                if (!r.ok) { this.error = data.message || 'Validation failed'; this.saving = false; return; }
                                if (data.error) { this.error = data.error; this.saving = false; return; }
                                const sel = this.$refs.subjectSelect || document.querySelector('[name=subject_id]');
                                if (sel) { sel.add(new Option(data.subject.name, data.subject.id, true, true)); sel.value = data.subject.id; sel.dispatchEvent(new Event('change', { bubbles: true })); }
                                this.subjectName = ''; this.shortName = ''; this.saving = false;
                                this.$refs.newSubjectModal.close();
                            } catch (e) { this.saving = false; this.error = e.message || 'Failed to save.'; }
                        }
                    }" class="p-6 space-y-4">
                        <h3 class="text-lg font-semibold">{{ __('Add New Subject') }}</h3>
                        <div class="space-y-3">
                            <div>
                                <flux:label>{{ __('Subject Name') }} *</flux:label>
                                <flux:input type="text" x-model="subjectName" placeholder="{{ __('e.g., Mathematics') }}" required />
                            </div>
                            <div>
                                <flux:label>{{ __('Short Name (optional)') }}</flux:label>
                                <flux:input type="text" x-model="shortName" placeholder="{{ __('e.g., MATH') }}" />
                            </div>
                        </div>
                        <p x-show="error" x-text="error" class="text-sm text-red-500"></p>
                        <div class="flex justify-end gap-2">
                            <button type="button" class="px-3 py-1.5 text-sm rounded text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-700" x-on:click="subjectName = ''; shortName = ''; error = ''; $refs.newSubjectModal.close()">{{ __('Cancel') }}</button>
                            <button type="button" class="px-3 py-1.5 text-sm font-medium rounded bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-50" x-bind:disabled="saving || !subjectName.trim()" x-on:click="saveSubject()">
                                <span x-show="saving">{{ __('Saving...') }}</span>
                                <span x-show="!saving">{{ __('Save Subject') }}</span>
                            </button>
                        </div>
                    </div>
                </dialog>
            </div>

            <flux:select name="score_component_id" label="{{ __('Score Component') }}">
                <option value="">{{ __('None') }}</option>
                @foreach ($scoreComponents as $comp)
                    <option value="{{ $comp->id }}" @selected(old('score_component_id', $selectedScoreComponentId ?? $exam?->score_component_id) == $comp->id)>{{ $comp->name }} ({{ $comp->short_name }})</option>
                @endforeach
            </flux:select>

            <flux:input name="max_score" type="number" label="{{ __('Maximum Score') }}" value="{{ $exam?->max_score ?? old('max_score', 100) }}" min="1" max="200" required />
            <flux:input name="passing_score" type="number" label="{{ __('Passing Score (%)') }}" value="{{ $exam?->passing_score ?? old('passing_score', 40) }}" min="1" max="100" required />
            <flux:input name="time_limit_minutes" type="number" label="{{ __('Time Limit (min)') }}" value="{{ $exam?->time_limit_minutes ?? old('time_limit_minutes', '') }}" min="1" max="300" placeholder="{{ __('No limit') }}" />
            <flux:input name="max_attempts" type="number" label="{{ __('Max Attempts') }}" value="{{ $exam?->max_attempts ?? old('max_attempts', 1) }}" min="1" max="5" required />
            <flux:select name="difficulty" label="{{ __('Difficulty') }}">
                @php $diff = old('difficulty', $difficulty ?? $exam?->difficulty ?? 'medium'); @endphp
                <option value="easy" @selected($diff === 'easy')>Easy</option>
                <option value="medium" @selected($diff === 'medium')>Medium</option>
                <option value="hard" @selected($diff === 'hard')>Hard</option>
            </flux:select>
        </div>

        <flux:textarea name="description" label="{{ __('Description (optional)') }}" rows="2">{{ $exam?->description ?? old('description', '') }}</flux:textarea>
        <flux:textarea name="instructions" label="{{ __('Instructions for Students') }}" rows="3" placeholder="{{ __('Instructions shown before the exam starts...') }}">{{ $exam?->instructions ?? old('instructions', '') }}</flux:textarea>

        {{-- Availability Window --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:input name="available_from" type="datetime-local" label="{{ __('Available From') }}" value="{{ $exam?->available_from?->format('Y-m-d\TH:i') ?? old('available_from', '') }}" />
            <flux:input name="available_until" type="datetime-local" label="{{ __('Available Until') }}" value="{{ $exam?->available_until?->format('Y-m-d\TH:i') ?? old('available_until', '') }}" />
        </div>

        {{-- Anti-cheating & Display Settings --}}
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            <flux:checkbox name="shuffle_questions" label="{{ __('Shuffle questions') }}" value="1" :checked="old('shuffle_questions', $exam?->shuffle_questions ?? false)" />
            <flux:checkbox name="shuffle_options" label="{{ __('Shuffle options') }}" value="1" :checked="old('shuffle_options', $exam?->shuffle_options ?? false)" />
            <flux:checkbox name="show_correct_answers" label="{{ __('Show answers after') }}" value="1" :checked="old('show_correct_answers', $exam?->show_correct_answers ?? false)" />
            <flux:checkbox name="prevent_tab_switch" label="{{ __('Prevent tab switch') }}" value="1" :checked="old('prevent_tab_switch', $exam?->prevent_tab_switch ?? true)" />
            <flux:checkbox name="prevent_copy_paste" label="{{ __('Prevent copy/paste') }}" value="1" :checked="old('prevent_copy_paste', $exam?->prevent_copy_paste ?? true)" />
            <flux:checkbox name="randomize_per_student" label="{{ __('Randomize per student') }}" value="1" :checked="old('randomize_per_student', $exam?->randomize_per_student ?? false)" />
        </div>
        <flux:input name="max_tab_switches" type="number" label="{{ __('Max Tab Switches (auto-submit after)') }}" value="{{ $exam?->max_tab_switches ?? old('max_tab_switches', 3) }}" min="1" max="10" class="w-48" />

        {{-- Questions Editor --}}
        <div class="border-t pt-6 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold">
                    {{ __('Questions') }}
                    <span class="text-sm font-normal text-zinc-500" x-text="'(' + questions.length + ' questions, ' + totalPoints + ' points)'"></span>
                </h3>
                <div class="flex gap-2">
                    <flux:button type="button" variant="subtle" size="sm" icon="plus" x-on:click="addQuestion('multiple_choice')">MCQ (A, B, C, D)</flux:button>
                    <flux:button type="button" variant="subtle" size="sm" x-on:click="addQuestion('true_false')">T/F</flux:button>
                    <flux:button type="button" variant="subtle" size="sm" x-on:click="addQuestion('fill_blank')">Fill</flux:button>
                    <flux:button type="button" variant="subtle" size="sm" x-on:click="addQuestion('short_answer')">Short</flux:button>
                    <flux:button type="button" variant="subtle" size="sm" x-on:click="addQuestion('theory')">Theory</flux:button>
                    <flux:button type="button" variant="subtle" size="sm" x-on:click="addQuestion('matching')">Match</flux:button>
                </div>
            </div>

            <template x-for="(q, idx) in questions" :key="q.id">
                <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg p-4 space-y-3 bg-white dark:bg-zinc-800">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-semibold text-zinc-500" x-text="'Q' + (idx + 1)"></span>
                            <select :name="'questions[' + idx + '][type]'" x-model="q.type" x-on:change="onTypeChange(q)" class="text-xs rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/10 text-zinc-700 dark:text-zinc-300 px-2 py-1 shadow-xs">
                                <option value="multiple_choice">MCQ (Multiple Choice Question)</option>
                                <option value="true_false">True/False</option>
                                <option value="fill_blank">Fill Blank (Subjective)</option>
                                <option value="short_answer">Short Answer</option>
                                <option value="theory">Theory</option>
                                <option value="matching">Matching</option>
                            </select>
                        </div>
                        <div class="flex items-center gap-2">
                            <input :name="'questions[' + idx + '][section_label]'" x-model="q.section_label" placeholder="{{ __('Section label...') }}" class="text-xs rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/10 text-zinc-700 dark:text-zinc-300 px-2 py-1 w-36 shadow-xs placeholder-zinc-400 dark:placeholder-zinc-500">
                            <span class="text-xs flex items-center gap-1 text-zinc-600 dark:text-zinc-400">
                                Pts:
                                <input type="number" :name="'questions[' + idx + '][points]'" x-model.number="q.points" min="1" max="100" class="w-14 text-xs rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/10 text-zinc-700 dark:text-zinc-300 px-1 py-1 shadow-xs">
                            </span>
                            <button type="button" x-on:click="moveUp(idx)" x-show="idx > 0" class="text-zinc-400 hover:text-zinc-600" aria-label="Move up">↑</button>
                            <button type="button" x-on:click="moveDown(idx)" x-show="idx < questions.length - 1" class="text-zinc-400 hover:text-zinc-600" aria-label="Move down">↓</button>
                            <button type="button" x-on:click="removeQuestion(idx)" class="text-red-400 hover:text-red-600" aria-label="Remove">✕</button>
                        </div>
                    </div>

                    {{-- Question text --}}
                    <textarea :name="'questions[' + idx + '][question_text]'" x-model="q.question_text" rows="2" class="w-full rounded-lg border border-zinc-200 border-b-zinc-300/80 dark:border-white/10 bg-white dark:bg-white/10 shadow-xs px-3 py-2 text-sm text-zinc-700 dark:text-zinc-300 placeholder-zinc-400 dark:placeholder-zinc-500" placeholder="{{ __('Question text...') }}"></textarea>

                    {{-- MCQ Options --}}
                    <template x-if="q.type === 'multiple_choice'">
                        <div class="space-y-2">
                            <template x-for="(opt, oi) in q.options" :key="oi">
                                <div class="flex items-center gap-2">
                                    <input type="radio" :name="'q_correct_' + q.id" :checked="q.correct_answer === opt" x-on:click="q.correct_answer = opt" class="mt-0.5">
                                    <input type="text" x-model="q.options[oi]" x-on:input="if (q.correct_answer === opt) q.correct_answer = $event.target.value" class="flex-1 rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/10 shadow-xs px-2 py-1 text-sm text-zinc-700 dark:text-zinc-300 placeholder-zinc-400 dark:placeholder-zinc-500" :placeholder="'Option ' + String.fromCharCode(65 + oi)">
                                    <button type="button" x-show="q.options.length > 2" x-on:click="q.options.splice(oi, 1)" class="text-red-400 text-xs">✕</button>
                                </div>
                            </template>
                            <button type="button" x-on:click="q.options.push('')" class="text-xs text-blue-600 hover:underline">+ Add option</button>
                            <input type="hidden" :name="'questions[' + idx + '][correct_answer]'" :value="q.correct_answer">
                            <template x-for="(opt, oi) in q.options" :key="'opt_' + oi">
                                <input type="hidden" :name="'questions[' + idx + '][options][' + oi + ']'" :value="opt">
                            </template>
                        </div>
                    </template>

                    {{-- True/False --}}
                    <template x-if="q.type === 'true_false'">
                        <div class="flex items-center gap-4">
                            <label class="flex items-center gap-1 text-sm">
                                <input type="radio" :name="'q_correct_' + q.id" value="True" :checked="q.correct_answer === 'True'" x-on:click="q.correct_answer = 'True'"> True
                            </label>
                            <label class="flex items-center gap-1 text-sm">
                                <input type="radio" :name="'q_correct_' + q.id" value="False" :checked="q.correct_answer === 'False'" x-on:click="q.correct_answer = 'False'"> False
                            </label>
                            <input type="hidden" :name="'questions[' + idx + '][correct_answer]'" :value="q.correct_answer">
                            <input type="hidden" :name="'questions[' + idx + '][options][0]'" value="True">
                            <input type="hidden" :name="'questions[' + idx + '][options][1]'" value="False">
                        </div>
                    </template>

                    {{-- Fill in the Blank --}}
                    <template x-if="q.type === 'fill_blank'">
                        <div>
                            <input type="text" :name="'questions[' + idx + '][correct_answer]'" x-model="q.correct_answer" class="w-full rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/10 shadow-xs px-2 py-1 text-sm text-zinc-700 dark:text-zinc-300 placeholder-zinc-400 dark:placeholder-zinc-500" placeholder="{{ __('Correct answer...') }}">
                        </div>
                    </template>

                    {{-- Short Answer --}}
                    <template x-if="q.type === 'short_answer'">
                        <div class="space-y-2">
                            <textarea :name="'questions[' + idx + '][sample_answer]'" x-model="q.sample_answer" rows="2" class="w-full rounded-lg border border-zinc-200 border-b-zinc-300/80 dark:border-white/10 bg-white dark:bg-white/10 shadow-xs px-3 py-2 text-sm text-zinc-700 dark:text-zinc-300 placeholder-zinc-400 dark:placeholder-zinc-500" placeholder="{{ __('Sample answer...') }}"></textarea>
                            <textarea :name="'questions[' + idx + '][marking_guide]'" x-model="q.marking_guide" rows="2" class="w-full rounded-lg border border-zinc-200 border-b-zinc-300/80 dark:border-white/10 bg-white dark:bg-white/10 shadow-xs px-3 py-2 text-sm text-zinc-700 dark:text-zinc-300 placeholder-zinc-400 dark:placeholder-zinc-500" placeholder="{{ __('Marking guide (e.g., Award 1 mark for mentioning X...)') }}"></textarea>
                        </div>
                    </template>

                    {{-- Theory --}}
                    <template x-if="q.type === 'theory'">
                        <div class="space-y-2">
                            <textarea :name="'questions[' + idx + '][sample_answer]'" x-model="q.sample_answer" rows="3" class="w-full rounded-lg border border-zinc-200 border-b-zinc-300/80 dark:border-white/10 bg-white dark:bg-white/10 shadow-xs px-3 py-2 text-sm text-zinc-700 dark:text-zinc-300 placeholder-zinc-400 dark:placeholder-zinc-500" placeholder="{{ __('Model answer...') }}"></textarea>
                            <textarea :name="'questions[' + idx + '][marking_guide]'" x-model="q.marking_guide" rows="3" class="w-full rounded-lg border border-zinc-200 border-b-zinc-300/80 dark:border-white/10 bg-white dark:bg-white/10 shadow-xs px-3 py-2 text-sm text-zinc-700 dark:text-zinc-300 placeholder-zinc-400 dark:placeholder-zinc-500" placeholder="{{ __('Marking guide with point allocation...') }}"></textarea>
                            <div class="flex gap-4">
                                <span class="text-xs flex items-center gap-1 text-zinc-600 dark:text-zinc-400">Min words: <input type="number" :name="'questions[' + idx + '][min_words]'" x-model.number="q.min_words" min="1" class="w-20 rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/10 text-zinc-700 dark:text-zinc-300 px-1 py-0.5 text-xs shadow-xs"></span>
                                <span class="text-xs flex items-center gap-1 text-zinc-600 dark:text-zinc-400">Max words: <input type="number" :name="'questions[' + idx + '][max_words]'" x-model.number="q.max_words" min="1" class="w-20 rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/10 text-zinc-700 dark:text-zinc-300 px-1 py-0.5 text-xs shadow-xs"></span>
                            </div>
                        </div>
                    </template>

                    {{-- Matching --}}
                    <template x-if="q.type === 'matching'">
                        <div class="space-y-2">
                            <div class="grid grid-cols-2 gap-2 text-xs font-medium text-zinc-500">
                                <span>{{ __('Column A (Term)') }}</span>
                                <span>{{ __('Column B (Definition)') }}</span>
                            </div>
                            <template x-for="(pair, pi) in q.options" :key="pi">
                                <div class="grid grid-cols-2 gap-2">
                                    <input type="text" x-model="pair.left" :name="'questions[' + idx + '][options][' + pi + '][left]'" class="rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/10 shadow-xs px-2 py-1 text-sm text-zinc-700 dark:text-zinc-300 placeholder-zinc-400 dark:placeholder-zinc-500" placeholder="{{ __('Term...') }}">
                                    <div class="flex gap-1">
                                        <input type="text" x-model="pair.right" :name="'questions[' + idx + '][options][' + pi + '][right]'" class="flex-1 rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/10 shadow-xs px-2 py-1 text-sm text-zinc-700 dark:text-zinc-300 placeholder-zinc-400 dark:placeholder-zinc-500" placeholder="{{ __('Definition...') }}">
                                        <button type="button" x-show="q.options.length > 2" x-on:click="q.options.splice(pi, 1)" class="text-red-400 text-xs">✕</button>
                                    </div>
                                </div>
                            </template>
                            <button type="button" x-on:click="q.options.push({left: '', right: ''})" class="text-xs text-blue-600 hover:underline">+ Add pair</button>
                        </div>
                    </template>

                    {{-- Explanation (for all types) --}}
                    <input type="text" :name="'questions[' + idx + '][explanation]'" x-model="q.explanation" class="w-full rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/10 shadow-xs px-2 py-1 text-sm text-zinc-500 dark:text-zinc-400 placeholder-zinc-400 dark:placeholder-zinc-500" placeholder="{{ __('Explanation (optional)...') }}">
                </div>
            </template>

            <div x-show="questions.length === 0" class="text-center py-8 text-zinc-400">
                {{ __('No questions yet. Click the buttons above to add questions.') }}
            </div>
        </div>

        {{-- Validation error banner --}}
        <div x-show="validationError" x-cloak x-transition
            class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/30 p-3 flex items-center gap-2">
            <flux:icon name="exclamation-triangle" class="size-5 text-red-500 shrink-0" />
            <span class="text-sm text-red-700 dark:text-red-300" x-text="validationError"></span>
            <button type="button" @click="validationError = ''" class="ml-auto text-red-400 hover:text-red-600">&times;</button>
        </div>

        {{-- Submit --}}
        <div class="flex justify-end gap-3 border-t pt-4">
            <flux:button href="{{ $exam ? ($indexRoute ?? route(($routePrefix ?? 'admin.exams') . '.show', $exam)) : ($indexRoute ?? route(($routePrefix ?? 'admin.exams') . '.index')) }}" variant="ghost">{{ __('Cancel') }}</flux:button>
            <button
                type="submit"
                x-on:click="
                    if (questions.length === 0) {
                        $event.preventDefault();
                        validationError = '{{ __('Please add at least one question before submitting.') }}';
                        window.scrollTo({ top: document.querySelector('[x-show=\'validationError\']')?.offsetTop - 20, behavior: 'smooth' });
                        return;
                    }
                    validationError = '';
                "
                x-bind:disabled="submitting"
                class="relative inline-flex items-center font-medium justify-center gap-2 whitespace-nowrap h-10 text-sm rounded-lg ps-4 pe-4 bg-[var(--color-accent)] hover:bg-[color-mix(in_oklab,_var(--color-accent),_transparent_10%)] text-[var(--color-accent-foreground)] border border-black/10 dark:border-0 shadow-[inset_0px_1px_--theme(--color-white/.2)] disabled:opacity-75 disabled:cursor-default disabled:pointer-events-none"
            >
                <span x-show="!submitting">{{ $exam ? __('Update Exam') : __('Create Exam') }}</span>
                <span x-show="submitting" x-cloak class="flex items-center gap-2">
                    <svg class="animate-spin size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    {{ __('Saving...') }}
                </span>
            </button>
        </div>
    </form>
</flux:card>

<script>
    function examEditor(initial) {
        return {
            nextId: initial.length + 1,
            questions: initial.length ? initial : [],
            submitting: false,
            validationError: '',
            get totalPoints() {
                return this.questions.reduce((sum, q) => sum + (parseInt(q.points) || 0), 0);
            },
            addQuestion(type) {
                const defaults = {
                    multiple_choice: { options: ['', '', '', ''], correct_answer: '', points: 1 },
                    true_false: { options: ['True', 'False'], correct_answer: 'True', points: 1 },
                    fill_blank: { options: [], correct_answer: '', points: 1 },
                    short_answer: { options: [], correct_answer: null, sample_answer: '', marking_guide: '', points: 3 },
                    theory: { options: [], correct_answer: null, sample_answer: '', marking_guide: '', min_words: 100, max_words: 500, points: 10 },
                    matching: { options: [{left: '', right: ''}, {left: '', right: ''}, {left: '', right: ''}, {left: '', right: ''}], correct_answer: null, points: 4 },
                };
                const d = defaults[type] || defaults.multiple_choice;
                this.questions.push({
                    id: this.nextId++,
                    type,
                    question_text: '',
                    explanation: '',
                    section_label: '',
                    ...d,
                });
            },
            onTypeChange(q) {
                const defaults = {
                    multiple_choice: { options: ['', '', '', ''], correct_answer: '', points: 1 },
                    true_false: { options: ['True', 'False'], correct_answer: 'True', points: 1 },
                    fill_blank: { options: [], correct_answer: '', points: 1 },
                    short_answer: { options: [], correct_answer: null, sample_answer: '', marking_guide: '', points: 3 },
                    theory: { options: [], correct_answer: null, sample_answer: '', marking_guide: '', min_words: 100, max_words: 500, points: 10 },
                    matching: { options: [{left: '', right: ''}, {left: '', right: ''}, {left: '', right: ''}, {left: '', right: ''}], correct_answer: null, points: 4 },
                };
                const d = defaults[q.type] || {};
                Object.assign(q, d);
            },
            removeQuestion(idx) {
                this.questions.splice(idx, 1);
            },
            moveUp(idx) {
                if (idx > 0) [this.questions[idx - 1], this.questions[idx]] = [this.questions[idx], this.questions[idx - 1]];
            },
            moveDown(idx) {
                if (idx < this.questions.length - 1) [this.questions[idx], this.questions[idx + 1]] = [this.questions[idx + 1], this.questions[idx]];
            },
        };
    }
</script>
