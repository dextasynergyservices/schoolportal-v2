<x-layouts::app :title="__(':type Results', ['type' => $categoryLabel])">
    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold">{{ $exam->title }} — {{ __('Results') }}</h1>
                <p class="text-sm text-zinc-500">
                    {{ $exam->subject?->name }} · {{ $exam->class?->name }} · {{ $exam->session?->name }} · {{ $exam->term?->name }}
                </p>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                @if ($theoryQuestionCount > 0)
                    <flux:button href="{{ route($routePrefix . '.bulk-grade', $exam) }}" variant="primary" icon="academic-cap" wire:navigate>
                        {{ __('Bulk Grade Theory') }}
                    </flux:button>
                @endif
                <flux:button href="{{ route($routePrefix . '.analytics', $exam) }}" variant="subtle" icon="chart-bar-square" wire:navigate>{{ __('Analytics') }}</flux:button>
                @if ($exam->is_published)
                    <flux:button href="{{ route($routePrefix . '.monitor', $exam) }}" variant="subtle" icon="signal" wire:navigate>{{ __('Monitor') }}</flux:button>
                @endif
                @if ($attempts->isNotEmpty())
                    <flux:button href="{{ route($routePrefix . '.export-results-csv', $exam) }}" variant="subtle" icon="arrow-down-tray">{{ __('Export CSV') }}</flux:button>
                @endif
                <flux:button href="{{ route($routePrefix . '.show', $exam) }}" variant="ghost" icon="arrow-left" wire:navigate>{{ __('Back') }}</flux:button>
            </div>
        </div>

        @if (session('success'))
            <flux:callout variant="success" icon="check-circle">{{ session('success') }}</flux:callout>
        @endif

        {{-- Stats Summary --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <flux:card class="p-4">
                <span class="text-xs text-zinc-500 block">{{ __('Total Attempts') }}</span>
                <span class="text-2xl font-bold">{{ $stats['total_attempts'] }}</span>
            </flux:card>
            @if ($theoryQuestionCount > 0)
                <flux:card class="p-4">
                    <span class="text-xs text-zinc-500 block">{{ __('Pending Grading') }}</span>
                    <span class="text-2xl font-bold {{ $stats['pending_grading'] > 0 ? 'text-amber-600' : 'text-green-600' }}">{{ $stats['pending_grading'] }}</span>
                </flux:card>
            @endif
            <flux:card class="p-4">
                <span class="text-xs text-zinc-500 block">{{ __('Average Score') }}</span>
                <span class="text-2xl font-bold">{{ $stats['average_score'] !== null ? $stats['average_score'] . '%' : '—' }}</span>
            </flux:card>
            <flux:card class="p-4">
                <span class="text-xs text-zinc-500 block">{{ __('Pass / Fail') }}</span>
                <span class="text-2xl font-bold">
                    <span class="text-green-600">{{ $stats['pass_count'] }}</span>
                    /
                    <span class="text-red-600">{{ $stats['fail_count'] }}</span>
                </span>
            </flux:card>
        </div>

        {{-- Results Table --}}
        <flux:card>
            @if ($attempts->isEmpty())
                <div class="p-8 text-center text-zinc-500">
                    <flux:icon name="clipboard-document-list" class="size-12 mx-auto mb-2 text-zinc-300" />
                    <p>{{ __('No submissions yet.') }}</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-zinc-500">
                                <th class="p-3 font-medium">{{ __('Student') }}</th>
                                <th class="p-3 font-medium">{{ __('Status') }}</th>
                                <th class="p-3 font-medium">{{ __('Score') }}</th>
                                <th class="p-3 font-medium">{{ __('Percentage') }}</th>
                                <th class="p-3 font-medium">{{ __('Grade') }}</th>
                                <th class="p-3 font-medium">{{ __('Result') }}</th>
                                <th class="p-3 font-medium">{{ __('Submitted') }}</th>
                                <th class="p-3 font-medium">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($attempts as $attempt)
                                <tr class="border-b hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                    <td class="p-3">
                                        <div class="font-medium">{{ $attempt->student?->name }}</div>
                                        <div class="text-xs text-zinc-400">{{ $attempt->student?->username }}</div>
                                    </td>
                                    <td class="p-3">
                                        @php
                                            $statusMap = [
                                                'submitted' => ['color' => 'green', 'label' => 'Submitted'],
                                                'timed_out' => ['color' => 'amber', 'label' => 'Timed Out'],
                                                'grading' => ['color' => 'amber', 'label' => 'Pending Grading'],
                                                'graded' => ['color' => 'blue', 'label' => 'Graded'],
                                            ];
                                            $s = $statusMap[$attempt->status] ?? ['color' => 'zinc', 'label' => ucfirst($attempt->status)];
                                        @endphp
                                        <flux:badge :color="$s['color']" size="sm">{{ __($s['label']) }}</flux:badge>
                                    </td>
                                    <td class="p-3 font-mono">
                                        {{ $attempt->score !== null ? $attempt->score . '/' . $attempt->total_points : '—' }}
                                    </td>
                                    <td class="p-3 font-mono">
                                        {{ $attempt->percentage !== null ? $attempt->percentage . '%' : '—' }}
                                    </td>
                                    <td class="p-3">
                                        @if (isset($grades[$attempt->id]) && $grades[$attempt->id])
                                            <span class="text-sm font-semibold text-indigo-600 dark:text-indigo-400">{{ $grades[$attempt->id]['grade'] }}</span>
                                            <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $grades[$attempt->id]['label'] }}</span>
                                        @else
                                            <span class="text-zinc-400">—</span>
                                        @endif
                                    </td>
                                    <td class="p-3">
                                        @if ($attempt->passed === true)
                                            <flux:badge color="green" size="sm">{{ __('Passed') }}</flux:badge>
                                        @elseif ($attempt->passed === false)
                                            <flux:badge color="red" size="sm">{{ __('Failed') }}</flux:badge>
                                        @else
                                            <span class="text-zinc-400">—</span>
                                        @endif
                                    </td>
                                    <td class="p-3 text-xs text-zinc-500">
                                        {{ $attempt->submitted_at?->diffForHumans() ?? '—' }}
                                    </td>
                                    <td class="p-3">
                                        @if (in_array($attempt->status, ['grading']) && $theoryQuestionCount > 0)
                                            <flux:button size="xs" href="{{ route($routePrefix . '.grade-student', [$exam, $attempt]) }}" variant="primary" icon="pencil-square" wire:navigate>
                                                {{ __('Grade') }}
                                            </flux:button>
                                        @elseif ($attempt->status === 'graded' && $theoryQuestionCount > 0)
                                            <flux:button size="xs" href="{{ route($routePrefix . '.grade-student', [$exam, $attempt]) }}" variant="subtle" icon="eye" wire:navigate>
                                                {{ __('Review') }}
                                            </flux:button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </flux:card>
    </div>
</x-layouts::app>
