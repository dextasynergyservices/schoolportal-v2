<x-layouts::app :title="__('Student Insights')">
    @include('partials.dashboard-styles')

    <div class="max-w-5xl mx-auto space-y-6">
        {{-- ── Page header ── --}}
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold text-zinc-900 dark:text-white">{{ __('Student Performance Insights') }}</h1>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('Search, filter, and review student performance across quizzes and games.') }}
                </p>
            </div>
            <a href="{{ route('admin.analytics') }}" wire:navigate
               class="inline-flex items-center gap-1.5 text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white transition-colors">
                <flux:icon.arrow-left class="w-4 h-4" />
                {{ __('Back to Analytics') }}
            </a>
        </div>

        {{-- ── Insights Component (full mode) ── --}}
        <div class="dash-animate">
            <livewire:admin.student-insights
                :session-id="$currentSession?->id"
                :term-id="$currentTerm?->id"
                :compact="false"
            />
        </div>
    </div>
</x-layouts::app>
