{{-- Shared question form partial (create & edit) --}}
{{-- Requires x-data="questionForm()" on the parent <form> --}}

{{-- Type & Subject row --}}
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('Question Type') }}</label>
        <select name="type" x-model="type" class="w-full rounded-md border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            <option value="multiple_choice">MCQ (Multiple Choice)</option>
            <option value="true_false">True / False</option>
            <option value="fill_blank">Fill in the Blank</option>
            <option value="short_answer">Short Answer</option>
            <option value="theory">Theory / Essay</option>
            <option value="matching">Matching</option>
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('Subject') }} <span class="text-zinc-400">(optional)</span></label>
        <select name="subject_id" x-model="subject_id" class="w-full rounded-md border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            <option value="">{{ __('— No subject —') }}</option>
            @foreach ($subjects as $subject)
                <option value="{{ $subject->id }}">{{ $subject->name }}</option>
            @endforeach
        </select>
    </div>
</div>

{{-- Points & Difficulty --}}
<div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
    <div>
        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('Points') }}</label>
        <input type="number" name="points" x-model="points" min="1" max="100" class="w-full rounded-md border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
    </div>
    <div>
        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('Difficulty') }}</label>
        <select name="difficulty" x-model="difficulty" class="w-full rounded-md border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            <option value="easy">Easy</option>
            <option value="medium">Medium</option>
            <option value="hard">Hard</option>
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('Tags') }} <span class="text-zinc-400">(comma-separated)</span></label>
        <input type="text" name="tags" x-model="tags" placeholder="e.g. algebra, fractions" class="w-full rounded-md border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
    </div>
</div>

{{-- Question Text --}}
<div>
    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('Question') }}</label>
    <textarea name="question_text" x-model="question_text" rows="3" required class="w-full rounded-md border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none resize-y" placeholder="{{ __('Enter the question text...') }}"></textarea>
</div>

{{-- MCQ Options --}}
<div x-show="isMCQ">
    <div class="flex items-center justify-between mb-2">
        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Answer Options') }}</label>
        <button type="button" @click="addOption()" class="text-xs text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 font-medium">+ {{ __('Add option') }}</button>
    </div>
    <div class="space-y-2">
        <template x-for="(opt, i) in options" :key="i">
            <div class="flex items-center gap-2">
                <label class="flex items-center gap-2 flex-1 min-w-0">
                    <input
                        type="radio"
                        name="correct_answer"
                        :value="opt"
                        :checked="correctAnswer === opt && opt !== ''"
                        @change="correctAnswer = opt"
                        class="text-green-600 focus:ring-green-500"
                        title="{{ __('Mark as correct') }}"
                    />
                    <input
                        type="text"
                        :name="`options[${i}]`"
                        x-model="options[i]"
                        :placeholder="`${String.fromCharCode(65 + i)}. Option`"
                        class="flex-1 min-w-0 rounded-md border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-1.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                        :class="correctAnswer === opt && opt !== '' ? 'border-green-400 dark:border-green-600 bg-green-50 dark:bg-green-900/20' : ''"
                    />
                </label>
                <button type="button" @click="removeOption(i)" x-show="options.length > 2" class="text-red-400 hover:text-red-600 p-1 rounded" tabindex="-1">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </template>
    </div>
    {{-- hidden correct answer for submission --}}
    <input type="hidden" name="correct_answer" :value="correctAnswer" />
    <p class="text-xs text-zinc-400 mt-1">{{ __('Click the radio button to mark the correct answer.') }}</p>
</div>

{{-- True/False --}}
<div x-show="isTrueFalse">
    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">{{ __('Correct Answer') }}</label>
    <div class="flex gap-4">
        <label class="flex items-center gap-2">
            <input type="radio" name="correct_answer" value="True" :checked="correctAnswer === 'True'" @change="correctAnswer = 'True'" x-show="isTrueFalse" class="text-green-600" />
            <span class="text-sm">True</span>
        </label>
        <label class="flex items-center gap-2">
            <input type="radio" name="correct_answer" value="False" :checked="correctAnswer === 'False'" @change="correctAnswer = 'False'" x-show="isTrueFalse" class="text-green-600" />
            <span class="text-sm">False</span>
        </label>
    </div>
</div>

{{-- Fill in the blank / short answer correct answer --}}
<div x-show="['fill_blank', 'short_answer', 'matching'].includes(type)">
    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('Correct Answer / Answer Key') }}</label>
    <input type="text" name="correct_answer" x-model="correctAnswer" class="w-full rounded-md border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
</div>

{{-- Theory fields --}}
<div x-show="isTheory" class="space-y-3">
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('Min Words') }} <span class="text-zinc-400">(optional)</span></label>
            <input type="number" name="min_words" x-model="min_words" min="1" placeholder="—" class="w-full rounded-md border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
        </div>
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('Max Words') }} <span class="text-zinc-400">(optional)</span></label>
            <input type="number" name="max_words" x-model="max_words" min="1" placeholder="—" class="w-full rounded-md border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
        </div>
    </div>
    <div>
        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('Marking Guide') }} <span class="text-zinc-400">(shown to teacher when grading)</span></label>
        <textarea name="marking_guide" x-model="marking_guide" rows="3" class="w-full rounded-md border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none resize-y" placeholder="{{ __('What should the answer include? Award marks for...') }}"></textarea>
    </div>
    <div>
        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('Sample Answer') }} <span class="text-zinc-400">(model answer)</span></label>
        <textarea name="sample_answer" x-model="sample_answer" rows="3" class="w-full rounded-md border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none resize-y" placeholder="{{ __('A full model answer...') }}"></textarea>
    </div>
</div>

{{-- Explanation (all types) --}}
<div>
    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('Explanation') }} <span class="text-zinc-400">(shown after answering)</span></label>
    <textarea name="explanation" x-model="explanation" rows="2" class="w-full rounded-md border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none resize-y" placeholder="{{ __('Brief explanation of the answer...') }}"></textarea>
</div>
