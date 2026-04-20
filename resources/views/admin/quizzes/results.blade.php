<x-layouts::app :title="__('Quiz Results')">
    <div class="space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <flux:button variant="subtle" size="sm" href="{{ route('admin.quizzes.index') }}" wire:navigate class="mb-2">
                    <flux:icon name="arrow-left" class="size-4 mr-1" /> {{ __('Back to Quizzes') }}
                </flux:button>
                <h1 class="text-xl font-bold text-zinc-900 dark:text-white">{{ $quiz->title }} &mdash; {{ __('Results') }}</h1>
                <p class="text-sm text-zinc-500 mt-1">{{ $quiz->class?->name }}</p>
            </div>
            @if ($attempts->isNotEmpty())
                <flux:button variant="primary" size="sm" icon="arrow-down-tray" href="{{ route('admin.quizzes.results.export', $quiz) }}">
                    {{ __('Export CSV') }}
                </flux:button>
            @endif
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-4">
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-3 text-center">
                <p class="text-lg font-bold text-zinc-900 dark:text-white">{{ $stats['unique_students'] }}</p>
                <p class="text-xs text-zinc-500">{{ __('Students') }}</p>
            </div>
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-3 text-center">
                <p class="text-lg font-bold text-zinc-900 dark:text-white">{{ $stats['total_attempts'] }}</p>
                <p class="text-xs text-zinc-500">{{ __('Attempts') }}</p>
            </div>
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-3 text-center">
                <p class="text-lg font-bold text-zinc-900 dark:text-white">{{ $stats['average'] }}%</p>
                <p class="text-xs text-zinc-500">{{ __('Average') }}</p>
            </div>
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-3 text-center">
                <p class="text-lg font-bold text-zinc-900 dark:text-white">{{ $stats['highest'] }}%</p>
                <p class="text-xs text-zinc-500">{{ __('Highest') }}</p>
            </div>
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-3 text-center">
                <p class="text-lg font-bold text-zinc-900 dark:text-white">{{ $stats['lowest'] }}%</p>
                <p class="text-xs text-zinc-500">{{ __('Lowest') }}</p>
            </div>
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-3 text-center">
                <p class="text-lg font-bold text-green-600">{{ $stats['passed'] }}</p>
                <p class="text-xs text-zinc-500">{{ __('Passed') }}</p>
            </div>
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-3 text-center">
                <p class="text-lg font-bold text-red-600">{{ $stats['failed'] }}</p>
                <p class="text-xs text-zinc-500">{{ __('Failed') }}</p>
            </div>
        </div>

        @if ($attempts->isNotEmpty())
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 overflow-hidden">
                <div class="overflow-x-auto">
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>{{ __('Student') }}</flux:table.column>
                            <flux:table.column>{{ __('Score') }}</flux:table.column>
                            <flux:table.column>{{ __('Percentage') }}</flux:table.column>
                            <flux:table.column>{{ __('Status') }}</flux:table.column>
                            <flux:table.column class="hidden sm:table-cell">{{ __('Time') }}</flux:table.column>
                            <flux:table.column class="hidden sm:table-cell">{{ __('Submitted') }}</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach ($attempts as $attempt)
                                <flux:table.row>
                                    <flux:table.cell>
                                        <span class="font-medium text-zinc-900 dark:text-white">{{ $attempt->student?->name }}</span>
                                    </flux:table.cell>
                                    <flux:table.cell>{{ $attempt->score }} / {{ $attempt->total_points }}</flux:table.cell>
                                    <flux:table.cell>{{ number_format($attempt->percentage, 1) }}%</flux:table.cell>
                                    <flux:table.cell>
                                        @if ($attempt->passed)
                                            <flux:badge color="green" size="sm">{{ __('Passed') }}</flux:badge>
                                        @elseif ($attempt->status === 'timed_out')
                                            <flux:badge color="yellow" size="sm">{{ __('Timed Out') }}</flux:badge>
                                        @else
                                            <flux:badge color="red" size="sm">{{ __('Failed') }}</flux:badge>
                                        @endif
                                    </flux:table.cell>
                                    <flux:table.cell class="hidden sm:table-cell">
                                        @if ($attempt->time_spent_seconds)
                                            {{ floor($attempt->time_spent_seconds / 60) }}m {{ $attempt->time_spent_seconds % 60 }}s
                                        @else
                                            -
                                        @endif
                                    </flux:table.cell>
                                    <flux:table.cell class="hidden sm:table-cell">{{ $attempt->submitted_at?->format('M j, Y H:i') }}</flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>
            </div>

            @if ($attempts->hasPages())
                <div class="mt-4">{{ $attempts->links() }}</div>
            @endif
        @else
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-8 text-center">
                <flux:icon name="chart-bar" class="mx-auto h-12 w-12 text-zinc-400" />
                <h3 class="mt-2 text-sm font-semibold text-zinc-900 dark:text-white">{{ __('No attempts yet') }}</h3>
            </div>
        @endif
    </div>
</x-layouts::app>
