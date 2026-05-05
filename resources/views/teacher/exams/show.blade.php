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
                    <flux:button href="{{ route($routePrefix . '.analytics', $exam) }}" variant="subtle" icon="chart-bar-square" wire:navigate>{{ __('Analytics') }}</flux:button>
                    <flux:button href="{{ route($routePrefix . '.results', $exam) }}" variant="subtle" icon="chart-bar" wire:navigate>{{ __('Results') }}</flux:button>
                @endif
                <flux:button href="{{ route($routePrefix . '.preview', $exam) }}" variant="subtle" icon="eye" wire:navigate>{{ __('Preview as Student') }}</flux:button>
                @if (in_array($exam->status, ['draft', 'pending', 'rejected']))
                    <flux:button href="{{ route($routePrefix . '.edit', $exam) }}" variant="subtle" icon="pencil-square" wire:navigate>{{ __('Edit') }}</flux:button>
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
                @php $statusColors = ['draft' => 'zinc', 'pending' => 'amber', 'approved' => 'green', 'rejected' => 'red']; @endphp
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

        {{-- Approval Status --}}
        @if ($exam->latestTeacherAction)
            @php $action = $exam->latestTeacherAction; @endphp
            <flux:card class="p-4">
                @if ($action->status === 'pending')
                    <flux:callout variant="warning" icon="clock">{{ __('Waiting for admin approval. You will be notified when reviewed.') }}</flux:callout>
                @elseif ($action->status === 'rejected')
                    <flux:callout variant="danger" icon="x-circle">
                        <strong>{{ __('Rejected by admin') }}</strong>
                        @if ($action->rejection_reason)
                            <p class="mt-1 text-sm">{{ $action->rejection_reason }}</p>
                        @endif
                        <p class="mt-2 text-sm">{{ __('You can edit and resubmit this exam.') }}</p>
                    </flux:callout>
                @elseif ($action->status === 'approved')
                    <flux:callout variant="success" icon="check-circle">{{ __('Approved by admin. The exam will be published on the scheduled date, or when the admin publishes it.') }}</flux:callout>
                @endif
            </flux:card>
        @endif

        @if ($exam->description)
            <flux:card class="p-4">
                <span class="text-xs font-medium text-zinc-500 block mb-1">{{ __('Description') }}</span>
                <p class="text-sm">{{ $exam->description }}</p>
            </flux:card>
        @endif

        {{-- Questions --}}
        <div class="space-y-4">
            <h2 class="text-lg font-semibold">{{ __('Questions') }} ({{ $exam->questions->count() }})</h2>

            @php $currentSection = null; @endphp
            @foreach ($exam->questions->sortBy('sort_order') as $idx => $question)
                @if ($question->section_label && $question->section_label !== $currentSection)
                    @php $currentSection = $question->section_label; @endphp
                    <div class="border-t pt-4 mt-2">
                        <h3 class="font-semibold text-zinc-600 dark:text-zinc-400">{{ $currentSection }}</h3>
                    </div>
                @endif

                <flux:card class="p-4">
                    <div class="flex items-start gap-3">
                        <span class="text-sm font-bold text-zinc-400">{{ $idx + 1 }}.</span>
                        <div class="flex-1 space-y-2">
                            <div class="flex items-center gap-2 text-xs text-zinc-500">
                                @php
                                    $qTypeColors = ['multiple_choice' => 'blue', 'true_false' => 'green', 'fill_blank' => 'amber', 'short_answer' => 'purple', 'theory' => 'red', 'matching' => 'cyan'];
                                    $qTypeLabels = ['multiple_choice' => 'MCQ', 'true_false' => 'T/F', 'fill_blank' => 'Fill', 'short_answer' => 'Short', 'theory' => 'Theory', 'matching' => 'Match'];
                                @endphp
                                <flux:badge :color="$qTypeColors[$question->type] ?? 'zinc'" size="sm">{{ $qTypeLabels[$question->type] ?? $question->type }}</flux:badge>
                                <span>{{ $question->points }} pt{{ $question->points > 1 ? 's' : '' }}</span>
                            </div>

                            <p class="font-medium">{{ $question->question_text }}</p>

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
                                        <span>{{ $pair['left'] ?? '' }}</span>
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
                                <p class="text-xs text-zinc-400 italic">{{ $question->explanation }}</p>
                            @endif
                        </div>
                    </div>
                </flux:card>
            @endforeach
        </div>
    </div>
</x-layouts::app>
