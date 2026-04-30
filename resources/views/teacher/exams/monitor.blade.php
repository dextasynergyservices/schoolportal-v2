<x-layouts::app :title="__('Live Monitor: :title', ['title' => $exam->title])">
    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl font-bold">{{ __('Live Monitor') }}</h1>
                    @if ($exam->is_published)
                        <span class="inline-flex items-center gap-1 text-xs text-green-600 font-medium">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                            </span>
                            {{ __('Live') }}
                        </span>
                    @endif
                </div>
                <p class="text-sm text-zinc-500">
                    {{ $exam->title }} · {{ $exam->subject?->name }} · {{ $exam->class?->name }}
                </p>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <flux:button href="{{ route($routePrefix . '.analytics', $exam) }}" variant="subtle" icon="chart-bar-square" wire:navigate>{{ __('Analytics') }}</flux:button>
                <flux:button href="{{ route($routePrefix . '.results', $exam) }}" variant="subtle" icon="clipboard-document-list" wire:navigate>{{ __('Results') }}</flux:button>
                <flux:button href="{{ route($routePrefix . '.show', $exam) }}" variant="ghost" icon="arrow-left" wire:navigate>{{ __('Back') }}</flux:button>
            </div>
        </div>

        {{-- Exam Meta --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <flux:card class="p-3">
                <span class="text-xs text-zinc-500">{{ __('Time Limit') }}</span>
                <span class="block font-semibold">{{ $exam->time_limit_minutes ? $exam->time_limit_minutes . ' min' : __('No limit') }}</span>
            </flux:card>
            <flux:card class="p-3">
                <span class="text-xs text-zinc-500">{{ __('Questions') }}</span>
                <span class="block font-semibold">{{ $exam->total_questions }}</span>
            </flux:card>
            <flux:card class="p-3">
                <span class="text-xs text-zinc-500">{{ __('Pass Score') }}</span>
                <span class="block font-semibold">{{ $exam->passing_score }}%</span>
            </flux:card>
            <flux:card class="p-3">
                <span class="text-xs text-zinc-500">{{ __('Max Attempts') }}</span>
                <span class="block font-semibold">{{ $exam->max_attempts }}</span>
            </flux:card>
        </div>

        {{-- Live Monitor Component (reuses admin Livewire component) --}}
        <livewire:admin.exam-monitor :exam="$exam" />
    </div>
</x-layouts::app>
