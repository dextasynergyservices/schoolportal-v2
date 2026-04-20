<x-layouts::app :title="__('Game Stats')">
    <div class="space-y-6">
        <div>
            <flux:button variant="subtle" size="sm" href="{{ route('parent.children.show', $child) }}" wire:navigate class="mb-2">
                <flux:icon name="arrow-left" class="size-4 mr-1" /> {{ __('Back to :name', ['name' => $child->name]) }}
            </flux:button>
            <h1 class="text-xl font-bold text-zinc-900 dark:text-white">{{ $child->name }} &mdash; {{ __('Game Stats') }}</h1>
        </div>

        @if ($plays->isNotEmpty())
            @php
                $totalPlays = $plays->total();
                $avgPercentage = $plays->getCollection()->avg('percentage') ?? 0;
                $bestPercentage = $plays->getCollection()->max('percentage') ?? 0;
                $uniqueGames = $plays->getCollection()->pluck('game_id')->unique()->count();
            @endphp
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Games Played') }}</p>
                    <p class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ $totalPlays }}</p>
                </div>
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Unique Games') }}</p>
                    <p class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ $uniqueGames }}</p>
                </div>
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Average Score') }}</p>
                    <p class="mt-1 text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ number_format($avgPercentage, 0) }}%</p>
                </div>
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Best Score') }}</p>
                    <p class="mt-1 text-2xl font-bold text-amber-600 dark:text-amber-400">{{ number_format($bestPercentage, 0) }}%</p>
                </div>
            </div>

            <div class="grid gap-4">
                @foreach ($plays as $play)
                    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $play->game?->title }}</h3>
                                <p class="text-xs text-zinc-500 mt-0.5">
                                    {{ $play->game?->gameTypeLabel() }}
                                    &middot; {{ $play->game?->class?->name }}
                                    &middot; {{ $play->completed_at?->format('M j, Y') }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-bold text-indigo-600 dark:text-indigo-400">
                                    {{ number_format($play->percentage, 0) }}%
                                </p>
                                <p class="text-xs text-zinc-500">{{ $play->score }} / {{ $play->max_score }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if ($plays->hasPages())
                <div class="mt-4">{{ $plays->links() }}</div>
            @endif
        @else
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-8 text-center">
                <flux:icon name="puzzle-piece" class="mx-auto h-12 w-12 text-zinc-400" />
                <h3 class="mt-2 text-sm font-semibold text-zinc-900 dark:text-white">{{ __('No game activity yet') }}</h3>
                <p class="mt-1 text-sm text-zinc-500">{{ __(':name hasn\'t played any games yet.', ['name' => $child->name]) }}</p>
            </div>
        @endif
    </div>
</x-layouts::app>
