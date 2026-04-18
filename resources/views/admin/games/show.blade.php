<x-layouts::app :title="$game->title">
    <div class="space-y-6">
        <div>
            <flux:button variant="subtle" size="sm" href="{{ route('admin.games.index') }}" wire:navigate class="mb-2">
                <flux:icon name="arrow-left" class="size-4 mr-1" /> {{ __('Back to Games') }}
            </flux:button>
            <div class="flex items-center gap-3">
                <h1 class="text-xl font-bold text-zinc-900 dark:text-white">{{ $game->title }}</h1>
                @if ($game->status === 'approved' && $game->is_published)
                    <flux:badge color="green" size="sm">{{ __('Published') }}</flux:badge>
                @elseif ($game->status === 'approved')
                    <flux:badge color="blue" size="sm">{{ __('Approved') }}</flux:badge>
                @elseif ($game->status === 'pending')
                    <flux:badge color="yellow" size="sm">{{ __('Pending') }}</flux:badge>
                @elseif ($game->status === 'rejected')
                    <flux:badge color="red" size="sm">{{ __('Rejected') }}</flux:badge>
                @endif
            </div>
            <p class="text-sm text-zinc-500 mt-1">
                {{ $game->class?->name }} &middot; {{ $game->gameTypeLabel() }} &middot; {{ ucfirst($game->difficulty) }}
                &middot; {{ __('By') }} {{ $game->creator?->name }}
            </p>
        </div>

        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 sm:p-5">
            <h3 class="text-base font-semibold text-zinc-900 dark:text-white mb-3">{{ __('Game Content') }}</h3>

            @php $data = $game->game_data; @endphp

            @if ($game->game_type === 'memory_match' && isset($data['pairs']))
                <div class="grid gap-2">
                    @foreach ($data['pairs'] as $i => $pair)
                        <div class="flex gap-2 text-sm">
                            <span class="text-zinc-400 w-6">{{ $i + 1 }}.</span>
                            <span class="font-medium text-zinc-900 dark:text-white">{{ $pair['term'] }}</span>
                            <span class="text-zinc-400">&rarr;</span>
                            <span class="text-zinc-600 dark:text-zinc-300">{{ $pair['definition'] }}</span>
                        </div>
                    @endforeach
                </div>
            @elseif ($game->game_type === 'word_scramble' && isset($data['words']))
                <div class="grid gap-2">
                    @foreach ($data['words'] as $i => $word)
                        <div class="text-sm">
                            <span class="text-zinc-400">{{ $i + 1 }}.</span>
                            <span class="font-medium text-zinc-900 dark:text-white">{{ $word['word'] }}</span>
                            <span class="text-zinc-500 ml-2">({{ __('Hint:') }} {{ $word['hint'] }})</span>
                        </div>
                    @endforeach
                </div>
            @elseif ($game->game_type === 'quiz_race' && isset($data['questions']))
                <div class="space-y-3">
                    @foreach ($data['questions'] as $i => $q)
                        <div class="text-sm">
                            <p class="font-medium text-zinc-900 dark:text-white">{{ $i + 1 }}. {{ $q['question'] }}</p>
                            <p class="text-green-600 text-xs mt-1">{{ __('Answer:') }} {{ $q['answer'] }}</p>
                        </div>
                    @endforeach
                </div>
            @elseif ($game->game_type === 'flashcard' && isset($data['cards']))
                <div class="grid gap-2">
                    @foreach ($data['cards'] as $i => $card)
                        <div class="text-sm">
                            <span class="text-zinc-400">{{ $i + 1 }}.</span>
                            <span class="font-medium text-zinc-900 dark:text-white">{{ $card['front'] }}</span>
                            <span class="text-zinc-400">&rarr;</span>
                            <span class="text-zinc-600 dark:text-zinc-300">{{ $card['back'] }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="flex items-center gap-3">
            @if ($game->status === 'approved' && ! $game->is_published)
                <form method="POST" action="{{ route('admin.games.publish', $game) }}">
                    @csrf
                    <flux:button type="submit" variant="primary">{{ __('Publish Game') }}</flux:button>
                </form>
            @endif
            @if ($game->is_published)
                <form method="POST" action="{{ route('admin.games.unpublish', $game) }}">
                    @csrf
                    <flux:button type="submit" variant="subtle">{{ __('Unpublish') }}</flux:button>
                </form>
                <flux:button variant="subtle" href="{{ route('admin.games.stats', $game) }}" wire:navigate>{{ __('View Stats') }}</flux:button>
            @endif
        </div>
    </div>
</x-layouts::app>
