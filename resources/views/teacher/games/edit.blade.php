<x-layouts::app :title="__('Edit Game')">
    <div class="space-y-6">
        <div>
            <flux:button variant="subtle" size="sm" href="{{ route('teacher.games.index') }}" wire:navigate class="mb-2">
                <flux:icon name="arrow-left" class="size-4 mr-1" /> {{ __('Back to Games') }}
            </flux:button>
            <h1 class="text-xl font-bold text-zinc-900 dark:text-white">{{ __('Edit Game') }}</h1>
        </div>

        <form method="POST" action="{{ route('teacher.games.update', $game) }}" x-data="{ ...gameEditor(), submitting: false }" @submit="submitting = true">
            @csrf
            @method('PUT')

            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 sm:p-5 mb-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <flux:input name="title" label="{{ __('Title') }}" required :value="old('title', $game->title)" />
                    </div>
                    <div>
                        <flux:select name="class_id" label="{{ __('Class') }}" required>
                            @foreach ($classes as $class)
                                <option value="{{ $class->id }}" @selected(old('class_id', $game->class_id) == $class->id)>{{ $class->name }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                    <div>
                        <flux:select name="difficulty" label="{{ __('Difficulty') }}" required>
                            <option value="easy" @selected($game->difficulty === 'easy')>{{ __('Easy') }}</option>
                            <option value="medium" @selected($game->difficulty === 'medium')>{{ __('Medium') }}</option>
                            <option value="hard" @selected($game->difficulty === 'hard')>{{ __('Hard') }}</option>
                        </flux:select>
                    </div>
                    <div>
                        <flux:input type="number" name="time_limit_minutes" label="{{ __('Time Limit (min)') }}"
                            :value="old('time_limit_minutes', $game->time_limit_minutes)" min="1" max="60" />
                    </div>
                </div>
                <div class="mt-4">
                    <flux:textarea name="description" label="{{ __('Description') }}" rows="2">{{ old('description', $game->description) }}</flux:textarea>
                </div>
            </div>

            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 sm:p-5 mb-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-base font-semibold text-zinc-900 dark:text-white">{{ __('Content') }} (<span x-text="items.length"></span>)</h3>
                    <flux:button type="button" variant="subtle" size="sm" @click="addItem()">
                        <flux:icon name="plus" class="size-4 mr-1" /> {{ __('Add') }}
                    </flux:button>
                </div>

                <div class="space-y-3">
                    <template x-for="(item, i) in items" :key="i">
                        <div class="flex items-start gap-2 rounded-lg border border-zinc-200 dark:border-zinc-700 p-3">
                            <span class="text-xs text-zinc-400 mt-2" x-text="i + 1"></span>
                            <div class="flex-1 space-y-2">
                                <template x-if="gameType === 'memory_match'">
                                    <div class="grid grid-cols-2 gap-2">
                                        <input type="text" x-model="item.term" class="rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 text-sm" placeholder="{{ __('Term') }}" required>
                                        <input type="text" x-model="item.definition" class="rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 text-sm" placeholder="{{ __('Definition') }}" required>
                                    </div>
                                </template>
                                <template x-if="gameType === 'word_scramble'">
                                    <div class="grid grid-cols-2 gap-2">
                                        <input type="text" x-model="item.word" class="rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 text-sm" placeholder="{{ __('Word') }}" required>
                                        <input type="text" x-model="item.hint" class="rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 text-sm" placeholder="{{ __('Hint') }}" required>
                                    </div>
                                </template>
                                <template x-if="gameType === 'quiz_race'">
                                    <div>
                                        <input type="text" x-model="item.question" class="w-full rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 text-sm mb-2" placeholder="{{ __('Question') }}" required>
                                        <div class="grid grid-cols-2 gap-2">
                                            <template x-for="(opt, oi) in item.options" :key="oi">
                                                <input type="text" x-model="item.options[oi]" class="rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 text-sm"
                                                    :placeholder="oi === 0 ? '{{ __("Correct") }}' : '{{ __("Option") }} ' + (oi + 1)" required>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="gameType === 'flashcard'">
                                    <div class="grid grid-cols-2 gap-2">
                                        <input type="text" x-model="item.front" class="rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 text-sm" placeholder="{{ __('Front') }}" required>
                                        <input type="text" x-model="item.back" class="rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 text-sm" placeholder="{{ __('Back') }}" required>
                                    </div>
                                </template>
                            </div>
                            <button type="button" @click="items.splice(i, 1)" class="text-red-500 mt-2" x-show="items.length > 1">
                                <flux:icon name="x-mark" class="size-4" />
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            <input type="hidden" name="game_data" :value="JSON.stringify(buildGameData())">

            <div class="flex items-center gap-3">
                <flux:button type="submit" variant="primary" x-bind:disabled="submitting">
                    <span x-show="!submitting">{{ __('Update & Resubmit') }}</span>
                    <span x-show="submitting" x-cloak class="inline-flex items-center gap-2">
                        <flux:icon name="arrow-path" class="size-4 animate-spin" />
                        {{ __('Saving...') }}
                    </span>
                </flux:button>
                <flux:button variant="subtle" href="{{ route('teacher.games.index') }}" wire:navigate x-show="!submitting">{{ __('Cancel') }}</flux:button>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        function gameEditor() {
            const gameType = '{{ $game->game_type }}';
            const data = @json($game->game_data);
            let items = [];

            if (gameType === 'memory_match' && data.pairs) items = data.pairs;
            else if (gameType === 'word_scramble' && data.words) items = data.words;
            else if (gameType === 'quiz_race' && data.questions) items = data.questions;
            else if (gameType === 'flashcard' && data.cards) items = data.cards;

            return {
                gameType,
                items: items.length > 0 ? items : [{}],
                addItem() {
                    if (gameType === 'memory_match') this.items.push({ term: '', definition: '' });
                    else if (gameType === 'word_scramble') this.items.push({ word: '', hint: '' });
                    else if (gameType === 'quiz_race') this.items.push({ question: '', answer: '', options: ['', '', '', ''] });
                    else if (gameType === 'flashcard') this.items.push({ front: '', back: '' });
                },
                buildGameData() {
                    if (gameType === 'memory_match') return { pairs: this.items };
                    if (gameType === 'word_scramble') return { words: this.items };
                    if (gameType === 'quiz_race') return { questions: this.items.map(i => ({ ...i, answer: i.options?.[0] || i.answer })) };
                    if (gameType === 'flashcard') return { cards: this.items };
                    return {};
                },
            };
        }
    </script>
    @endpush
</x-layouts::app>
