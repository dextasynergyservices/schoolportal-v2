<x-layouts::app :title="__('Quiz Results')">
    <div class="space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <flux:button variant="subtle" size="sm" href="{{ route('teacher.quizzes.index') }}" wire:navigate class="mb-2">
                    <flux:icon name="arrow-left" class="size-4 mr-1" /> {{ __('Back to Quizzes') }}
                </flux:button>
                <h1 class="text-xl font-bold text-zinc-900 dark:text-white">{{ $quiz->title }} &mdash; {{ __('Results') }}</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $quiz->class?->name }} &middot; {{ $quiz->total_questions }} {{ __('questions') }}</p>
            </div>
            @if ($attempts->isNotEmpty())
                <flux:button variant="primary" size="sm" icon="arrow-down-tray" href="{{ route('teacher.quizzes.results.export', $quiz) }}">
                    {{ __('Export CSV') }}
                </flux:button>
            @endif
        </div>

        {{-- Summary Stats --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 text-center">
                <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $stats['total_students'] }}</p>
                <p class="text-xs text-zinc-500">{{ __('Students') }}</p>
            </div>
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 text-center">
                <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $stats['average_score'] }}%</p>
                <p class="text-xs text-zinc-500">{{ __('Average') }}</p>
            </div>
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 text-center">
                <p class="text-2xl font-bold text-green-600">{{ $stats['highest_score'] }}%</p>
                <p class="text-xs text-zinc-500">{{ __('Highest') }}</p>
            </div>
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 text-center">
                <p class="text-2xl font-bold text-red-600">{{ $stats['lowest_score'] }}%</p>
                <p class="text-xs text-zinc-500">{{ __('Lowest') }}</p>
            </div>
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 text-center">
                <p class="text-2xl font-bold text-green-600">{{ $stats['passed'] }}</p>
                <p class="text-xs text-zinc-500">{{ __('Passed') }}</p>
            </div>
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 text-center">
                <p class="text-2xl font-bold text-red-600">{{ $stats['failed'] }}</p>
                <p class="text-xs text-zinc-500">{{ __('Failed') }}</p>
            </div>
        </div>

        {{-- Student Results Table --}}
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Student') }}</flux:table.column>
                <flux:table.column>{{ __('Score') }}</flux:table.column>
                <flux:table.column class="hidden sm:table-cell">{{ __('Percentage') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column class="hidden md:table-cell">{{ __('Time') }}</flux:table.column>
                <flux:table.column class="hidden sm:table-cell">{{ __('Submitted') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($attempts as $attempt)
                    <flux:table.row>
                        <flux:table.cell class="font-medium">{{ $attempt->student?->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $attempt->score ?? 0 }}/{{ $attempt->total_points ?? $quiz->total_questions }}</flux:table.cell>
                        <flux:table.cell class="hidden sm:table-cell font-semibold">{{ number_format($attempt->percentage ?? 0, 1) }}%</flux:table.cell>
                        <flux:table.cell>
                            @if ($attempt->passed)
                                <flux:badge color="green" size="sm">{{ __('Passed') }}</flux:badge>
                            @else
                                <flux:badge color="red" size="sm">{{ __('Failed') }}</flux:badge>
                            @endif
                            @if ($attempt->status === 'timed_out')
                                <flux:badge color="amber" size="sm">{{ __('Timed Out') }}</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="hidden md:table-cell text-zinc-500">
                            @if ($attempt->time_spent_seconds)
                                {{ floor($attempt->time_spent_seconds / 60) }}m {{ $attempt->time_spent_seconds % 60 }}s
                            @else
                                —
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="hidden sm:table-cell text-zinc-500">
                            {{ $attempt->submitted_at?->format('M j, g:ia') ?? '—' }}
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="text-center py-8">
                            {{ __('No students have taken this quiz yet.') }}
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>
</x-layouts::app>
