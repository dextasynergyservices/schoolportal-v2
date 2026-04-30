<x-layouts::app :title="__('CBT Exam Results')">
    <div class="space-y-6">
        <div>
            <flux:button variant="subtle" size="sm" href="{{ route('parent.children.show', $child) }}" wire:navigate class="mb-2">
                <flux:icon name="arrow-left" class="size-4 mr-1" /> {{ __('Back to :name', ['name' => $child->name]) }}
            </flux:button>
            <h1 class="text-xl font-bold text-zinc-900 dark:text-white">{{ $child->name }} &mdash; {{ __('CBT Results') }}</h1>
        </div>

        @if ($attempts->isNotEmpty())
            @php
                $totalAttempts = $attempts->total();
                $gradedAttempts = $attempts->getCollection()->whereNotNull('percentage');
                $passedCount = $gradedAttempts->where('passed', true)->count();
                $avgPercentage = $gradedAttempts->count() > 0 ? $gradedAttempts->avg('percentage') : 0;
                $bestPercentage = $gradedAttempts->max('percentage') ?? 0;
            @endphp
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('CBT Attempts') }}</p>
                    <p class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ $totalAttempts }}</p>
                </div>
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Passed') }}</p>
                    <p class="mt-1 text-2xl font-bold text-green-600 dark:text-green-400">{{ $passedCount }} / {{ $gradedAttempts->count() }}</p>
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
                @foreach ($attempts as $attempt)
                    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $attempt->exam?->title }}</h3>
                                <p class="text-xs text-zinc-500 mt-0.5">
                                    {{ $attempt->exam?->subject?->name }}
                                    &middot; {{ $attempt->exam?->class?->name }}
                                    &middot; {{ $attempt->submitted_at?->format('M j, Y') }}
                                </p>
                                <div class="mt-1.5 flex items-center gap-2">
                                    @php
                                        $categoryColors = [
                                            'exam' => 'indigo',
                                            'assessment' => 'violet',
                                            'assignment' => 'fuchsia',
                                        ];
                                        $categoryColor = $categoryColors[$attempt->exam?->category] ?? 'zinc';
                                    @endphp
                                    <flux:badge :color="$categoryColor" size="sm">{{ ucfirst($attempt->exam?->category ?? 'exam') }}</flux:badge>

                                    @if ($attempt->status === 'grading')
                                        <flux:badge color="amber" size="sm">{{ __('Awaiting Grading') }}</flux:badge>
                                    @elseif ($attempt->passed === true)
                                        <flux:badge color="green" size="sm">{{ __('Passed') }}</flux:badge>
                                    @elseif ($attempt->passed === false)
                                        <flux:badge color="red" size="sm">{{ __('Failed') }}</flux:badge>
                                    @elseif ($attempt->status === 'timed_out')
                                        <flux:badge color="amber" size="sm">{{ __('Timed Out') }}</flux:badge>
                                    @else
                                        <flux:badge color="zinc" size="sm">{{ __('Submitted') }}</flux:badge>
                                    @endif
                                </div>
                            </div>
                            <div class="text-right">
                                @if ($attempt->percentage !== null)
                                    <p class="text-lg font-bold {{ $attempt->passed ? 'text-green-600' : 'text-red-600' }}">
                                        {{ number_format($attempt->percentage, 0) }}%
                                    </p>
                                    @if (isset($grades[$attempt->id]) && $grades[$attempt->id])
                                        <p class="text-xs font-semibold text-indigo-600 dark:text-indigo-400">{{ $grades[$attempt->id]['grade'] }} &mdash; {{ $grades[$attempt->id]['label'] }}</p>
                                    @endif
                                    <p class="text-xs text-zinc-500">{{ $attempt->score }} / {{ $attempt->total_points }}</p>
                                @else
                                    <p class="text-sm text-zinc-400 italic">{{ __('Pending') }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if ($attempts->hasPages())
                <div class="mt-4">{{ $attempts->links() }}</div>
            @endif
        @else
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-8 text-center">
                <flux:icon name="computer-desktop" class="mx-auto h-12 w-12 text-zinc-400" />
                <h3 class="mt-2 text-sm font-semibold text-zinc-900 dark:text-white">{{ __('No CBT results yet') }}</h3>
                <p class="mt-1 text-sm text-zinc-500">{{ __(':name hasn\'t taken any CBT exams, assessments, or assignments yet.', ['name' => $child->name]) }}</p>
            </div>
        @endif
    </div>
</x-layouts::app>
