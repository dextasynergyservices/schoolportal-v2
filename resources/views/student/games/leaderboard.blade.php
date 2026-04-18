<x-layouts::app :title="__('Leaderboard')">
    <div class="space-y-6">
        <div>
            <flux:button variant="subtle" size="sm" href="{{ route('student.games.play', $game) }}" wire:navigate class="mb-2">
                <flux:icon name="arrow-left" class="size-4 mr-1" /> {{ __('Back to Game') }}
            </flux:button>
            <h1 class="text-xl font-bold text-zinc-900 dark:text-white">{{ $game->title }} &mdash; {{ __('Leaderboard') }}</h1>
            <p class="text-sm text-zinc-500 mt-1">{{ $game->gameTypeLabel() }} &middot; {{ $game->class?->name }}</p>
        </div>

        @if ($leaderboard->isNotEmpty())
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 overflow-hidden">
                <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach ($leaderboard as $index => $play)
                        @php
                            $isMe = $play->student_id === auth()->id();
                            $rank = $index + 1;
                            $medal = match($rank) { 1 => 'text-amber-500', 2 => 'text-zinc-400', 3 => 'text-amber-700', default => 'text-zinc-500' };
                        @endphp
                        <div class="flex items-center gap-4 px-4 py-3 {{ $isMe ? 'bg-indigo-50 dark:bg-indigo-900/20' : '' }}">
                            <span class="w-8 text-center text-lg font-bold {{ $medal }}">
                                @if ($rank <= 3)
                                    {{ match($rank) { 1 => '#1', 2 => '#2', 3 => '#3' } }}
                                @else
                                    {{ $rank }}
                                @endif
                            </span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-zinc-900 dark:text-white truncate">
                                    {{ $play->student?->name }}
                                    @if ($isMe)
                                        <span class="text-xs text-indigo-600 dark:text-indigo-400 font-normal">({{ __('You') }})</span>
                                    @endif
                                </p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-sm font-bold text-zinc-900 dark:text-white">{{ $play->score }} {{ __('pts') }}</p>
                                <p class="text-xs text-zinc-500">{{ number_format($play->percentage, 0) }}%</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="text-center">
                <flux:button variant="primary" href="{{ route('student.games.play', $game) }}" wire:navigate>
                    {{ __('Play Again to Improve Your Score') }}
                </flux:button>
            </div>
        @else
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-8 text-center">
                <flux:icon name="trophy" class="mx-auto h-12 w-12 text-zinc-400" />
                <h3 class="mt-2 text-sm font-semibold text-zinc-900 dark:text-white">{{ __('No scores yet') }}</h3>
                <p class="mt-1 text-sm text-zinc-500">{{ __('Be the first to play and top the leaderboard!') }}</p>
                <div class="mt-4">
                    <flux:button variant="primary" size="sm" href="{{ route('student.games.play', $game) }}" wire:navigate>{{ __('Play Now') }}</flux:button>
                </div>
            </div>
        @endif
    </div>
</x-layouts::app>
