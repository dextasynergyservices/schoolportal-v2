<x-layouts::app :title="__('Student Performance Trends')">
    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold">{{ __('Student Performance Trends') }}</h1>
                <p class="text-sm text-zinc-500">{{ __('Individual student progress over time across exams') }}</p>
            </div>
            <div class="flex items-center gap-2">
                <flux:button href="{{ route('teacher.performance.subjects') }}" variant="subtle" icon="chart-bar" wire:navigate>{{ __('Subject Trends') }}</flux:button>
            </div>
        </div>

        <livewire:admin.student-performance-trends :class-id="$defaultClassId" />
    </div>
</x-layouts::app>
