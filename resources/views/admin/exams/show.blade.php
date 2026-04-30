<x-layouts::app :title="$exam->title">
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold">{{ $exam->title }}</h1>
                <p class="text-sm text-zinc-500">
                    {{ $exam->subject?->name }} · {{ $exam->class?->name }} · {{ $exam->session?->name }} · {{ $exam->term?->name }}
                </p>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                @if ($exam->is_published)
                    <flux:button href="{{ route($routePrefix . '.monitor', $exam) }}" variant="primary" icon="signal" wire:navigate>{{ __('Live Monitor') }}</flux:button>
                @endif
                <flux:button href="{{ route($routePrefix . '.analytics', $exam) }}" variant="subtle" icon="chart-bar-square" wire:navigate>{{ __('Analytics') }}</flux:button>
                <flux:button href="{{ route($routePrefix . '.results', $exam) }}" variant="subtle" icon="clipboard-document-list" wire:navigate>{{ __('Results') }}</flux:button>
                <flux:button href="{{ route($routePrefix . '.edit', $exam) }}" variant="subtle" icon="pencil-square" wire:navigate>{{ __('Edit') }}</flux:button>
                @if ($exam->status === 'approved' && ! $exam->is_published)
                    <form method="POST" action="{{ route($routePrefix . '.publish', $exam) }}" class="inline">
                        @csrf @method('PATCH')
                        <flux:button type="submit" variant="primary" icon="arrow-up-circle">{{ __('Publish') }}</flux:button>
                    </form>
                @elseif ($exam->is_published)
                    <form method="POST" action="{{ route($routePrefix . '.unpublish', $exam) }}" class="inline">
                        @csrf @method('PATCH')
                        <flux:button type="submit" variant="subtle" icon="arrow-down-circle">{{ __('Unpublish') }}</flux:button>
                    </form>
                @endif
                <flux:button href="{{ route($routePrefix . '.index') }}" variant="ghost" icon="arrow-left" wire:navigate>{{ __('Back') }}</flux:button>
            </div>
        </div>

        @if (session('success'))
            <flux:callout variant="success" icon="check-circle">{{ session('success') }}</flux:callout>
        @endif

        {{-- Status & Meta --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <flux:card class="p-4">
                <span class="text-xs text-zinc-500 block">{{ __('Status') }}</span>
                @php
                    $statusColors = ['draft' => 'zinc', 'pending' => 'amber', 'approved' => 'green', 'rejected' => 'red'];
                @endphp
                <flux:badge :color="$statusColors[$exam->status] ?? 'zinc'" size="sm" class="mt-1">{{ ucfirst($exam->status) }}</flux:badge>
                @if ($exam->is_published)
                    <flux:badge color="green" size="sm" class="ml-1 mt-1">{{ __('Published') }}</flux:badge>
                @endif
            </flux:card>
            <flux:card class="p-4">
                <span class="text-xs text-zinc-500 block">{{ __('Questions / Points') }}</span>
                <span class="font-semibold">{{ $exam->total_questions }} questions · {{ $exam->total_points }} pts</span>
            </flux:card>
            <flux:card class="p-4">
                <span class="text-xs text-zinc-500 block">{{ __('Time / Score') }}</span>
                <span class="font-semibold">
                    {{ $exam->time_limit_minutes ? $exam->time_limit_minutes . ' min' : 'No limit' }}
                    · {{ $exam->passing_score }}% to pass
                </span>
            </flux:card>
        </div>

        @if ($exam->description)
            <flux:card class="p-4">
                <span class="text-xs font-medium text-zinc-500 block mb-1">{{ __('Description') }}</span>
                <p class="text-sm">{{ $exam->description }}</p>
            </flux:card>
        @endif

        @if ($exam->instructions)
            <flux:card class="p-4">
                <span class="text-xs font-medium text-zinc-500 block mb-1">{{ __('Student Instructions') }}</span>
                <p class="text-sm whitespace-pre-line">{{ $exam->instructions }}</p>
            </flux:card>
        @endif

        {{-- Settings Chips --}}
        <div class="flex flex-wrap gap-2">
            @if ($exam->shuffle_questions) <flux:badge color="zinc" size="sm">Shuffle Questions</flux:badge> @endif
            @if ($exam->shuffle_options) <flux:badge color="zinc" size="sm">Shuffle Options</flux:badge> @endif
            @if ($exam->show_correct_answers) <flux:badge color="zinc" size="sm">Show Answers</flux:badge> @endif
            @if ($exam->prevent_tab_switch) <flux:badge color="red" size="sm">Tab Switch Detection</flux:badge> @endif
            @if ($exam->prevent_copy_paste) <flux:badge color="red" size="sm">No Copy/Paste</flux:badge> @endif
            @if ($exam->randomize_per_student) <flux:badge color="zinc" size="sm">Randomize Per Student</flux:badge> @endif
            @if ($exam->max_tab_switches) <flux:badge color="zinc" size="sm">Max {{ $exam->max_tab_switches }} tab switches</flux:badge> @endif
            @if ($exam->available_from) <flux:badge color="zinc" size="sm">From: {{ $exam->available_from->format('M d, Y H:i') }}</flux:badge> @endif
            @if ($exam->available_until) <flux:badge color="zinc" size="sm">Until: {{ $exam->available_until->format('M d, Y H:i') }}</flux:badge> @endif
            <flux:badge color="zinc" size="sm">Max {{ $exam->max_attempts }} attempts</flux:badge>
            @if ($exam->scoreComponent) <flux:badge color="blue" size="sm">{{ $exam->scoreComponent->name }}</flux:badge> @endif
        </div>

        {{-- Teacher Action Status (if applicable) --}}
        @if ($exam->latestTeacherAction)
            <flux:card class="p-4">
                <span class="text-xs font-medium text-zinc-500 block mb-1">{{ __('Approval Status') }}</span>
                @php $action = $exam->latestTeacherAction; @endphp
                @if ($action->status === 'pending')
                    <flux:callout variant="warning" icon="clock" class="text-sm">{{ __('Pending admin approval') }}</flux:callout>
                @elseif ($action->status === 'rejected')
                    <flux:callout variant="danger" icon="x-circle" class="text-sm">
                        {{ __('Rejected') }}@if ($action->rejection_reason): {{ $action->rejection_reason }}@endif
                    </flux:callout>
                @elseif ($action->status === 'approved')
                    <flux:callout variant="success" icon="check-circle" class="text-sm">{{ __('Approved') }}</flux:callout>
                @endif
            </flux:card>
        @endif

        {{-- Questions --}}
        <div class="space-y-4">
            <h2 class="text-lg font-semibold">{{ __('Questions') }}</h2>

            @php $currentSection = null; @endphp
            @foreach ($exam->questions->sortBy('sort_order') as $idx => $question)
                @if ($question->section_label && $question->section_label !== $currentSection)
                    @php $currentSection = $question->section_label; @endphp
                    <div class="border-t pt-4 mt-2">
                        <h3 class="font-semibold text-zinc-600 dark:text-zinc-400">{{ $currentSection }}</h3>
                    </div>
                @endif

                <flux:card class="p-4">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1 space-y-2">
                            <div class="flex items-center gap-2 text-sm text-zinc-500">
                                <span class="font-semibold">Q{{ $idx + 1 }}.</span>
                                @php
                                    $qTypeColors = ['multiple_choice' => 'blue', 'true_false' => 'green', 'fill_blank' => 'amber', 'short_answer' => 'purple', 'theory' => 'red', 'matching' => 'cyan'];
                                    $qTypeLabels = ['multiple_choice' => 'MCQ', 'true_false' => 'T/F', 'fill_blank' => 'Fill', 'short_answer' => 'Short', 'theory' => 'Theory', 'matching' => 'Match'];
                                @endphp
                                <flux:badge :color="$qTypeColors[$question->type] ?? 'zinc'" size="sm">{{ $qTypeLabels[$question->type] ?? $question->type }}</flux:badge>
                                <span>{{ $question->points }} pt{{ $question->points > 1 ? 's' : '' }}</span>
                            </div>

                            <p class="font-medium">{{ $question->question_text }}</p>

                            {{-- Options display --}}
                            @if (in_array($question->type, ['multiple_choice', 'true_false']))
                                <ul class="space-y-1 ml-4">
                                    @foreach ($question->options ?? [] as $opt)
                                        <li class="flex items-center gap-2 text-sm {{ $opt === $question->correct_answer ? 'text-green-700 dark:text-green-400 font-medium' : 'text-zinc-600 dark:text-zinc-400' }}">
                                            @if ($opt === $question->correct_answer) ✓ @else ○ @endif
                                            {{ is_string($opt) ? $opt : ($opt['left'] ?? '') }}
                                        </li>
                                    @endforeach
                                </ul>
                            @elseif ($question->type === 'fill_blank')
                                <p class="text-sm text-green-700 dark:text-green-400">{{ __('Answer') }}: {{ $question->correct_answer }}</p>
                            @elseif ($question->type === 'matching')
                                <div class="grid grid-cols-2 gap-2 text-sm ml-4">
                                    @foreach ($question->options ?? [] as $pair)
                                        <span class="text-zinc-700 dark:text-zinc-300">{{ $pair['left'] ?? '' }}</span>
                                        <span class="text-zinc-500">↔ {{ $pair['right'] ?? '' }}</span>
                                    @endforeach
                                </div>
                            @endif

                            @if ($question->marking_guide)
                                <div class="text-sm text-zinc-500 bg-zinc-50 dark:bg-zinc-800 rounded p-2 mt-2">
                                    <span class="font-medium">{{ __('Marking Guide') }}:</span> {{ $question->marking_guide }}
                                </div>
                            @endif

                            @if ($question->sample_answer)
                                <div class="text-sm text-zinc-500 bg-zinc-50 dark:bg-zinc-800 rounded p-2">
                                    <span class="font-medium">{{ __('Sample Answer') }}:</span> {{ Str::limit($question->sample_answer, 200) }}
                                </div>
                            @endif

                            @if ($question->explanation)
                                <p class="text-xs text-zinc-400 italic">💡 {{ $question->explanation }}</p>
                            @endif
                        </div>
                    </div>
                </flux:card>
            @endforeach
        </div>
    </div>
</x-layouts::app>
