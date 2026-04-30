<x-layouts::app :title="__(':type Analytics: :title', ['type' => $categoryLabel, 'title' => $exam->title])">
    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold">{{ $exam->title }} — {{ __('Analytics') }}</h1>
                <p class="text-sm text-zinc-500">
                    {{ $exam->subject?->name }} · {{ $exam->class?->name }} · {{ $exam->session?->name }} · {{ $exam->term?->name }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                @if ($exam->is_published)
                    <flux:button href="{{ route($routePrefix . '.monitor', $exam) }}" variant="subtle" icon="signal" wire:navigate>{{ __('Live Monitor') }}</flux:button>
                @endif
                <flux:button href="{{ route($routePrefix . '.results', $exam) }}" variant="subtle" icon="clipboard-document-list" wire:navigate>{{ __('Results') }}</flux:button>
                <flux:button href="{{ route($routePrefix . '.show', $exam) }}" variant="ghost" icon="arrow-left" wire:navigate>{{ __('Back') }}</flux:button>
            </div>
        </div>

        {{-- Analytics Component --}}
        <livewire:admin.exam-analytics :exam="$exam" />
    </div>
</x-layouts::app>
