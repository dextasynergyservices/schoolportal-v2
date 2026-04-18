<x-layouts::app :title="__('Review Generated Game')">
    <div class="space-y-6">
        <x-admin-header :title="__('Review Generated Game Content')" />

        <flux:callout variant="info" icon="sparkles">
            {{ __('AI generated the content below. Review and edit before saving.') }}
        </flux:callout>

        <form method="POST" action="{{ route('teacher.games.store') }}" x-data="gameReviewer()" @submit="return onSubmit($event)">
            @csrf
            <input type="hidden" name="game_type" value="{{ $gameType }}">
            <input type="hidden" name="source_type" value="{{ $sourceType }}">
            <input type="hidden" name="difficulty" value="{{ $difficulty }}">
            @if ($sourcePrompt)
                <input type="hidden" name="source_prompt" value="{{ $sourcePrompt }}">
            @endif
            @if ($sourceDocumentUrl)
                <input type="hidden" name="source_document_url" value="{{ $sourceDocumentUrl }}">
                <input type="hidden" name="source_document_public_id" value="{{ $sourceDocumentPublicId }}">
            @endif

            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 sm:p-5 mb-4">
                <h3 class="text-base font-semibold text-zinc-900 dark:text-white mb-4">{{ __('Game Settings') }}</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <flux:input name="title" label="{{ __('Game Title') }}" required placeholder="{{ __('Enter a title for this game') }}" />
                        @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
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
                    <flux:textarea name="description" label="{{ __('Description (optional)') }}" rows="2" placeholder="{{ __('Brief description of the game') }}" />
                </div>
                <div class="mt-4">
                    <flux:input type="number" name="time_limit_minutes" label="{{ __('Time Limit (minutes, optional)') }}" placeholder="{{ __('No limit') }}" min="1" max="60" />
                </div>
                <p class="mt-2 text-sm text-zinc-500">
                    {{ __('Game Type:') }}
                    <span class="font-medium">
                        @switch($gameType)
                            @case('memory_match') {{ __('Memory Match') }} @break
                            @case('word_scramble') {{ __('Word Scramble') }} @break
                            @case('quiz_race') {{ __('Quiz Race') }} @break
                            @case('flashcard') {{ __('Flashcard Study') }} @break
                        @endswitch
                    </span>
                    &middot; {{ __('Difficulty:') }}
                    <span class="font-medium">{{ ucfirst($difficulty) }}</span>
                </p>
            </div>

            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 sm:p-5 mb-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-semibold text-zinc-900 dark:text-white">
                        {{ __('Content') }}
                        <span class="text-sm font-normal text-zinc-500" x-text="'(' + items.length + ' ' + itemLabel + ')'"></span>
                    </h3>
                    <flux:button type="button" variant="primary" size="sm" @click="addItem()">
                        <flux:icon name="plus" class="size-4 mr-1" /> {{ __('Add') }}
                    </flux:button>
                </div>

                <template x-if="items.length === 0">
                    <div class="rounded-lg bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 p-4 text-center">
                        <p class="text-sm text-amber-700 dark:text-amber-300">{{ __('No content was generated. Please add items manually or go back and try again.') }}</p>
                    </div>
                </template>

                <div class="space-y-3">
                    <template x-for="(item, i) in items" :key="i">
                        <div class="flex items-start gap-2 rounded-lg border border-zinc-200 dark:border-zinc-700 p-3">
                            <span class="flex items-center justify-center size-6 rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 text-xs font-bold shrink-0 mt-1" x-text="i + 1"></span>
                            <div class="flex-1 space-y-2">
                                <template x-if="gameType === 'memory_match'">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">{{ __('Term') }}</label>
                                            <input type="text" x-model="item.term" class="w-full rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 text-sm" placeholder="{{ __('Term') }}" required>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">{{ __('Definition') }}</label>
                                            <input type="text" x-model="item.definition" class="w-full rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 text-sm" placeholder="{{ __('Definition') }}" required>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="gameType === 'word_scramble'">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">{{ __('Word') }}</label>
                                            <input type="text" x-model="item.word" class="w-full rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 text-sm" placeholder="{{ __('Word') }}" required>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">{{ __('Hint') }}</label>
                                            <input type="text" x-model="item.hint" class="w-full rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 text-sm" placeholder="{{ __('Hint') }}" required>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="gameType === 'quiz_race'">
                                    <div>
                                        <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">{{ __('Question') }}</label>
                                        <input type="text" x-model="item.question" class="w-full rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 text-sm mb-2" placeholder="{{ __('Question') }}" required>
                                        <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">{{ __('Options (first is correct)') }}</label>
                                        <div class="grid grid-cols-2 gap-2">
                                            <template x-for="(opt, oi) in item.options" :key="oi">
                                                <div class="flex items-center gap-1">
                                                    <span class="text-xs font-medium w-4 shrink-0" :class="oi === 0 ? 'text-green-600' : 'text-zinc-400'" x-text="String.fromCharCode(65 + oi) + '.'"></span>
                                                    <input type="text" x-model="item.options[oi]"
                                                        class="flex-1 rounded-lg text-sm"
                                                        :class="oi === 0 ? 'border-green-300 dark:border-green-700 ring-1 ring-green-200 dark:ring-green-800 dark:bg-zinc-700' : 'border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700'"
                                                        :placeholder="oi === 0 ? '{{ __("Correct answer") }}' : '{{ __("Option") }} ' + String.fromCharCode(65 + oi)" required>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="gameType === 'flashcard'">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">{{ __('Front (question)') }}</label>
                                            <input type="text" x-model="item.front" class="w-full rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 text-sm" placeholder="{{ __('Front (question)') }}" required>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">{{ __('Back (answer)') }}</label>
                                            <input type="text" x-model="item.back" class="w-full rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 text-sm" placeholder="{{ __('Back (answer)') }}" required>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <button type="button" @click="items.splice(i, 1)" class="text-red-500 hover:text-red-700 mt-1 shrink-0" x-show="items.length > 1">
                                <flux:icon name="trash" class="size-4" />
                            </button>
                        </div>
                    </template>
                </div>

                {{-- Add more at bottom --}}
                <div class="mt-4 text-center" x-show="items.length > 0">
                    <button type="button" @click="addItem()"
                        class="inline-flex items-center gap-1 text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 font-medium">
                        <flux:icon name="plus-circle" class="size-5" />
                        {{ __('Add Another Item') }}
                    </button>
                </div>
            </div>

            <input type="hidden" name="game_data" :value="JSON.stringify(buildGameData())">

            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
                <flux:button type="submit" variant="primary" x-bind:disabled="submitting" class="w-full sm:w-auto">
                    <span x-show="!submitting">{{ __('Save & Submit for Approval') }}</span>
                    <span x-show="submitting" x-cloak class="inline-flex items-center gap-2">
                        <flux:icon name="arrow-path" class="size-4 animate-spin" />
                        {{ __('Saving...') }}
                    </span>
                </flux:button>
                <flux:button variant="subtle" href="{{ route('teacher.games.create') }}" wire:navigate>{{ __('Cancel') }}</flux:button>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        function gameReviewer() {
            const gameType = '{{ $gameType }}';
            const raw = @json($gameData ?? []);

            const itemLabels = {
                memory_match: '{{ __("pairs") }}',
                word_scramble: '{{ __("words") }}',
                quiz_race: '{{ __("questions") }}',
                flashcard: '{{ __("cards") }}',
            };

            let items = [];
            try {
                if (gameType === 'memory_match' && raw.pairs && Array.isArray(raw.pairs)) {
                    items = raw.pairs.map(p => ({ term: p.term || '', definition: p.definition || '' }));
                } else if (gameType === 'word_scramble' && raw.words && Array.isArray(raw.words)) {
                    items = raw.words.map(w => ({ word: w.word || '', hint: w.hint || '' }));
                } else if (gameType === 'quiz_race' && raw.questions && Array.isArray(raw.questions)) {
                    items = raw.questions.map(q => ({
                        question: q.question || '',
                        answer: q.answer || '',
                        options: Array.isArray(q.options) && q.options.length >= 4 ? q.options : [q.answer || '', '', '', '']
                    }));
                } else if (gameType === 'flashcard' && raw.cards && Array.isArray(raw.cards)) {
                    items = raw.cards.map(c => ({ front: c.front || '', back: c.back || '' }));
                }
            } catch (e) {
                console.error('Error parsing game data:', e);
            }

            // If AI returned empty data, start with one blank item
            if (items.length === 0) {
                if (gameType === 'memory_match') items = [{ term: '', definition: '' }];
                else if (gameType === 'word_scramble') items = [{ word: '', hint: '' }];
                else if (gameType === 'quiz_race') items = [{ question: '', answer: '', options: ['', '', '', ''] }];
                else if (gameType === 'flashcard') items = [{ front: '', back: '' }];
            }

            return {
                submitting: false,
                gameType,
                items,
                itemLabel: itemLabels[gameType] || '{{ __("items") }}',

                addItem() {
                    if (gameType === 'memory_match') this.items.push({ term: '', definition: '' });
                    else if (gameType === 'word_scramble') this.items.push({ word: '', hint: '' });
                    else if (gameType === 'quiz_race') this.items.push({ question: '', answer: '', options: ['', '', '', ''] });
                    else if (gameType === 'flashcard') this.items.push({ front: '', back: '' });
                },

                buildGameData() {
                    if (gameType === 'memory_match') return { pairs: this.items.map(i => ({ term: i.term, definition: i.definition })) };
                    if (gameType === 'word_scramble') return { words: this.items.map(i => ({ word: i.word, hint: i.hint, category: '' })) };
                    if (gameType === 'quiz_race') return { questions: this.items.map(i => ({ question: i.question, answer: i.options[0], options: i.options })) };
                    if (gameType === 'flashcard') return { cards: this.items.map(i => ({ front: i.front, back: i.back, category: '' })) };
                    return {};
                },

                onSubmit(event) {
                    if (this.items.length === 0) {
                        alert('{{ __("Please add at least one item.") }}');
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
