<x-layouts::app :title="__('Review Generated Questions')">
    <div class="space-y-6">
        <x-admin-header :title="__('Review Generated Questions')" />

        <flux:callout variant="info" icon="sparkles">
            {{ __('AI generated the questions below. Review, edit, add, or remove questions before saving.') }}
        </flux:callout>

        <form method="POST" action="{{ route('teacher.quizzes.store') }}" x-data="{ ...quizReviewer(), submitting: false }" @submit="submitting = true">
            @csrf
            <input type="hidden" name="source_type" value="{{ $sourceType }}">
            @if ($sourcePrompt)
                <input type="hidden" name="source_prompt" value="{{ $sourcePrompt }}">
            @endif
            @if ($sourceDocumentUrl)
                <input type="hidden" name="source_document_url" value="{{ $sourceDocumentUrl }}">
                <input type="hidden" name="source_document_public_id" value="{{ $sourceDocumentPublicId }}">
            @endif

            <div class="space-y-4">
                {{-- Quiz Settings --}}
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 sm:p-5">
                    <h3 class="text-base font-semibold text-zinc-900 dark:text-white mb-4">{{ __('Quiz Settings') }}</h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <flux:input name="title" label="{{ __('Quiz Title') }}" required placeholder="{{ __('e.g., Photosynthesis Quiz') }}" />
                        </div>
                        <div>
                            <flux:select name="class_id" label="{{ __('Class') }}" required>
                                @foreach ($classes as $class)
                                    <option value="{{ $class->id }}" @selected($class->id === $selectedClassId)>{{ $class->name }}</option>
                                @endforeach
                            </flux:select>
                        </div>
                    </div>

                    <div class="mt-4">
                        <flux:textarea name="description" label="{{ __('Description (optional)') }}" rows="2" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4">
                        <div>
                            <flux:input type="number" name="time_limit_minutes" label="{{ __('Time Limit (min)') }}" placeholder="{{ __('No limit') }}" min="1" max="180" />
                        </div>
                        <div>
                            <flux:input type="number" name="passing_score" label="{{ __('Passing Score (%)') }}" value="50" required min="1" max="100" />
                        </div>
                        <div>
                            <flux:input type="number" name="max_attempts" label="{{ __('Max Attempts') }}" value="1" required min="1" max="10" />
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-4 mt-4">
                        <label class="inline-flex items-center gap-2">
                            <input type="hidden" name="shuffle_questions" value="0">
                            <input type="checkbox" name="shuffle_questions" value="1" class="rounded border-zinc-300">
                            <span class="text-sm">{{ __('Shuffle questions') }}</span>
                        </label>
                        <label class="inline-flex items-center gap-2">
                            <input type="hidden" name="shuffle_options" value="0">
                            <input type="checkbox" name="shuffle_options" value="1" class="rounded border-zinc-300">
                            <span class="text-sm">{{ __('Shuffle options') }}</span>
                        </label>
                        <label class="inline-flex items-center gap-2">
                            <input type="hidden" name="show_correct_answers" value="0">
                            <input type="checkbox" name="show_correct_answers" value="1" checked class="rounded border-zinc-300">
                            <span class="text-sm">{{ __('Show answers after submission') }}</span>
                        </label>
                    </div>
                </div>

                {{-- Questions --}}
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 sm:p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base font-semibold text-zinc-900 dark:text-white">
                            {{ __('Questions') }} (<span x-text="questions.length"></span>)
                        </h3>
                        <flux:button type="button" variant="subtle" size="sm" @click="addQuestion()">
                            <flux:icon name="plus" class="size-4 mr-1" /> {{ __('Add Question') }}
                        </flux:button>
                    </div>

                    <template x-for="(question, qIndex) in questions" :key="qIndex">
                        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 mb-4">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-medium text-zinc-600 dark:text-zinc-400" x-text="'Q' + (qIndex + 1)"></span>
                                    <select :name="'questions[' + qIndex + '][type]'" x-model="question.type" @change="onTypeChange(qIndex)"
                                        class="rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 text-xs py-1">
                                        <option value="multiple_choice">{{ __('Multiple Choice') }}</option>
                                        <option value="true_false">{{ __('True / False') }}</option>
                                        <option value="fill_blank">{{ __('Fill in Blank') }}</option>
                                    </select>
                                </div>
                                <button type="button" @click="removeQuestion(qIndex)" class="text-red-500 hover:text-red-700 text-sm" x-show="questions.length > 1">
                                    <flux:icon name="trash" class="size-4" />
                                </button>
                            </div>

                            <div class="space-y-3">
                                <textarea :name="'questions[' + qIndex + '][question_text]'" x-model="question.question_text" rows="2"
                                    class="w-full rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 text-sm" required></textarea>

                                <template x-if="question.type === 'multiple_choice'">
                                    <div class="space-y-2">
                                        <template x-for="(opt, oIndex) in question.options" :key="oIndex">
                                            <div class="flex items-center gap-2">
                                                <input type="radio" :name="'correct_' + qIndex" :value="oIndex"
                                                    :checked="question.correct_answer === question.options[oIndex]"
                                                    @change="question.correct_answer = question.options[oIndex]"
                                                    class="text-indigo-600 focus:ring-indigo-500">
                                                <input type="text" x-model="question.options[oIndex]"
                                                    :name="'questions[' + qIndex + '][options][' + oIndex + ']'"
                                                    class="flex-1 rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 text-sm" required>
                                            </div>
                                        </template>
                                        <input type="hidden" :name="'questions[' + qIndex + '][correct_answer]'" :value="question.correct_answer">
                                    </div>
                                </template>

                                <template x-if="question.type === 'true_false'">
                                    <div>
                                        <input type="hidden" :name="'questions[' + qIndex + '][options][0]'" value="True">
                                        <input type="hidden" :name="'questions[' + qIndex + '][options][1]'" value="False">
                                        <div class="flex items-center gap-4">
                                            <label class="inline-flex items-center gap-2">
                                                <input type="radio" :name="'correct_' + qIndex" value="True"
                                                    :checked="question.correct_answer === 'True'"
                                                    @change="question.correct_answer = 'True'" class="text-indigo-600">
                                                <span class="text-sm">{{ __('True') }}</span>
                                            </label>
                                            <label class="inline-flex items-center gap-2">
                                                <input type="radio" :name="'correct_' + qIndex" value="False"
                                                    :checked="question.correct_answer === 'False'"
                                                    @change="question.correct_answer = 'False'" class="text-indigo-600">
                                                <span class="text-sm">{{ __('False') }}</span>
                                            </label>
                                        </div>
                                        <input type="hidden" :name="'questions[' + qIndex + '][correct_answer]'" :value="question.correct_answer">
                                    </div>
                                </template>

                                <template x-if="question.type === 'fill_blank'">
                                    <div>
                                        <input type="hidden" :name="'questions[' + qIndex + '][options][0]'" value="">
                                        <input type="text" :name="'questions[' + qIndex + '][correct_answer]'" x-model="question.correct_answer"
                                            class="w-full rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 text-sm"
                                            placeholder="{{ __('Correct answer...') }}" required>
                                    </div>
                                </template>

                                <textarea :name="'questions[' + qIndex + '][explanation]'" x-model="question.explanation" rows="1"
                                    class="w-full rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 text-sm"
                                    placeholder="{{ __('Explanation (optional)') }}"></textarea>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="flex items-center gap-3">
                    <flux:button type="submit" variant="primary" x-bind:disabled="submitting">
                        <span x-show="!submitting">{{ __('Save & Submit for Approval') }}</span>
                        <span x-show="submitting" x-cloak class="inline-flex items-center gap-2">
                            <flux:icon name="arrow-path" class="size-4 animate-spin" />
                            {{ __('Saving...') }}
                        </span>
                    </flux:button>
                    <flux:button variant="subtle" href="{{ route('teacher.quizzes.create') }}" wire:navigate x-show="!submitting">{{ __('Cancel') }}</flux:button>
                </div>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        function quizReviewer() {
            // Convert AI-generated questions to editable format
            let aiQuestions = @json($questions);

            // Handle wrapped responses like {"questions": [...]}
            if (aiQuestions && !Array.isArray(aiQuestions) && typeof aiQuestions === 'object') {
                const keys = Object.keys(aiQuestions);
                if (keys.length === 1 && Array.isArray(aiQuestions[keys[0]])) {
                    aiQuestions = aiQuestions[keys[0]];
                } else if (aiQuestions.questions) {
                    aiQuestions = aiQuestions.questions;
                }
            }

            const mapped = Array.isArray(aiQuestions) ? aiQuestions.map(q => ({
                type: q.type || 'multiple_choice',
                question_text: q.question || q.question_text || '',
                options: q.options || ['', '', '', ''],
                correct_answer: q.correct_answer || '',
                explanation: q.explanation || '',
            })) : [];

            return {
                questions: mapped.length > 0 ? mapped : [{
                    type: 'multiple_choice',
                    question_text: '',
                    options: ['', '', '', ''],
                    correct_answer: '',
                    explanation: '',
                }],
                addQuestion() {
                    this.questions.push({
                        type: 'multiple_choice',
                        question_text: '',
                        options: ['', '', '', ''],
                        correct_answer: '',
                        explanation: '',
                    });
                },
                removeQuestion(index) {
                    this.questions.splice(index, 1);
                },
                onTypeChange(index) {
                    const q = this.questions[index];
                    if (q.type === 'true_false') {
                        q.options = ['True', 'False'];
                        q.correct_answer = 'True';
                    } else if (q.type === 'fill_blank') {
                        q.options = [''];
                        q.correct_answer = '';
                    } else {
                        q.options = ['', '', '', ''];
                        q.correct_answer = '';
                    }
                },
            };
        }
    </script>
    @endpush
</x-layouts::app>
