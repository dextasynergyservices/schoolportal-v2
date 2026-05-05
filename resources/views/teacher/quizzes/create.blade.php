<x-layouts::app :title="__('Create Quiz')">
    <div class="space-y-6">
        <x-admin-header :title="__('Create Quiz')" />

        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 sm:p-6" x-data="{ tab: '{{ request()->query('tab', 'prompt') }}' }">
            @if (session('error'))
                <flux:callout variant="danger" icon="x-circle" class="mb-6">
                    <flux:callout.heading>{{ __('AI Generation Failed') }}</flux:callout.heading>
                    <flux:callout.text>{{ session('error') }}</flux:callout.text>
                    <div class="mt-3">
                        <flux:button size="sm" variant="ghost" icon="pencil-square" @click="tab = 'manual'">
                            {{ __('Create Manually Instead') }}
                        </flux:button>
                    </div>
                </flux:callout>
            @endif

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-6">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Choose how to create your quiz') }}</h2>
                <div class="text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('AI credits remaining:') }}
                    <span class="font-semibold {{ $availableCredits > 0 ? 'text-green-600' : 'text-red-600' }}">{{ $availableCredits }}</span>
                </div>
            </div>

            <div class="space-y-6">
                {{-- Tab buttons --}}
                <div class="flex flex-wrap gap-2 border-b border-zinc-200 dark:border-zinc-700 pb-3">
                    <button type="button" @click="tab = 'prompt'"
                        :class="tab === 'prompt' ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' : 'bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-600'"
                        class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition">
                        <flux:icon name="chat-bubble-left-right" class="size-4" />
                        {{ __('From Prompt') }}
                    </button>
                    <button type="button" @click="tab = 'document'"
                        :class="tab === 'document' ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' : 'bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-600'"
                        class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition">
                        <flux:icon name="document-text" class="size-4" />
                        {{ __('From Document') }}
                    </button>
                    <button type="button" @click="tab = 'manual'"
                        :class="tab === 'manual' ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' : 'bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-600'"
                        class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition">
                        <flux:icon name="pencil-square" class="size-4" />
                        {{ __('Manual (Free)') }}
                    </button>
                </div>

                {{-- AI: From Prompt --}}
                <div x-show="tab === 'prompt'" x-cloak>
                    @if ($availableCredits > 0)
                        <form method="POST" action="{{ route('teacher.quizzes.generate') }}" x-data="{ submitting: false }" @submit="submitting = true">
                            @csrf
                            <input type="hidden" name="source_type" value="prompt">
                            <div class="space-y-4">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <flux:select name="class_id" label="{{ __('Class') }}" required>
                                            <option value="">{{ __('Select class...') }}</option>
                                            @foreach ($classes as $class)
                                                <option value="{{ $class->id }}" @selected(old('class_id') == $class->id)>{{ $class->name }}</option>
                                            @endforeach
                                        </flux:select>
                                        @error('class_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <flux:select name="difficulty" label="{{ __('Difficulty') }}" required>
                                            <option value="easy">{{ __('Easy') }}</option>
                                            <option value="medium" selected>{{ __('Medium') }}</option>
                                            <option value="hard">{{ __('Hard') }}</option>
                                        </flux:select>
                                    </div>
                                </div>

                                <div>
                                    <flux:textarea name="prompt" label="{{ __('Prompt') }}" rows="3" required
                                        placeholder="{{ __('e.g., Generate questions about photosynthesis covering light reactions, Calvin cycle, and chloroplast structure') }}"
                                    >{{ old('prompt') }}</flux:textarea>
                                    @error('prompt') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <flux:select name="question_count" label="{{ __('Number of Questions') }}" required>
                                            <option value="5">5</option>
                                            <option value="10" selected>10</option>
                                            <option value="15">15</option>
                                            <option value="20">20</option>
                                        </flux:select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">{{ __('Question Types') }}</label>
                                        <div class="flex flex-wrap gap-3">
                                            <label class="inline-flex items-center gap-2">
                                                <input type="checkbox" name="question_types[]" value="multiple_choice" checked class="rounded border-zinc-300 dark:border-zinc-600">
                                                <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ __('Multiple Choice') }}</span>
                                            </label>
                                            <label class="inline-flex items-center gap-2">
                                                <input type="checkbox" name="question_types[]" value="true_false" class="rounded border-zinc-300 dark:border-zinc-600">
                                                <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ __('True/False') }}</span>
                                            </label>
                                            <label class="inline-flex items-center gap-2">
                                                <input type="checkbox" name="question_types[]" value="fill_blank" class="rounded border-zinc-300 dark:border-zinc-600">
                                                <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ __('Fill in the Blank') }}</span>
                                            </label>
                                        </div>
                                        @error('question_types') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                </div>

                                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 pt-2">
                                    <flux:button type="submit" variant="primary" x-bind:disabled="submitting">
                                        <span x-show="!submitting">{{ __('Generate Questions (1 credit)') }}</span>
                                        <span x-show="submitting" x-cloak class="inline-flex items-center gap-2">
                                            <flux:icon name="arrow-path" class="size-4 animate-spin" />
                                            {{ __('Generating...') }}
                                        </span>
                                    </flux:button>
                                    <p class="text-xs text-zinc-500" x-show="!submitting">{{ __('You can review and edit questions before saving.') }}</p>
                                    <p class="text-xs text-amber-600" x-show="submitting" x-cloak>{{ __('AI is generating your questions. This may take 15-30 seconds...') }}</p>
                                </div>
                            </div>
                        </form>
                    @else
                        @include('teacher.quizzes._no-credits')
                    @endif
                </div>

                {{-- AI: From Document --}}
                <div x-show="tab === 'document'" x-cloak>
                    @if ($availableCredits > 0)
                        <form method="POST" action="{{ route('teacher.quizzes.generate') }}" x-data="documentUploader()" @submit="return onSubmit($event)">
                            @csrf
                            <input type="hidden" name="source_type" value="document">
                            <input type="hidden" name="document_url" :value="documentUrl">
                            <input type="hidden" name="document_public_id" :value="documentPublicId">

                            {{-- Inline validation error --}}
                            <div x-show="validationError" x-cloak x-transition class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/30 p-3 flex items-center gap-2">
                                <flux:icon name="exclamation-triangle" class="size-5 text-red-500 shrink-0" />
                                <span class="text-sm text-red-700 dark:text-red-300" x-text="validationError"></span>
                                <button type="button" @click="validationError = ''" class="ml-auto text-red-400 hover:text-red-600">&times;</button>
                            </div>

                            <div class="space-y-4">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <flux:select name="class_id" label="{{ __('Class') }}" required>
                                            <option value="">{{ __('Select class...') }}</option>
                                            @foreach ($classes as $class)
                                                <option value="{{ $class->id }}">{{ $class->name }}</option>
                                            @endforeach
                                        </flux:select>
                                    </div>
                                    <div>
                                        <flux:select name="difficulty" label="{{ __('Difficulty') }}" required>
                                            <option value="easy">{{ __('Easy') }}</option>
                                            <option value="medium" selected>{{ __('Medium') }}</option>
                                            <option value="hard">{{ __('Hard') }}</option>
                                        </flux:select>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">{{ __('Upload Document (PDF)') }}</label>
                                    <div class="border-2 border-dashed border-zinc-300 dark:border-zinc-600 rounded-lg p-6 text-center cursor-pointer hover:border-indigo-400 transition"
                                        @click="openUploader()">
                                        <template x-if="!fileName">
                                            <div>
                                                <flux:icon name="document-arrow-up" class="mx-auto h-10 w-10 text-zinc-400" />
                                                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ __('Click to upload a PDF document') }}</p>
                                                <p class="mt-1 text-xs text-zinc-400">{{ __('Max 10MB, PDF format') }}</p>
                                            </div>
                                        </template>
                                        <template x-if="fileName">
                                            <div class="flex items-center justify-center gap-3">
                                                <flux:icon name="document-check" class="size-5 text-green-600" />
                                                <span class="text-sm text-green-700 dark:text-green-400 font-medium" x-text="fileName"></span>
                                                <button type="button" @click.stop="removeDocument()"
                                                    class="text-sm text-zinc-500 hover:text-red-600 underline">{{ __('Remove') }}</button>
                                            </div>
                                        </template>
                                    </div>
                                    <p class="mt-1 text-xs text-zinc-400" x-show="uploading" x-cloak>{{ __('Uploading document...') }}</p>
                                    @error('document_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <flux:select name="question_count" label="{{ __('Number of Questions') }}" required>
                                            <option value="5">5</option>
                                            <option value="10" selected>10</option>
                                            <option value="15">15</option>
                                            <option value="20">20</option>
                                        </flux:select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">{{ __('Question Types') }}</label>
                                        <div class="flex flex-wrap gap-3">
                                            <label class="inline-flex items-center gap-2">
                                                <input type="checkbox" name="question_types[]" value="multiple_choice" checked class="rounded border-zinc-300 dark:border-zinc-600">
                                                <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ __('Multiple Choice') }}</span>
                                            </label>
                                            <label class="inline-flex items-center gap-2">
                                                <input type="checkbox" name="question_types[]" value="true_false" class="rounded border-zinc-300 dark:border-zinc-600">
                                                <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ __('True/False') }}</span>
                                            </label>
                                            <label class="inline-flex items-center gap-2">
                                                <input type="checkbox" name="question_types[]" value="fill_blank" class="rounded border-zinc-300 dark:border-zinc-600">
                                                <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ __('Fill in the Blank') }}</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 pt-2">
                                    <flux:button type="submit" variant="primary" x-bind:disabled="!documentUrl || submitting">
                                        <span x-show="!submitting">{{ __('Generate Questions (1 credit)') }}</span>
                                        <span x-show="submitting" x-cloak class="inline-flex items-center gap-2">
                                            <flux:icon name="arrow-path" class="size-4 animate-spin" />
                                            {{ __('Generating...') }}
                                        </span>
                                    </flux:button>
                                    <p class="text-xs text-amber-600" x-show="submitting" x-cloak>{{ __('AI is extracting text and generating questions. This may take 30-60 seconds...') }}</p>
                                </div>
                            </div>
                        </form>
                    @else
                        @include('teacher.quizzes._no-credits')
                    @endif
                </div>

                {{-- Manual Creation --}}
                <div x-show="tab === 'manual'" x-cloak>
                    <form method="POST" action="{{ route('teacher.quizzes.store') }}" x-data="manualQuizCreator()" @submit="return onManualSubmit($event)">
                        @csrf
                        <input type="hidden" name="source_type" value="manual">

                        {{-- Inline validation error --}}
                        <div x-show="validationError" x-cloak x-transition class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/30 p-3 flex items-center gap-2">
                            <flux:icon name="exclamation-triangle" class="size-5 text-red-500 shrink-0" />
                            <span class="text-sm text-red-700 dark:text-red-300" x-text="validationError"></span>
                            <button type="button" @click="validationError = ''" class="ml-auto text-red-400 hover:text-red-600">&times;</button>
                        </div>

                        <div class="space-y-5">
                            {{-- Quiz settings --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <flux:input name="title" label="{{ __('Quiz Title') }}" required placeholder="{{ __('e.g., Photosynthesis Quiz') }}" />
                                    @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <flux:select name="class_id" label="{{ __('Class') }}" required>
                                        <option value="">{{ __('Select class...') }}</option>
                                        @foreach ($classes as $class)
                                            <option value="{{ $class->id }}">{{ $class->name }}</option>
                                        @endforeach
                                    </flux:select>
                                </div>
                            </div>

                            <div>
                                <flux:textarea name="description" label="{{ __('Description (optional)') }}" rows="2"
                                    placeholder="{{ __('Brief description of the quiz') }}" />
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <flux:input type="number" name="time_limit_minutes" label="{{ __('Time Limit (minutes)') }}" placeholder="{{ __('No limit') }}" min="1" max="180" />
                                </div>
                                <div>
                                    <flux:input type="number" name="passing_score" label="{{ __('Passing Score (%)') }}" value="50" required min="1" max="100" />
                                </div>
                                <div>
                                    <flux:input type="number" name="max_attempts" label="{{ __('Max Attempts') }}" value="1" required min="1" max="10" />
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-4">
                                <label class="inline-flex items-center gap-2">
                                    <input type="hidden" name="shuffle_questions" value="0">
                                    <input type="checkbox" name="shuffle_questions" value="1" class="rounded border-zinc-300 dark:border-zinc-600">
                                    <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ __('Shuffle questions') }}</span>
                                </label>
                                <label class="inline-flex items-center gap-2">
                                    <input type="hidden" name="shuffle_options" value="0">
                                    <input type="checkbox" name="shuffle_options" value="1" class="rounded border-zinc-300 dark:border-zinc-600">
                                    <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ __('Shuffle options') }}</span>
                                </label>
                                <label class="inline-flex items-center gap-2">
                                    <input type="hidden" name="show_correct_answers" value="0">
                                    <input type="checkbox" name="show_correct_answers" value="1" checked class="rounded border-zinc-300 dark:border-zinc-600">
                                    <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ __('Show correct answers after submission') }}</span>
                                </label>
                            </div>

                            {{-- Questions Section --}}
                            <div class="border-t border-zinc-200 dark:border-zinc-700 pt-5">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-base font-semibold text-zinc-900 dark:text-white">
                                        {{ __('Questions') }}
                                        <span class="text-sm font-normal text-zinc-500" x-text="'(' + questions.length + ')'"></span>
                                    </h3>
                                    <flux:button type="button" variant="primary" size="sm" @click="addQuestion()">
                                        <flux:icon name="plus" class="size-4 mr-1" /> {{ __('Add Question') }}
                                    </flux:button>
                                </div>

                                <div class="space-y-4">
                                    <template x-for="(question, qIndex) in questions" :key="qIndex">
                                        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
                                            <div class="flex items-center justify-between mb-3">
                                                <div class="flex items-center gap-3">
                                                    <span class="flex items-center justify-center size-7 rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 text-xs font-bold" x-text="qIndex + 1"></span>
                                                    <select :name="'questions[' + qIndex + '][type]'" x-model="question.type" @change="onTypeChange(qIndex)"
                                                        class="rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/10 text-zinc-700 dark:text-zinc-300 shadow-xs text-sm py-1.5 px-20 text-zinc-700 dark:text-zinc-300 shadow-xs text-sm py-1.5 px-2">
                                                        <option value="multiple_choice">{{ __('Multiple Choice') }}</option>
                                                        <option value="true_false">{{ __('True / False') }}</option>
                                                        <option value="fill_blank">{{ __('Fill in the Blank') }}</option>
                                                    </select>
                                                </div>
                                                <button type="button" @click="removeQuestion(qIndex)"
                                                    class="text-red-500 hover:text-red-700 text-xs font-medium" x-show="questions.length > 1">
                                                    <flux:icon name="trash" class="size-4" />
                                                </button>
                                            </div>

                                            <div class="space-y-3">
                                                {{-- Question text --}}
                                                <div>
                                                    <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">{{ __('Question') }}</label>
                                                    <textarea :name="'questions[' + qIndex + '][question_text]'" x-model="question.question_text" rows="2"
                                                        class="w-full rounded-lg border border-zinc-200 border-b-zinc-300/80 dark:border-white/10 bg-white dark:bg-white/10 shadow-xs px-3 py-2 text-sm text-zinc-700 dark:text-zinc-300 placeholder-zinc-400 dark:placeholder-zinc-500/10 bg-white dark:bg-white/10 shadow-xs px-3 py-2 text-sm text-zinc-700 dark:text-zinc-300 placeholder-zinc-400 dark:placeholder-zinc-500"
                                                        placeholder="{{ __('Enter your question here...') }}" required></textarea>
                                                </div>

                                                {{-- Multiple Choice Options --}}
                                                <template x-if="question.type === 'multiple_choice'">
                                                    <div>
                                                        <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-2">{{ __('Options (select the correct answer)') }}</label>
                                                        <div class="space-y-2">
                                                            <template x-for="(opt, oIndex) in question.options" :key="oIndex">
                                                                <div class="flex items-center gap-2">
                                                                    <input type="radio" :name="'correct_' + qIndex" :value="oIndex"
                                                                        :checked="question.correct_answer === question.options[oIndex]"
                                                                        @change="question.correct_answer = question.options[oIndex]"
                                                                        class="text-indigo-600 focus:ring-indigo-500">
                                                                    <span class="text-xs font-medium text-zinc-500 w-5" x-text="String.fromCharCode(65 + oIndex) + '.'"></span>
                                                                    <input type="text" x-model="question.options[oIndex]"
                                                                        @input="if(question.correct_answer === question.options[oIndex]) question.correct_answer = $event.target.value"
                                                                        :name="'questions[' + qIndex + '][options][' + oIndex + ']'"
                                                                        class="flex-1 rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/10 shadow-xs px-2 py-1 text-sm text-zinc-700 dark:text-zinc-300 placeholder-zinc-400 dark:placeholder-zinc-500white/10 shadow-xs px-2 py-1 text-sm text-zinc-700 dark:text-zinc-300 placeholder-zinc-400 dark:placeholder-zinc-500"
                                                                        :placeholder="'{{ __('Option') }} ' + String.fromCharCode(65 + oIndex)" required>
                                                                </div>
                                                            </template>
                                                        </div>
                                                        <input type="hidden" :name="'questions[' + qIndex + '][correct_answer]'" :value="question.correct_answer">
                                                    </div>
                                                </template>

                                                {{-- True/False Options --}}
                                                <template x-if="question.type === 'true_false'">
                                                    <div>
                                                        <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-2">{{ __('Correct Answer') }}</label>
                                                        <input type="hidden" :name="'questions[' + qIndex + '][options][0]'" value="True">
                                                        <input type="hidden" :name="'questions[' + qIndex + '][options][1]'" value="False">
                                                        <div class="flex items-center gap-6">
                                                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                                                <input type="radio" :name="'correct_' + qIndex" value="True"
                                                                    :checked="question.correct_answer === 'True'"
                                                                    @change="question.correct_answer = 'True'" class="text-indigo-600 focus:ring-indigo-500">
                                                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('True') }}</span>
                                                            </label>
                                                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                                                <input type="radio" :name="'correct_' + qIndex" value="False"
                                                                    :checked="question.correct_answer === 'False'"
                                                                    @change="question.correct_answer = 'False'" class="text-indigo-600 focus:ring-indigo-500">
                                                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('False') }}</span>
                                                            </label>
                                                        </div>
                                                        <input type="hidden" :name="'questions[' + qIndex + '][correct_answer]'" :value="question.correct_answer">
                                                    </div>
                                                </template>

                                                {{-- Fill in the Blank --}}
                                                <template x-if="question.type === 'fill_blank'">
                                                    <div>
                                                        <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">{{ __('Correct Answer') }}</label>
                                                        <input type="hidden" :name="'questions[' + qIndex + '][options][0]'" value="">
                                                        <input type="text" :name="'questions[' + qIndex + '][correct_answer]'" x-model="question.correct_answer"
                                                            class="w-full rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/10 shadow-xs px-2 py-1 text-sm text-zinc-700 dark:text-zinc-300 placeholder-zinc-400 dark:placeholder-zinc-500white/10 shadow-xs px-2 py-1 text-sm text-zinc-700 dark:text-zinc-300 placeholder-zinc-400 dark:placeholder-zinc-500"
                                                            placeholder="{{ __('Type the correct answer...') }}" required>
                                                    </div>
                                                </template>

                                                {{-- Explanation --}}
                                                <div>
                                                    <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">{{ __('Explanation (optional)') }}</label>
                                                    <textarea :name="'questions[' + qIndex + '][explanation]'" x-model="question.explanation" rows="1"
                                                        class="w-full rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/10 shadow-xs px-3 py-2 text-sm text-zinc-700 dark:text-zinc-300 placeholder-zinc-400 dark:placeholder-zinc-500white/10 shadow-xs px-3 py-2 text-sm text-zinc-700 dark:text-zinc-300 placeholder-zinc-400 dark:placeholder-zinc-500"
                                                        placeholder="{{ __('Explain why this is the correct answer...') }}"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                {{-- Add more button at bottom --}}
                                <div class="mt-4 text-center">
                                    <button type="button" @click="addQuestion()"
                                        class="inline-flex items-center gap-1 text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 font-medium">
                                        <flux:icon name="plus-circle" class="size-5" />
                                        {{ __('Add Another Question') }}
                                    </button>
                                </div>
                            </div>

                            {{-- Submit --}}
                            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                                <flux:button type="submit" variant="primary" x-bind:disabled="submitting" class="w-full sm:w-auto">
                                    <span x-show="!submitting">{{ __('Save & Submit for Approval') }}</span>
                                    <span x-show="submitting" x-cloak class="inline-flex items-center gap-2">
                                        <flux:icon name="arrow-path" class="size-4 animate-spin" />
                                        {{ __('Saving...') }}
                                    </span>
                                </flux:button>
                                <p class="text-xs text-zinc-500" x-show="!submitting">{{ __('Manual creation is always free. No credits needed.') }}</p>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://upload-widget.cloudinary.com/latest/global/all.js"></script>
    <script>
        function manualQuizCreator() {
            return {
                submitting: false,
                validationError: '',
                questions: [{
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
                    if (this.questions.length > 1) {
                        this.questions.splice(index, 1);
                    }
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
                onManualSubmit(event) {
                    this.validationError = '';
                    // Validate at least one question has content
                    const hasContent = this.questions.some(q => q.question_text.trim() !== '');
                    if (!hasContent) {
                        this.validationError = '{{ __('Please add at least one question.') }}';
                        event.preventDefault();
                        return false;
                    }
                    // Validate correct answers are set
                    for (let i = 0; i < this.questions.length; i++) {
                        const q = this.questions[i];
                        if (!q.correct_answer || q.correct_answer.trim() === '') {
                            this.validationError = '{{ __('Please select the correct answer for Question') }} ' + (i + 1);
                            event.preventDefault();
                            return false;
                        }
                    }
                    this.submitting = true;
                    return true;
                },
            };
        }

        function documentUploader() {
            return {
                documentUrl: '',
                documentPublicId: '',
                fileName: '',
                uploading: false,
                submitting: false,
                validationError: '',
                openUploader() {
                    if (typeof cloudinary !== 'undefined') {
                        const widget = cloudinary.createUploadWidget({
                            cloudName: '{{ config("cloudinary.cloud_name", "") }}',
                            uploadPreset: '{{ config("cloudinary.upload_preset", "ml_default") }}',
                            sources: ['local'],
                            resourceType: 'raw',
                            maxFileSize: 10000000,
                            clientAllowedFormats: ['pdf'],
                            multiple: false,
                        }, (error, result) => {
                            if (!error && result && result.event === 'success') {
                                this.documentUrl = result.info.secure_url;
                                this.documentPublicId = result.info.public_id;
                                this.fileName = result.info.original_filename + '.' + result.info.format;
                                this.uploading = false;
                            }
                            if (result && result.event === 'upload-added') {
                                this.uploading = true;
                            }
                        });
                        widget.open();
                    } else {
                        this.validationError = '{{ __("Document upload service is not available. Please check Cloudinary configuration.") }}';
                    }
                },
                removeDocument() {
                    this.documentUrl = '';
                    this.documentPublicId = '';
                    this.fileName = '';
                },
                onSubmit(event) {
                    this.validationError = '';
                    if (!this.documentUrl) {
                        this.validationError = '{{ __("Please upload a document first.") }}';
                        event.preventDefault();
                        return false;
                    }
                    this.submitting = true;
                    return true;
                },
            };
        }
    </script>
    @endpush
</x-layouts::app>
