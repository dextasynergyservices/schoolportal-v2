<x-layouts::app :title="__('CBT Results')">
    <div class="space-y-6">
        <x-student-header
            :title="__('CBT Results')"
            :description="__('Your exam, assessment, and assignment results.')"
        />

        {{-- Category Filter --}}
        <div class="flex flex-wrap items-center gap-3" role="tablist" aria-label="{{ __('Category filter') }}">
            @foreach ([['all', 'All'], ['exam', 'Exams'], ['assessment', 'Assessments'], ['assignment', 'Assignments']] as [$key, $label])
                <a href="{{ route('student.report-cards.cbt-results', ['category' => $key]) }}"
                   role="tab"
                   aria-selected="{{ $selectedCategory === $key ? 'true' : 'false' }}"
                   @if ($selectedCategory === $key) aria-current="page" @endif
                   role="tab"
                   aria-selected="{{ $selectedCategory === $key ? 'true' : 'false' }}"
                   @if ($selectedCategory === $key) aria-current="page" @endif
                   class="px-3 py-1.5 rounded-lg text-sm font-medium transition {{ $selectedCategory === $key ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-400 dark:hover:bg-zinc-700' }}"
                   wire:navigate>
                    {{ __($label) }}
                </a>
            @endforeach
        </div>

        @if ($attempts->isEmpty())
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-8 text-center">
                <flux:icon name="computer-desktop" class="mx-auto size-12 text-zinc-300 dark:text-zinc-600 mb-3" />
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-1">{{ __('No CBT Results Yet') }}</h3>
                <p class="text-sm text-zinc-500">{{ __('Your completed CBT results will appear here.') }}</p>
            </div>
        @else
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-xs text-zinc-500 uppercase bg-zinc-50 dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700">
                            <tr>
                                <th class="px-4 py-3 text-left">{{ __('Title') }}</th>
                                <th class="px-3 py-3 text-left">{{ __('Subject') }}</th>
                                <th class="px-3 py-3 text-center">{{ __('Type') }}</th>
                                <th class="px-3 py-3 text-center">{{ __('Score') }}</th>
                                <th class="px-3 py-3 text-center">{{ __('Percentage') }}</th>
                                <th class="px-3 py-3 text-center">{{ __('Grade') }}</th>
                                <th class="px-3 py-3 text-center">{{ __('Status') }}</th>
                                <th class="px-3 py-3 text-center">{{ __('Date') }}</th>
                                <th class="px-3 py-3 text-center">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                            @foreach ($attempts as $attempt)
                                @php
                                    $exam = $attempt->exam;
                                    $categoryLabel = match($exam->category ?? '') {
                                        'exam' => 'Exam',
                                        'assessment' => 'Assessment',
                                        'assignment' => 'Assignment',
                                        default => ucfirst($exam->category ?? ''),
                                    };
                                    $categoryColor = match($exam->category ?? '') {
                                        'exam' => 'red',
                                        'assessment' => 'blue',
                                        'assignment' => 'amber',
                                        default => 'zinc',
                                    };
                                    $routePrefix = 'student.exams';
                                @endphp
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                    <td class="px-4 py-3 font-medium text-zinc-900 dark:text-white">{{ $exam->title ?? '—' }}</td>
                                    <td class="px-3 py-3 text-zinc-600 dark:text-zinc-400">{{ $exam->subject->short_name ?? $exam->subject->name ?? '—' }}</td>
                                    <td class="px-3 py-3 text-center">
                                        <flux:badge size="sm" :color="$categoryColor">{{ $categoryLabel }}</flux:badge>
                                    </td>
                                    <td class="px-3 py-3 text-center font-semibold {{ $attempt->passed ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $attempt->score ?? '?' }}/{{ $attempt->total_points }}
                                    </td>
                                    <td class="px-3 py-3 text-center font-semibold {{ $attempt->passed ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $attempt->percentage !== null ? number_format($attempt->percentage, 1) . '%' : '—' }}
                                    </td>
                                    <td class="px-3 py-3 text-center">
                                        @if (isset($grades[$attempt->id]) && $grades[$attempt->id])
                                            <span class="text-sm font-semibold text-indigo-600 dark:text-indigo-400" title="{{ $grades[$attempt->id]['label'] }}">{{ $grades[$attempt->id]['grade'] }}</span>
                                        @else
                                            <span class="text-zinc-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 text-center">
                                        @if ($attempt->status === 'grading')
                                            <flux:badge size="sm" color="amber">{{ __('Grading') }}</flux:badge>
                                        @elseif ($attempt->passed)
                                            <flux:badge size="sm" color="green">{{ __('Passed') }}</flux:badge>
                                        @else
                                            <flux:badge size="sm" color="red">{{ __('Failed') }}</flux:badge>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 text-center text-zinc-500">{{ $attempt->submitted_at?->format('M d, Y') ?? '—' }}</td>
                                    <td class="px-3 py-3 text-center">
                                        <a href="{{ route($routePrefix . '.results', $attempt) }}" wire:navigate>
                                            <flux:button variant="subtle" size="sm" icon="eye">{{ __('Review') }}</flux:button>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4">{{ $attempts->links() }}</div>
        @endif
    </div>
</x-layouts::app>
