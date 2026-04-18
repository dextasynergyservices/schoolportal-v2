<x-layouts::app :title="__('My Games')">
    <div class="space-y-6">
        <div>
            <h1 class="text-xl font-bold text-zinc-900 dark:text-white">{{ __('My Games') }}</h1>
        </div>

        @if ($games->isNotEmpty())
            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ($games as $game)
                    @php
                        $bestPlay = $game->bestPlayForStudent(auth()->id());
                        $playCount = $game->playCountForStudent(auth()->id());
                        $typeIcons = [
                            'memory_match' => 'squares-2x2',
                            'word_scramble' => 'language',
                            'quiz_race' => 'bolt',
                            'flashcard' => 'rectangle-stack',
                        ];
                    @endphp
                    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 sm:p-5">
                        <div class="flex items-start gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-indigo-100 dark:bg-indigo-900/30">
                                <flux:icon :name="$typeIcons[$game->game_type] ?? 'puzzle-piece'" class="size-5 text-indigo-600 dark:text-indigo-400" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <h3 class="text-base font-semibold text-zinc-900 dark:text-white truncate">{{ $game->title }}</h3>
                                <p class="text-sm text-zinc-500 mt-0.5">
                                    {{ $game->gameTypeLabel() }} &middot; {{ ucfirst($game->difficulty) }}
                                </p>
                                <div class="flex items-center gap-3 mt-2 text-xs text-zinc-400">
                                    <span>{{ __('Plays:') }} {{ $playCount }}</span>
                                    @if ($bestPlay)
                                        <span>{{ __('Best:') }} {{ number_format($bestPlay->percentage, 0) }}%</span>
                                    @endif
                                    @if ($game->time_limit_minutes)
                                        <span>{{ $game->time_limit_minutes }} {{ __('min') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <flux:button variant="primary" size="sm" href="{{ route('student.games.play', $game) }}" wire:navigate>
                                {{ $playCount > 0 ? __('Play Again') : __('Play Now') }}
                            </flux:button>
                            @if ($game->game_type === 'quiz_race')
                                <flux:button variant="subtle" size="sm" href="{{ route('student.games.leaderboard', $game) }}" wire:navigate>
                                    {{ __('Leaderboard') }}
                                </flux:button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-8 text-center">
                <flux:icon name="puzzle-piece" class="mx-auto h-12 w-12 text-zinc-400" />
                <h3 class="mt-2 text-sm font-semibold text-zinc-900 dark:text-white">{{ __('No games available') }}</h3>
                <p class="mt-1 text-sm text-zinc-500">{{ __('Your teacher hasn\'t published any games for your class yet.') }}</p>
            </div>
        @endif
    </div>
</x-layouts::app>
