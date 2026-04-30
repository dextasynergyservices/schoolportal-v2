@php
    $typeLabel = match($report->report_type) {
        'midterm' => __('Mid-Term Report'),
        'full_term' => __('Full Term Report'),
        'session' => __('Session Report'),
        default => __('Report'),
    };
    $typeIcon = match($report->report_type) {
        'midterm' => 'document-text',
        'session' => 'chart-bar-square',
        default => 'document-chart-bar',
    };
    $typeColor = match($report->report_type) {
        'midterm' => 'amber',
        'full_term' => 'blue',
        'session' => 'purple',
        default => 'zinc',
    };
@endphp
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 rounded-lg border border-zinc-100 dark:border-zinc-700/50 bg-zinc-50/50 dark:bg-zinc-800/50 px-4 py-3">
    <div class="flex items-center gap-3 min-w-0">
        <div class="flex-shrink-0 flex items-center justify-center w-9 h-9 rounded-lg {{ match($report->report_type) { 'midterm' => 'bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400', 'full_term' => 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400', 'session' => 'bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400', default => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-400' } }}">
            <flux:icon :name="$typeIcon" class="size-5" />
        </div>
        <div class="min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $typeLabel }}</span>
                <flux:badge size="sm" :color="$typeColor">{{ ucwords(str_replace('_', ' ', $report->report_type)) }}</flux:badge>
            </div>
            <p class="text-xs text-zinc-500 mt-0.5">{{ $report->class->name ?? '' }}</p>
        </div>
    </div>

    <div class="flex items-center gap-4 sm:gap-6 flex-shrink-0">
        {{-- Stats --}}
        <div class="flex items-center gap-4 text-center">
            <div>
                <div class="text-sm font-bold text-indigo-600 dark:text-indigo-400">{{ number_format($report->average_weighted_score ?? 0, 1) }}%</div>
                <div class="text-[10px] text-zinc-400 uppercase">{{ $report->report_type === 'session' ? __('Avg') : __('Score') }}</div>
            </div>
            @if ($showPosition ?? true)
            <div>
                <div class="text-sm font-bold text-zinc-900 dark:text-white">
                    @if ($report->position)
                        {{ $report->position }}<sup class="text-[9px]">{{ match($report->position % 10) { 1 => 'st', 2 => 'nd', 3 => 'rd', default => 'th' } }}</sup>
                    @else
                        —
                    @endif
                </div>
                <div class="text-[10px] text-zinc-400 uppercase">{{ __('Pos') }}</div>
            </div>
            @endif
            <div>
                <div class="text-sm font-bold text-zinc-600 dark:text-zinc-300">{{ $report->subjects_count ?? 0 }}</div>
                <div class="text-[10px] text-zinc-400 uppercase">{{ __('Subj') }}</div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center gap-1.5">
            @php
                $showRoute = isset($child)
                    ? route('parent.children.report-cards.show', [$child, $report])
                    : route('student.report-cards.show', $report);
                $downloadRoute = isset($child)
                    ? route('parent.children.report-cards.download', [$child, $report])
                    : route('student.report-cards.download', $report);
            @endphp
            <a href="{{ $showRoute }}">
                <flux:button variant="primary" size="sm" icon="eye">{{ __('View') }}</flux:button>
            </a>
            <a href="{{ $downloadRoute }}">
                <flux:button variant="subtle" size="sm" icon="arrow-down-tray">{{ __('PDF') }}</flux:button>
            </a>
        </div>
    </div>
</div>
