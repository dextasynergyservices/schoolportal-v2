<x-layouts::app :title="__('Performance')">
    <div class="space-y-6">
        {{-- Header --}}
        <x-admin-header
            :title="__('Performance')"
            :description="__('Track performance trends across subjects and students.')"
        />

        {{-- Tabs --}}
        <div class="flex items-center gap-1 border-b border-zinc-200 dark:border-zinc-700">
            <a href="{{ route('teacher.performance.subjects') }}"
               class="px-4 py-2.5 text-sm font-medium border-b-2 transition {{ ($tab ?? 'subjects') === 'subjects' ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}"
               wire:navigate>
                {{ __('Subject Trends') }}
            </a>
            <a href="{{ route('teacher.performance.subjects', ['tab' => 'students']) }}"
               class="px-4 py-2.5 text-sm font-medium border-b-2 transition {{ ($tab ?? 'subjects') === 'students' ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}"
               wire:navigate>
                {{ __('Student Trends') }}
            </a>
        </div>

        {{-- Tab Content --}}
        @if (($tab ?? 'subjects') === 'subjects')
            <livewire:admin.subject-performance-trends :class-id="$defaultClassId" />
        @else
            <livewire:admin.student-performance-trends :class-id="$defaultClassId" />
        @endif
    </div>
</x-layouts::app>
