{{-- Question Bank Browser — Livewire modal for importing questions into an exam --}}
<div>
@if ($open)
<div
    class="fixed inset-0 z-50 flex items-start justify-center pt-10 px-4 pb-10"
    x-data
    x-init="$el.scrollTop = 0"
>
    {{-- Backdrop --}}
    <div
        class="absolute inset-0 bg-black/50 backdrop-blur-sm"
        wire:click="$set('open', false)"
    ></div>

    {{-- Panel --}}
    <div class="relative z-10 w-full max-w-3xl rounded-2xl bg-white dark:bg-zinc-900 shadow-2xl flex flex-col max-h-[85vh]">

        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
            <div>
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Import from Question Bank') }}</h2>
                <p class="text-sm text-zinc-500">
                    @if ($selectedCount > 0)
                        <span class="text-indigo-600 dark:text-indigo-400 font-medium">{{ $selectedCount }} {{ Str::plural('question', $selectedCount) }} selected</span>
                    @else
                        {{ __('Select questions to import into this exam.') }}
                    @endif
                </p>
            </div>
            <button wire:click="$set('open', false)" class="p-2 rounded-full hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Filters --}}
        <div class="flex flex-wrap items-center gap-2 px-6 py-3 bg-zinc-50 dark:bg-zinc-800/50 border-b border-zinc-200 dark:border-zinc-700">
            <input
                wire:model.live.debounce.300ms="search"
                type="search"
                placeholder="{{ __('Search questions…') }}"
                class="rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-1.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none w-48"
            />
            <select wire:model.live="filterType" class="rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-1.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                <option value="">{{ __('All Types') }}</option>
                <option value="multiple_choice">MCQ</option>
                <option value="true_false">True/False</option>
                <option value="fill_blank">Fill Blank</option>
                <option value="short_answer">Short Answer</option>
                <option value="theory">Theory/Essay</option>
            </select>
            <select wire:model.live="filterDifficulty" class="rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-1.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                <option value="">{{ __('Any Difficulty') }}</option>
                <option value="easy">Easy</option>
                <option value="medium">Medium</option>
                <option value="hard">Hard</option>
            </select>
            <select wire:model.live="filterSubjectId" class="rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-1.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                <option value="">{{ __('All Subjects') }}</option>
                @foreach ($subjects as $subject)
                    <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                @endforeach
            </select>

            <div class="ml-auto flex gap-2">
                @if ($selectedCount > 0)
                    <button wire:click="deselectAll" class="text-xs text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 px-2 py-1 rounded">
                        {{ __('Deselect all') }}
                    </button>
                @endif
                <button wire:click="selectAll" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline px-2 py-1 rounded font-medium">
                    {{ __('Select page') }}
                </button>
            </div>
        </div>

        {{-- Question List --}}
        <div class="flex-1 overflow-y-auto px-6 py-4 space-y-3" wire:loading.class="opacity-50">
            @forelse ($questions as $question)
                @php $isSelected = isset($selected[$question->id]); @endphp
                <button
                    type="button"
                    wire:click="toggleSelect({{ $question->id }})"
                    class="w-full text-left rounded-xl border-2 px-4 py-3 transition-all focus:outline-none {{ $isSelected ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'border-zinc-200 dark:border-zinc-700 hover:border-indigo-300 dark:hover:border-indigo-700 bg-white dark:bg-zinc-800/50' }}"
                >
                    <div class="flex items-start gap-3">
                        {{-- Checkbox indicator --}}
                        <div class="mt-0.5 flex-shrink-0 flex h-5 w-5 items-center justify-center rounded border-2 {{ $isSelected ? 'border-indigo-500 bg-indigo-500' : 'border-zinc-300 dark:border-zinc-600' }}">
                            @if ($isSelected)
                                <svg class="size-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            @endif
                        </div>

                        <div class="flex-1 min-w-0 space-y-1">
                            {{-- Badges --}}
                            <div class="flex flex-wrap items-center gap-1.5">
                                @php
                                    $typeColors = [
                                        'multiple_choice' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300',
                                        'true_false'      => 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300',
                                        'fill_blank'      => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                                        'short_answer'    => 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300',
                                        'theory'          => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
                                        'matching'        => 'bg-pink-100 text-pink-700 dark:bg-pink-900/40 dark:text-pink-300',
                                    ];
                                    $diffColors = ['easy' => 'text-green-600', 'medium' => 'text-amber-600', 'hard' => 'text-red-500'];
                                @endphp
                                <span class="inline-flex items-center rounded px-1.5 py-0.5 text-xs font-medium {{ $typeColors[$question->type] ?? 'bg-zinc-100 text-zinc-600' }}">
                                    {{ $question->typeLabel() }}
                                </span>
                                <span class="text-xs font-medium {{ $diffColors[$question->difficulty] ?? '' }}">
                                    {{ ucfirst($question->difficulty) }}
                                </span>
                                @if ($question->subject)
                                    <span class="text-xs text-zinc-400">· {{ $question->subject->name }}</span>
                                @endif
                                <span class="text-xs text-zinc-400">· {{ $question->points }} {{ Str::plural('pt', $question->points) }}</span>
                                @if ($question->times_used > 0)
                                    <span class="text-xs text-zinc-400">· used {{ $question->times_used }}×</span>
                                @endif
                            </div>

                            {{-- Question text --}}
                            <p class="text-sm text-zinc-800 dark:text-zinc-200 line-clamp-2 font-medium">
                                {{ $question->question_text }}
                            </p>

                            {{-- Options preview for MCQ --}}
                            @if ($question->type === 'multiple_choice' && $question->options)
                                <div class="flex flex-wrap gap-x-3 gap-y-0.5">
                                    @foreach (array_slice($question->options, 0, 4) as $opt)
                                        <span class="text-xs {{ $opt === $question->correct_answer ? 'text-green-600 dark:text-green-400 font-semibold' : 'text-zinc-400' }}">
                                            @if ($opt === $question->correct_answer)✓ @endif{{ Str::limit($opt, 30) }}
                                        </span>
                                    @endforeach
                                </div>
                            @elseif ($question->type === 'true_false')
                                <span class="text-xs text-green-600 dark:text-green-400 font-semibold">✓ {{ $question->correct_answer }}</span>
                            @endif
                        </div>
                    </div>
                </button>
            @empty
                <div class="py-12 text-center">
                    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
                        <svg class="size-6 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z"/></svg>
                    </div>
                    <p class="text-sm text-zinc-500">{{ __('No questions match your filters.') }}</p>
                </div>
            @endforelse

            {{-- Pagination --}}
            @if ($questions->hasPages())
                <div class="mt-4">
                    {{ $questions->links() }}
                </div>
            @endif
        </div>

        {{-- Footer --}}
        <div class="flex items-center justify-between px-6 py-4 border-t border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 rounded-b-2xl">
            <span class="text-sm text-zinc-500">
                @if ($selectedCount > 0)
                    {{ $selectedCount }} {{ Str::plural('question', $selectedCount) }} selected
                @else
                    {{ $questions->total() }} {{ Str::plural('question', $questions->total()) }} in bank
                @endif
            </span>
            <div class="flex gap-3">
                <button
                    type="button"
                    wire:click="$set('open', false)"
                    class="px-4 py-2 text-sm font-medium rounded-lg border border-zinc-300 dark:border-zinc-600 hover:bg-zinc-50 dark:hover:bg-zinc-800 text-zinc-700 dark:text-zinc-300 transition-colors"
                >
                    {{ __('Cancel') }}
                </button>
                <button
                    type="button"
                    wire:click="importSelected"
                    @disabled($selectedCount === 0)
                    class="px-5 py-2 text-sm font-semibold rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-indigo-500 {{ $selectedCount > 0 ? 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-400 cursor-not-allowed' }}"
                >
                    {{ $selectedCount > 0 ? __('Import :n', ['n' => $selectedCount]) : __('Import') }}
                </button>
            </div>
        </div>
    </div>
</div>
@endif
</div>
