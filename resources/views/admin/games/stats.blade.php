<x-layouts::app :title="__('Game Stats')">
    <div class="space-y-6">
        <div>
            <flux:button variant="subtle" size="sm" href="{{ route('admin.games.index') }}" wire:navigate class="mb-2">
                <flux:icon name="arrow-left" class="size-4 mr-1" /> {{ __('Back to Games') }}
            </flux:button>
            <h1 class="text-xl font-bold text-zinc-900 dark:text-white">{{ $game->title }} &mdash; {{ __('Stats') }}</h1>
            <p class="text-sm text-zinc-500 mt-1">{{ $game->class?->name }} &middot; {{ $game->gameTypeLabel() }}</p>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 text-center">
                <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $stats['total_plays'] }}</p>
                <p class="text-xs text-zinc-500">{{ __('Total Plays') }}</p>
            </div>
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 text-center">
                <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $stats['unique_players'] }}</p>
                <p class="text-xs text-zinc-500">{{ __('Unique Players') }}</p>
            </div>
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 text-center">
                <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $stats['average_score'] }}%</p>
                <p class="text-xs text-zinc-500">{{ __('Average Score') }}</p>
            </div>
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 text-center">
                <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $stats['highest_score'] }}%</p>
                <p class="text-xs text-zinc-500">{{ __('Highest Score') }}</p>
            </div>
        </div>

        @if ($plays->isNotEmpty())
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 overflow-hidden">
                <div class="overflow-x-auto">
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>{{ __('Student') }}</flux:table.column>
                            <flux:table.column>{{ __('Score') }}</flux:table.column>
                            <flux:table.column class="hidden sm:table-cell">{{ __('Percentage') }}</flux:table.column>
                            <flux:table.column class="hidden sm:table-cell">{{ __('Time') }}</flux:table.column>
                            <flux:table.column class="hidden sm:table-cell">{{ __('Played') }}</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach ($plays as $play)
                                <flux:table.row>
                                    <flux:table.cell>
                                        <span class="font-medium text-zinc-900 dark:text-white">{{ $play->student?->name }}</span>
                                    </flux:table.cell>
                                    <flux:table.cell>{{ $play->score }} / {{ $play->max_score }}</flux:table.cell>
                                    <flux:table.cell class="hidden sm:table-cell">{{ number_format($play->percentage, 1) }}%</flux:table.cell>
                                    <flux:table.cell class="hidden sm:table-cell">
                                        @if ($play->time_spent_seconds)
                                            {{ floor($play->time_spent_seconds / 60) }}m {{ $play->time_spent_seconds % 60 }}s
                                        @else
                                            -
                                        @endif
                                    </flux:table.cell>
                                    <flux:table.cell class="hidden sm:table-cell">{{ $play->completed_at?->format('M j, Y') }}</flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>
            </div>

            @if ($plays->hasPages())
                <div class="mt-4">{{ $plays->links() }}</div>
            @endif
        @else
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-8 text-center">
                <flux:icon name="chart-bar" class="mx-auto h-12 w-12 text-zinc-400" />
                <h3 class="mt-2 text-sm font-semibold text-zinc-900 dark:text-white">{{ __('No plays yet') }}</h3>
            </div>
        @endif
    </div>
</x-layouts::app>
