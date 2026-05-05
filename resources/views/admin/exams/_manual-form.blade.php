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
    $routePrefix = $routePrefix ?? 'admin.exams';
    $rolePrefix = explode('.', $routePrefix)[0]; // 'admin' or 'teacher'
    $bankSaveUrl = route($rolePrefix . '.question-bank.save-from-exam');
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

            {{-- Row 1: section heading + bank-save controls --}}
            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div>
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Questions') }}</h3>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5"
                        x-text="questions.length > 0
                            ? questions.length + ' question' + (questions.length !== 1 ? 's' : '') + ' · ' + totalPoints + ' {{ __('pts total') }}'
                            : '{{ __('No questions yet — add one using the buttons below.') }}'">
                    </p>
                </div>
                <div class="flex items-center gap-2 flex-wrap shrink-0">
                    <button
                        type="button"
                        x-show="questions.length > 0"
                        x-cloak
                        @click="toggleSelectAllForBank()"
                        class="inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-lg border border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors"
                        x-text="bankSelectedCount > 0 && bankSelectedCount === questions.filter(q => q._bankId === undefined).length ? '{{ __('Deselect All') }}' : '{{ __('Select All') }}'"
                    ></button>
                    <button
                        type="button"
                        x-show="bankSelectedCount > 0"
                        x-cloak
                        @click="saveSelectedToBank()"
                        class="inline-flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-semibold transition-colors shadow-sm"
                    >
                        <svg class="size-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
                        <span x-text="`{{ __('Save') }} ${bankSelectedCount} {{ __('to Bank') }}`"></span>
                    </button>
                </div>
            </div>

            {{-- Row 2: Add question panel --}}
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 px-4 py-3 space-y-2.5">
                <p class="text-[11px] font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500 select-none">
                    {{ __('Click a type to add a question ↓') }}
                </p>
                <div class="flex flex-wrap gap-2">

                    {{-- Multiple Choice --}}
                    <button type="button" @click="addQuestion('multiple_choice')"
                        class="group inline-flex items-center gap-2 rounded-lg border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-3 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-200 shadow-sm transition-all hover:border-blue-300 dark:hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/30 hover:text-blue-700 dark:hover:text-blue-300 hover:shadow"
                        title="{{ __('Multiple Choice: 4 options (A, B, C, D), one correct answer') }}">
                        <span class="inline-flex h-5 w-7 shrink-0 items-center justify-center rounded bg-blue-100 dark:bg-blue-900/50 text-[10px] font-bold text-blue-600 dark:text-blue-400 group-hover:bg-blue-200 dark:group-hover:bg-blue-800 transition-colors leading-none tracking-tight">A–D</span>
                        {{ __('Multiple Choice') }}
                    </button>

                    {{-- True / False --}}
                    <button type="button" @click="addQuestion('true_false')"
                        class="group inline-flex items-center gap-2 rounded-lg border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-3 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-200 shadow-sm transition-all hover:border-emerald-300 dark:hover:border-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 hover:text-emerald-700 dark:hover:text-emerald-300 hover:shadow"
                        title="{{ __('True / False: student picks True or False') }}">
                        <span class="inline-flex h-5 w-7 shrink-0 items-center justify-center rounded bg-emerald-100 dark:bg-emerald-900/50 text-[10px] font-bold text-emerald-600 dark:text-emerald-400 group-hover:bg-emerald-200 dark:group-hover:bg-emerald-800 transition-colors leading-none tracking-tight">T/F</span>
                        {{ __('True / False') }}
                    </button>

                    {{-- Fill in the Blank --}}
                    <button type="button" @click="addQuestion('fill_blank')"
                        class="group inline-flex items-center gap-2 rounded-lg border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-3 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-200 shadow-sm transition-all hover:border-amber-300 dark:hover:border-amber-500 hover:bg-amber-50 dark:hover:bg-amber-900/30 hover:text-amber-700 dark:hover:text-amber-300 hover:shadow"
                        title="{{ __('Fill in the Blank: student types a short word or phrase') }}">
                        <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded bg-amber-100 dark:bg-amber-900/50 text-amber-600 dark:text-amber-400 group-hover:bg-amber-200 dark:group-hover:bg-amber-800 transition-colors">
                            <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487z"/></svg>
                        </span>
                        {{ __('Fill in Blank') }}
                    </button>

                    {{-- Short Answer --}}
                    <button type="button" @click="addQuestion('short_answer')"
                        class="group inline-flex items-center gap-2 rounded-lg border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-3 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-200 shadow-sm transition-all hover:border-violet-300 dark:hover:border-violet-500 hover:bg-violet-50 dark:hover:bg-violet-900/30 hover:text-violet-700 dark:hover:text-violet-300 hover:shadow"
                        title="{{ __('Short Answer: 1–3 sentence response, manually graded') }}">
                        <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded bg-violet-100 dark:bg-violet-900/50 text-violet-600 dark:text-violet-400 group-hover:bg-violet-200 dark:group-hover:bg-violet-800 transition-colors">
                            <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"/></svg>
                        </span>
                        {{ __('Short Answer') }}
                    </button>

                    {{-- Theory --}}
                    <button type="button" @click="addQuestion('theory')"
                        class="group inline-flex items-center gap-2 rounded-lg border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-3 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-200 shadow-sm transition-all hover:border-rose-300 dark:hover:border-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/30 hover:text-rose-700 dark:hover:text-rose-300 hover:shadow"
                        title="{{ __('Theory: long-form essay, manually graded (AI assist available)') }}">
                        <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded bg-rose-100 dark:bg-rose-900/50 text-rose-600 dark:text-rose-400 group-hover:bg-rose-200 dark:group-hover:bg-rose-800 transition-colors">
                            <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                        </span>
                        {{ __('Theory') }}
                    </button>

                    {{-- Matching --}}
                    <button type="button" @click="addQuestion('matching')"
                        class="group inline-flex items-center gap-2 rounded-lg border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-3 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-200 shadow-sm transition-all hover:border-orange-300 dark:hover:border-orange-500 hover:bg-orange-50 dark:hover:bg-orange-900/30 hover:text-orange-700 dark:hover:text-orange-300 hover:shadow"
                        title="{{ __('Matching: pair items in Column A with items in Column B') }}">
                        <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded bg-orange-100 dark:bg-orange-900/50 text-orange-600 dark:text-orange-400 group-hover:bg-orange-200 dark:group-hover:bg-orange-800 transition-colors">
                            <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12"/></svg>
                        </span>
                        {{ __('Matching') }}
                    </button>

                    <span class="self-center border-l border-zinc-300 dark:border-zinc-600 h-6 mx-0.5"></span>

                    {{-- Import from Bank --}}
                    <button type="button" @click="$dispatch('open-question-bank-browser')"
                        class="inline-flex items-center gap-2 rounded-lg border border-indigo-200 dark:border-indigo-700 bg-indigo-50 dark:bg-indigo-900/30 px-3 py-2 text-sm font-medium text-indigo-700 dark:text-indigo-300 shadow-sm transition-all hover:border-indigo-400 dark:hover:border-indigo-500 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 hover:shadow"
                        title="{{ __('Browse your Question Bank and import saved questions') }}">
                        <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
                        {{ __('Import from Bank') }}
                    </button>

                </div>
            </div>

            <template x-for="(q, idx) in questions" :key="q.id">
                <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg p-4 space-y-3 bg-white dark:bg-zinc-800">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <input
                                type="checkbox"
                                x-show="q._bankId === undefined"
                                :checked="!!bankSelected[q.id]"
                                @change="bankSelected[q.id] = $event.target.checked"
                                class="h-3.5 w-3.5 cursor-pointer rounded border-zinc-300 dark:border-zinc-600 text-indigo-600 focus:ring-1 focus:ring-indigo-500"
                                :title="'{{ __('Select to save to Question Bank') }}'"
                            >
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
                            {{-- Save to Bank --}}
                            <button
                                type="button"
                                :id="'save-bank-' + idx"
                                :data-question-idx="idx"
                                x-show="q._bankId === undefined"
                                x-on:click="saveQuestionToBank(idx)"
                                class="text-xs px-2 py-1 rounded border border-zinc-200 dark:border-zinc-700 text-zinc-500 hover:text-indigo-600 hover:border-indigo-300 transition-colors"
                                title="{{ __('Save this question to the Question Bank for reuse') }}"
                            >
                                <svg class="size-3.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
                                {{ __('Bank') }}
                            </button>
                            <span x-show="q._bankId !== undefined" class="text-xs text-green-500 font-medium" title="{{ __('Saved to bank') }}">✓ {{ __('Banked') }}</span>
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

        {{-- Bank save toast (inside Alpine scope so it can access bankToast) --}}
        <div
            x-show="bankToast !== null"
            x-cloak
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-2"
            class="fixed bottom-6 right-6 z-[9999] flex items-center gap-3 px-4 py-3 rounded-xl shadow-xl border max-w-sm w-full pointer-events-auto"
            :class="bankToast?.type === 'success'
                ? 'bg-white dark:bg-zinc-800 border-green-200 dark:border-green-700'
                : 'bg-white dark:bg-zinc-800 border-red-200 dark:border-red-700'"
        >
            <template x-if="bankToast?.type === 'success'">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/40">
                    <svg class="size-4 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </template>
            <template x-if="bankToast?.type === 'error'">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/40">
                    <svg class="size-4 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                </div>
            </template>
            <p class="flex-1 min-w-0 text-sm font-medium text-zinc-900 dark:text-zinc-100" x-text="bankToast?.message"></p>
            <button @click="bankToast = null" class="shrink-0 rounded-md p-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
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
            bankSelected: {},
            bankToast: null,
            get bankSelectedCount() {
                return Object.values(this.bankSelected).filter(Boolean).length;
            },
            showBankToast(type, message) {
                this.bankToast = { type, message };
                setTimeout(() => { this.bankToast = null; }, 4000);
            },
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
            saveQuestionToBank(idx) {
                const q = this.questions[idx];
                fetch('{{ $bankSaveUrl }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        type: q.type,
                        question_text: q.question_text,
                        options: q.options,
                        correct_answer: q.correct_answer,
                        marking_guide: q.marking_guide ?? null,
                        sample_answer: q.sample_answer ?? null,
                        min_words: q.min_words ?? null,
                        max_words: q.max_words ?? null,
                        explanation: q.explanation ?? null,
                        points: q.points,
                        _from_editor: true,
                    }),
                })
                .then(r => r.json())
                .then(data => {
                    if (data.id) {
                        q._bankId = data.id;
                        this.bankSelected[q.id] = false;
                        this.showBankToast('success', '{{ __('Question saved to Question Bank!') }}');
                    } else {
                        this.showBankToast('error', data.message ?? '{{ __('Could not save to bank.') }}');
                    }
                })
                .catch(() => this.showBankToast('error', '{{ __('Error saving to Question Bank.') }}'));
            },
            toggleSelectAllForBank() {
                const saveable = this.questions.filter(q => q._bankId === undefined);
                if (!saveable.length) return;
                const allSelected = saveable.every(q => !!this.bankSelected[q.id]);
                saveable.forEach(q => { this.bankSelected[q.id] = !allSelected; });
            },
            saveSelectedToBank() {
                const toSave = this.questions.filter(q => q._bankId === undefined && !!this.bankSelected[q.id]);
                if (!toSave.length) return;
                let saved = 0, failed = 0;
                const headers = {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '{{ csrf_token() }}',
                    'Accept': 'application/json',
                };
                const promises = toSave.map(q =>
                    fetch('{{ $bankSaveUrl }}', {
                        method: 'POST',
                        headers,
                        body: JSON.stringify({
                            type: q.type,
                            question_text: q.question_text,
                            options: q.options,
                            correct_answer: q.correct_answer,
                            marking_guide: q.marking_guide ?? null,
                            sample_answer: q.sample_answer ?? null,
                            min_words: q.min_words ?? null,
                            max_words: q.max_words ?? null,
                            explanation: q.explanation ?? null,
                            points: q.points,
                            _from_editor: true,
                        }),
                    })
                    .then(r => r.json())
                    .then(data => { if (data.id) { q._bankId = data.id; this.bankSelected[q.id] = false; saved++; } else failed++; })
                    .catch(() => failed++)
                );
                Promise.all(promises).then(() => {
                    if (saved > 0 && failed === 0) {
                        this.showBankToast('success', saved === 1 ? '{{ __('1 question saved to Question Bank!') }}' : `${saved} {{ __('questions saved to Question Bank!') }}`);
                    } else if (saved > 0) {
                        this.showBankToast('error', `${saved} {{ __('saved') }}, ${failed} {{ __('failed') }}.`);
                    } else {
                        this.showBankToast('error', '{{ __('Could not save questions to Question Bank.') }}');
                    }
                });
            },
            init() {
                // Listen for questions imported from QuestionBankBrowser (Livewire dispatches browser event)
                window.addEventListener('questions-from-bank', (e) => {
                    if (!e.detail || !e.detail.questions) return;
                    e.detail.questions.forEach(q => {
                        this.questions.push({
                            id: this.nextId++,
                            _bankId: q.id,
                            type: q.type,
                            question_text: q.question_text,
                            options: q.options ?? [],
                            correct_answer: q.correct_answer ?? '',
                            marking_guide: q.marking_guide ?? '',
                            sample_answer: q.sample_answer ?? '',
                            min_words: q.min_words ?? null,
                            max_words: q.max_words ?? null,
                            explanation: q.explanation ?? '',
                            points: q.points ?? 1,
                            section_label: '',
                        });
                    });
                });
            },
        };
    }
</script>

{{-- Question Bank Browser (Livewire modal — renders its own overlay) --}}
@livewire('admin.question-bank-browser')
