<x-layouts::app :title="__('Create Game')">
    <div class="space-y-6">
        <x-admin-header :title="__('Create Game')" />

        @if (session('error'))
            <flux:callout variant="danger" icon="x-circle">{{ session('error') }}</flux:callout>
        @endif

        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 sm:p-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-6">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Choose how to create your game') }}</h2>
                <div class="text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('AI credits remaining:') }}
                    <span class="font-semibold {{ $availableCredits > 0 ? 'text-green-600' : 'text-red-600' }}">{{ $availableCredits }}</span>
                </div>
            </div>

            <div x-data="{ tab: 'prompt' }" class="space-y-6">
                {{-- Tab buttons --}}
                <div class="flex flex-wrap gap-2 border-b border-zinc-200 dark:border-zinc-700 pb-3">
                    <button type="button" @click="tab = 'prompt'"
                        :class="tab === 'prompt' ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' : 'bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-600'"
                        class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition">
                        <flux:icon name="chat-bubble-left-right" class="size-4" />
                        {{ __('From Prompt') }}
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
                        <form method="POST" action="{{ route('teacher.games.generate') }}" x-data="{ submitting: false }" @submit="submitting = true">
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
                                        <flux:select name="game_type" label="{{ __('Game Type') }}" required>
                                            <option value="memory_match">{{ __('Memory Match') }}</option>
                                            <option value="word_scramble">{{ __('Word Scramble') }}</option>
                                            <option value="quiz_race">{{ __('Quiz Race') }}</option>
                                            <option value="flashcard">{{ __('Flashcard Study') }}</option>
                                        </flux:select>
                                    </div>
                                </div>
                                <div>
                                    <flux:select name="difficulty" label="{{ __('Difficulty') }}" required>
                                        <option value="easy">{{ __('Easy') }}</option>
                                        <option value="medium" selected>{{ __('Medium') }}</option>
                                        <option value="hard">{{ __('Hard') }}</option>
                                    </flux:select>
                                </div>
                                <div>
                                    <flux:textarea name="prompt" label="{{ __('Prompt') }}" rows="3" required
                                        placeholder="{{ __('e.g., Create a memory match game about Nigerian states and their capitals for Primary 4') }}"
                                    >{{ old('prompt') }}</flux:textarea>
                                    @error('prompt') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 pt-2">
                                    <flux:button type="submit" variant="primary" x-bind:disabled="submitting">
                                        <span x-show="!submitting">{{ __('Generate Game (1 credit)') }}</span>
                                        <span x-show="submitting" x-cloak class="inline-flex items-center gap-2">
                                            <flux:icon name="arrow-path" class="size-4 animate-spin" />
                                            {{ __('Generating...') }}
                                        </span>
                                    </flux:button>
                                    <p class="text-xs text-zinc-500" x-show="!submitting">{{ __('You can review and edit content before saving.') }}</p>
                                    <p class="text-xs text-amber-600" x-show="submitting" x-cloak>{{ __('AI is generating your game content. This may take 15-30 seconds...') }}</p>
                                </div>
                            </div>
                        </form>
                    @else
                        @include('teacher.quizzes._no-credits')
                    @endif
                </div>

                {{-- Manual Creation --}}
                <div x-show="tab === 'manual'" x-cloak>
                    <form method="POST" action="{{ route('teacher.games.store') }}" x-data="manualGameCreator()" @submit="return onSubmit($event)">
                        @csrf
                        <input type="hidden" name="source_type" value="manual">
                        <div class="space-y-5">
                            {{-- Game settings --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <flux:input name="title" label="{{ __('Game Title') }}" required placeholder="{{ __('e.g., Nigerian States Memory Match') }}" />
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

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <flux:select name="game_type" label="{{ __('Game Type') }}" required x-model="gameType" @change="resetItems()">
                                        <option value="memory_match">{{ __('Memory Match') }}</option>
                                        <option value="word_scramble">{{ __('Word Scramble') }}</option>
                                        <option value="quiz_race">{{ __('Quiz Race') }}</option>
                                        <option value="flashcard">{{ __('Flashcard Study') }}</option>
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
                                <flux:textarea name="description" label="{{ __('Description (optional)') }}" rows="2"
                                    placeholder="{{ __('Brief description of the game') }}" />
                            </div>

                            <div>
                                <flux:input type="number" name="time_limit_minutes" label="{{ __('Time Limit (minutes, optional)') }}" placeholder="{{ __('No limit') }}" min="1" max="60" />
                            </div>

                            {{-- Game content section --}}
                            <div class="border-t border-zinc-200 dark:border-zinc-700 pt-5">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-base font-semibold text-zinc-900 dark:text-white">
                                        {{ __('Game Content') }}
                                        <span class="text-sm font-normal text-zinc-500" x-text="'(' + items.length + ' ' + itemLabel() + ')'"></span>
                                    </h3>
                                    <flux:button type="button" variant="primary" size="sm" @click="addItem()">
                                        <flux:icon name="plus" class="size-4 mr-1" /> {{ __('Add Item') }}
                                    </flux:button>
                                </div>

                                {{-- Game type description --}}
                                <div class="mb-4 text-sm text-zinc-500 dark:text-zinc-400 rounded-lg bg-zinc-50 dark:bg-zinc-900/50 p-3">
                                    <template x-if="gameType === 'memory_match'">
                                        <p>{{ __('Add term-definition pairs. Students flip cards to match terms with their definitions.') }}</p>
                                    </template>
                                    <template x-if="gameType === 'word_scramble'">
                                        <p>{{ __('Add words with hints. Students unscramble jumbled letters to form the correct word.') }}</p>
                                    </template>
                                    <template x-if="gameType === 'quiz_race'">
                                        <p>{{ __('Add rapid-fire questions with 4 options each. First option is the correct answer. Students race against a 10-second timer.') }}</p>
                                    </template>
                                    <template x-if="gameType === 'flashcard'">
                                        <p>{{ __('Add question-answer pairs. Students flip cards and self-assess: "Got it" or "Review again".') }}</p>
                                    </template>
                                </div>

                                <div class="space-y-4">
                                    <template x-for="(item, i) in items" :key="i">
                                        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
                                            <div class="flex items-center justify-between mb-3">
                                                <span class="flex items-center justify-center size-7 rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 text-xs font-bold" x-text="i + 1"></span>
                                                <button type="button" @click="removeItem(i)"
                                                    class="text-red-500 hover:text-red-700 text-xs font-medium" x-show="items.length > 1">
                                                    <flux:icon name="trash" class="size-4" />
                                                </button>
                                            </div>

                                            {{-- Memory Match / Flashcard --}}
                                            <template x-if="gameType === 'memory_match' || gameType === 'flashcard'">
                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                    <div>
                                                        <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1" x-text="gameType === 'memory_match' ? '{{ __("Term") }}' : '{{ __("Front (question)") }}'"></label>
                                                        <input type="text" x-model="item.a"
                                                            class="w-full rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 text-sm"
                                                            :placeholder="gameType === 'memory_match' ? '{{ __("e.g., Photosynthesis") }}' : '{{ __("e.g., What is the capital of Nigeria?") }}'" required>
                                                    </div>
                                                    <div>
                                                        <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1" x-text="gameType === 'memory_match' ? '{{ __("Definition") }}' : '{{ __("Back (answer)") }}'"></label>
                                                        <input type="text" x-model="item.b"
                                                            class="w-full rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 text-sm"
                                                            :placeholder="gameType === 'memory_match' ? '{{ __("e.g., Process plants use to make food") }}' : '{{ __("e.g., Abuja") }}'" required>
                                                    </div>
                                                </div>
                                            </template>

                                            {{-- Word Scramble --}}
                                            <template x-if="gameType === 'word_scramble'">
                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                    <div>
                                                        <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">{{ __('Word') }}</label>
                                                        <input type="text" x-model="item.a"
                                                            class="w-full rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 text-sm"
                                                            placeholder="{{ __('e.g., PHOTOSYNTHESIS') }}" required>
                                                    </div>
                                                    <div>
                                                        <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">{{ __('Hint') }}</label>
                                                        <input type="text" x-model="item.b"
                                                            class="w-full rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 text-sm"
                                                            placeholder="{{ __('e.g., Process plants use to make food') }}" required>
                                                    </div>
                                                </div>
                                            </template>

                                            {{-- Quiz Race --}}
                                            <template x-if="gameType === 'quiz_race'">
                                                <div class="space-y-3">
                                                    <div>
                                                        <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">{{ __('Question') }}</label>
                                                        <input type="text" x-model="item.a"
                                                            class="w-full rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 text-sm"
                                                            placeholder="{{ __('e.g., What is the chemical symbol for water?') }}" required>
                                                    </div>
                                                    <div>
                                                        <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-2">{{ __('Options (first option is the correct answer)') }}</label>
                                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                            <div class="flex items-center gap-2">
                                                                <span class="text-xs font-medium text-green-600 w-5">A.</span>
                                                                <input type="text" x-model="item.options[0]"
                                                                    class="flex-1 rounded-lg border-green-300 dark:border-green-700 dark:bg-zinc-700 text-sm ring-1 ring-green-200 dark:ring-green-800"
                                                                    placeholder="{{ __('Correct answer') }}" required>
                                                            </div>
                                                            <div class="flex items-center gap-2">
                                                                <span class="text-xs font-medium text-zinc-500 w-5">B.</span>
                                                                <input type="text" x-model="item.options[1]"
                                                                    class="flex-1 rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 text-sm"
                                                                    placeholder="{{ __('Option B') }}" required>
                                                            </div>
                                                            <div class="flex items-center gap-2">
                                                                <span class="text-xs font-medium text-zinc-500 w-5">C.</span>
                                                                <input type="text" x-model="item.options[2]"
                                                                    class="flex-1 rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 text-sm"
                                                                    placeholder="{{ __('Option C') }}" required>
                                                            </div>
                                                            <div class="flex items-center gap-2">
                                                                <span class="text-xs font-medium text-zinc-500 w-5">D.</span>
                                                                <input type="text" x-model="item.options[3]"
                                                                    class="flex-1 rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 text-sm"
                                                                    placeholder="{{ __('Option D') }}" required>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>

                                {{-- Add more button at bottom --}}
                                <div class="mt-4 text-center">
                                    <button type="button" @click="addItem()"
                                        class="inline-flex items-center gap-1 text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 font-medium">
                                        <flux:icon name="plus-circle" class="size-5" />
                                        {{ __('Add Another Item') }}
                                    </button>
                                </div>
                            </div>

                            <input type="hidden" name="game_data" :value="JSON.stringify(buildGameData())">

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
    <script>
        function manualGameCreator() {
            return {
                submitting: false,
                gameType: 'memory_match',
                items: [{ a: '', b: '', options: ['', '', '', ''] }],

                itemLabel() {
                    const labels = {
                        memory_match: '{{ __("pairs") }}',
                        word_scramble: '{{ __("words") }}',
                        quiz_race: '{{ __("questions") }}',
                        flashcard: '{{ __("cards") }}',
                    };
                    return labels[this.gameType] || '{{ __("items") }}';
                },

                addItem() {
                    this.items.push({ a: '', b: '', options: ['', '', '', ''] });
                },

                removeItem(index) {
                    if (this.items.length > 1) {
                        this.items.splice(index, 1);
                    }
                },

                resetItems() {
                    this.items = [{ a: '', b: '', options: ['', '', '', ''] }];
                },

                buildGameData() {
                    if (this.gameType === 'memory_match') {
                        return { pairs: this.items.map(i => ({ term: i.a, definition: i.b })) };
                    }
                    if (this.gameType === 'word_scramble') {
                        return { words: this.items.map(i => ({ word: i.a, hint: i.b, category: '' })) };
                    }
                    if (this.gameType === 'quiz_race') {
                        return { questions: this.items.map(i => ({ question: i.a, answer: i.options[0], options: i.options })) };
                    }
                    if (this.gameType === 'flashcard') {
                        return { cards: this.items.map(i => ({ front: i.a, back: i.b, category: '' })) };
                    }
                    return {};
                },

                onSubmit(event) {
                    const hasContent = this.items.some(item => item.a.trim() !== '' || item.b.trim() !== '');
                    if (!hasContent) {
                        alert('{{ __("Please add at least one item with content.") }}');
                        event.preventDefault();
                        return false;
                    }
                    // Validate quiz_race options
                    if (this.gameType === 'quiz_race') {
                        for (let i = 0; i < this.items.length; i++) {
                            if (this.items[i].a.trim() && !this.items[i].options[0].trim()) {
                                alert('{{ __("Please fill in the correct answer for question") }} ' + (i + 1));
                                event.preventDefault();
                                return false;
                            }
                        }
                    }
                    this.submitting = true;
                    return true;
                },
            };
        }
    </script>
    @endpush
</x-layouts::app>
