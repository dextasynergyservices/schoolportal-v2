<x-layouts::app :title="__(':name — Report Card', ['name' => $child->name])">
    <div class="space-y-6">
        {{-- Breadcrumb --}}
        <div class="flex items-center gap-2 flex-wrap">
            <flux:link href="{{ route('parent.dashboard') }}" wire:navigate class="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">
                {{ __('Dashboard') }}
            </flux:link>
            <flux:icon.chevron-right class="w-3 h-3 text-zinc-400" />
            <flux:link href="{{ route('parent.children.show', $child) }}" wire:navigate class="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">
                {{ $child->name }}
            </flux:link>
            <flux:icon.chevron-right class="w-3 h-3 text-zinc-400" />
            <flux:link href="{{ route('parent.children.report-cards', $child) }}" wire:navigate class="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">
                {{ __('Report Cards') }}
            </flux:link>
            <flux:icon.chevron-right class="w-3 h-3 text-zinc-400" />
            <flux:text class="text-sm">{{ $report->term->name ?? '' }}</flux:text>
        </div>

        <div class="flex items-center justify-between flex-wrap gap-3">
            <div>
                <h1 class="text-xl font-bold text-zinc-900 dark:text-white">{{ __(':name\'s Report Card', ['name' => $child->name]) }}</h1>
                <p class="text-sm text-zinc-500">{{ ($report->session->name ?? '') . ' — ' . ($report->term->name ?? '') }}</p>
            </div>
            <a href="{{ route('parent.children.report-cards.download', [$child, $report]) }}">
                <flux:button variant="primary" size="sm" icon="arrow-down-tray">{{ __('Download PDF') }}</flux:button>
            </a>
        </div>

        {{-- Summary Bar --}}
        <div class="grid grid-cols-2 sm:{{ $config?->show_position ? 'grid-cols-4' : 'grid-cols-3' }} gap-3">
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 text-center">
                <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ number_format($report->average_weighted_score ?? 0, 1) }}%</div>
                <div class="text-xs text-zinc-400 mt-1">{{ __('Average Score') }}</div>
            </div>
            @if ($config?->show_position)
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 text-center">
                <div class="text-2xl font-bold text-zinc-900 dark:text-white">
                    @if ($report->position)
                        {{ $report->position }}<sup class="text-sm">{{ match($report->position % 10) { 1 => 'st', 2 => 'nd', 3 => 'rd', default => 'th' } }}</sup>
                    @else
                        —
                    @endif
                </div>
                <div class="text-xs text-zinc-400 mt-1">{{ __('Position') }}</div>
            </div>
            @endif
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 text-center">
                <div class="text-2xl font-bold text-zinc-600 dark:text-zinc-300">{{ $report->out_of ?? '—' }}</div>
                <div class="text-xs text-zinc-400 mt-1">{{ __('Out Of') }}</div>
            </div>
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 text-center">
                <div class="text-2xl font-bold text-zinc-600 dark:text-zinc-300">{{ $report->subjects_count ?? 0 }}</div>
                <div class="text-xs text-zinc-400 mt-1">{{ __('Subjects') }}</div>
            </div>
        </div>

        {{-- Report Card Body (reuses same structure as student view) --}}
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 overflow-hidden shadow-sm">
            {{-- School Header --}}
            <div class="bg-gradient-to-r from-indigo-600 to-blue-600 px-6 py-5 text-white text-center">
                @if ($school->logo_url)
                    <img src="{{ $school->logo_url }}" alt="{{ $school->name }}" class="mx-auto mb-2 h-16 w-16 rounded-full object-cover border-2 border-white/30">
                @endif
                <h2 class="text-xl font-bold uppercase">{{ $school->name }}</h2>
                <p class="text-sm text-white/80">{{ $school->address ?? '' }}</p>
                <p class="mt-1 text-sm font-medium text-white/90">{{ __('Student Report Card') }}</p>
            </div>

            {{-- Student Info --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 px-6 py-4 bg-zinc-50 dark:bg-zinc-800/50 border-b border-zinc-200 dark:border-zinc-700 text-sm">
                <div>
                    <span class="text-zinc-400">{{ __('Name') }}</span>
                    <div class="font-medium text-zinc-900 dark:text-white">{{ $report->student->name ?? '—' }}</div>
                </div>
                <div>
                    <span class="text-zinc-400">{{ __('Admission No.') }}</span>
                    <div class="font-medium text-zinc-900 dark:text-white">{{ $report->student->studentProfile?->admission_number ?? '—' }}</div>
                </div>
                <div>
                    <span class="text-zinc-400">{{ __('Class') }}</span>
                    <div class="font-medium text-zinc-900 dark:text-white">{{ $report->class->name ?? '—' }}</div>
                </div>
                <div>
                    <span class="text-zinc-400">{{ __('Session / Term') }}</span>
                    <div class="font-medium text-zinc-900 dark:text-white">{{ $report->session->name ?? '' }} — {{ $report->term->name ?? '' }}</div>
                </div>
            </div>

            {{-- Subject Scores Table --}}
            @if ($report->subject_scores_snapshot && count($report->subject_scores_snapshot) > 0)
                @php
                    $compKeyFn = fn ($c) => $c['component_id'] ?? $c['short_name'] ?? $c['name'] ?? '';
                    $masterComponents = collect($report->subject_scores_snapshot)
                        ->flatMap(fn ($s) => $s['components'] ?? [])
                        ->unique($compKeyFn)
                        ->sortBy($compKeyFn)
                        ->values();
                @endphp
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-xs text-zinc-500 uppercase bg-zinc-50 dark:bg-zinc-800">
                            <tr>
                                <th class="px-4 py-3 text-left">{{ __('Subject') }}</th>
                                @foreach ($masterComponents as $comp)
                                    <th class="px-3 py-3 text-center">{{ $comp['short_name'] ?? $comp['name'] ?? '' }}{{ isset($comp['max_score']) ? ' ('.$comp['max_score'].')' : '' }}</th>
                                @endforeach
                                <th class="px-3 py-3 text-center bg-indigo-50/50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400">{{ __('Total (%)') }}</th>
                                <th class="px-3 py-3 text-center">{{ __('Grade') }}</th>
                                @if ($config?->show_position)
                                    <th class="px-3 py-3 text-center">{{ __('Position') }}</th>
                                @endif
                                <th class="px-3 py-3 text-center">{{ __('Class Avg') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                            @foreach ($report->subject_scores_snapshot as $snapshot)
                                @php
                                    $compLookup = collect($snapshot['components'] ?? [])->keyBy($compKeyFn);
                                @endphp
                                <tr class="hover:bg-zinc-50/30 dark:hover:bg-zinc-800/30">
                                    <td class="px-4 py-2.5 font-medium text-zinc-900 dark:text-white">{{ $snapshot['subject_name'] ?? '—' }}</td>
                                    @foreach ($masterComponents as $comp)
                                        @php $ck = $compKeyFn($comp); @endphp
                                        <td class="px-3 py-2.5 text-center text-zinc-600 dark:text-zinc-400">{{ $compLookup[$ck]['score'] ?? '—' }}</td>
                                    @endforeach
                                    <td class="px-3 py-2.5 text-center font-semibold text-indigo-600 dark:text-indigo-400 bg-indigo-50/30 dark:bg-indigo-900/10">
                                        {{ isset($snapshot['weighted_total']) ? number_format($snapshot['weighted_total'], 1) : '—' }}
                                    </td>
                                    <td class="px-3 py-2.5 text-center font-bold text-zinc-900 dark:text-white">{{ $snapshot['grade'] ?? '—' }}</td>
                                    @if ($config?->show_position)
                                        <td class="px-3 py-2.5 text-center text-zinc-600 dark:text-zinc-400">{{ $snapshot['position'] ?? '—' }}</td>
                                    @endif
                                    <td class="px-3 py-2.5 text-center text-zinc-500">{{ isset($snapshot['class_average']) ? number_format($snapshot['class_average'], 1) : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- Grading Key --}}
            @if ($gradingScale && $gradingScale->items->isNotEmpty())
                <div class="px-6 py-3 border-t border-zinc-200 dark:border-zinc-700 bg-zinc-50/50 dark:bg-zinc-800/30">
                    <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('Grading Key') }}</p>
                    <div class="flex flex-wrap gap-3 text-xs">
                        @foreach ($gradingScale->items as $item)
                            <span class="text-zinc-600 dark:text-zinc-400"><strong>{{ $item->grade }}</strong> = {{ $item->label }} ({{ $item->min_score }}–{{ $item->max_score }}%)</span>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Psychomotor & Affective Ratings --}}
            @if ($config && (($config->psychomotor_traits ?? []) || ($config->affective_traits ?? [])))
                <div class="grid md:grid-cols-2 gap-0 border-t border-zinc-200 dark:border-zinc-700">
                    @if (!empty($config->psychomotor_traits))
                        <div class="px-6 py-4 {{ !empty($config->affective_traits) ? 'border-r border-zinc-200 dark:border-zinc-700' : '' }}">
                            <h4 class="text-xs font-semibold uppercase text-zinc-500 mb-2">{{ __('Psychomotor Skills') }}</h4>
                            <div class="space-y-1.5">
                                @foreach ($config->psychomotor_traits as $trait)
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-zinc-600 dark:text-zinc-400">{{ $trait }}</span>
                                        @php $rating = $report->psychomotor_ratings[$trait] ?? null; @endphp
                                        <span class="font-medium text-zinc-900 dark:text-white">{{ $rating ?? '—' }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    @if (!empty($config->affective_traits))
                        <div class="px-6 py-4">
                            <h4 class="text-xs font-semibold uppercase text-zinc-500 mb-2">{{ __('Affective Domain') }}</h4>
                            <div class="space-y-1.5">
                                @foreach ($config->affective_traits as $trait)
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-zinc-600 dark:text-zinc-400">{{ $trait }}</span>
                                        @php $rating = $report->affective_ratings[$trait] ?? null; @endphp
                                        <span class="font-medium text-zinc-900 dark:text-white">{{ $rating ?? '—' }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Comments Section --}}
            <div class="border-t border-zinc-200 dark:border-zinc-700 px-6 py-4 space-y-3">
                <div>
                    <span class="text-xs font-semibold uppercase text-zinc-500">{{ __("Class Teacher's Comment") }}</span>
                    <p class="mt-1 text-sm text-zinc-700 dark:text-zinc-300 italic">{{ $report->teacher_comment ?? __('No comment.') }}</p>
                </div>
                <div>
                    <span class="text-xs font-semibold uppercase text-zinc-500">{{ __("Principal's Comment") }}</span>
                    <p class="mt-1 text-sm text-zinc-700 dark:text-zinc-300 italic">{{ $report->principal_comment ?? __('No comment.') }}</p>
                </div>
            </div>

            {{-- Principal Signature & School Stamp --}}
            @if ($config?->principal_signature_url || $config?->school_stamp_url)
                <div class="border-t border-zinc-200 dark:border-zinc-700 px-6 py-4 flex items-end justify-between gap-6">
                    @if ($config->principal_signature_url)
                        <div class="text-center">
                            <img src="{{ $config->principal_signature_url }}" alt="{{ __('Signature') }}" class="h-12 max-w-[150px] object-contain mx-auto">
                            <div class="mt-1 border-t border-zinc-400 dark:border-zinc-500 pt-1 text-xs font-semibold text-zinc-600 dark:text-zinc-400">{{ $config->principal_title ?? __('Principal') }}</div>
                        </div>
                    @endif
                    @if ($config->school_stamp_url)
                        <div class="text-center">
                            <img src="{{ $config->school_stamp_url }}" alt="{{ __('School Stamp') }}" class="h-14 max-w-[150px] object-contain mx-auto">
                            <div class="mt-1 text-[10px] text-zinc-500">{{ __('School Stamp') }}</div>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</x-layouts::app>
