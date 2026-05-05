<x-layouts::app :title="__('Report Card — :name', ['name' => $report->student->name ?? 'Student'])">
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ __('Student Report Card') }}</h1>
                <p class="text-sm text-zinc-500">{{ ($report->student->name ?? 'Student') . ' — ' . ($report->session->name ?? '') . ' ' . ($report->term->name ?? '') }}</p>
            </div>
            <div class="flex items-center gap-2">
                @if (!in_array($report->status, ['approved', 'published']))
                    <flux:button variant="subtle" size="sm" icon="pencil-square" href="{{ route('teacher.scores.reports.edit-data', $report) }}" wire:navigate>
                        {{ __('Edit Report Data') }}
                    </flux:button>
                @endif
                <flux:button variant="subtle" size="sm" icon="arrow-left" href="{{ route('teacher.scores.reports', ['class_id' => $report->class_id, 'term_id' => $report->term_id]) }}" wire:navigate>
                    {{ __('Back') }}
                </flux:button>
            </div>
        </div>

        {{-- Status Bar --}}
        <div class="flex items-center gap-3 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-4 py-3">
            <span class="text-sm text-zinc-500">{{ __('Status:') }}</span>
            @php
                $statusColor = match($report->status) {
                    'draft' => 'zinc',
                    'pending_approval' => 'amber',
                    'approved' => 'blue',
                    'published' => 'green',
                    default => 'zinc',
                };
                $overallGradeItem = $gradingScale?->items->first(fn ($item) => $item->min_score <= ($report->average_weighted_score ?? 0) && $item->max_score >= ($report->average_weighted_score ?? 0));
            @endphp
            <flux:badge size="sm" :color="$statusColor">{{ ucfirst(str_replace('_', ' ', $report->status)) }}</flux:badge>
            @if ($config?->show_position && $report->position)
                <span class="text-sm text-zinc-500">{{ __('Position:') }}</span>
                <span class="font-semibold">{{ $report->position }} / {{ $report->out_of }}</span>
            @endif
            @if ($report->average_weighted_score)
                <span class="text-sm text-zinc-500">{{ __('Average:') }}</span>
                <span class="font-semibold text-indigo-600 dark:text-indigo-400">{{ number_format($report->average_weighted_score, 1) }}%</span>
            @endif
            @if ($overallGradeItem)
                <span class="text-sm text-zinc-500">{{ __('Overall Grade:') }}</span>
                <span class="font-semibold text-emerald-600 dark:text-emerald-400">{{ $overallGradeItem->grade }} — {{ $overallGradeItem->label }}</span>
            @endif
        </div>

        {{-- Report Content --}}
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 overflow-hidden shadow-sm">
            {{-- School Header --}}
            <div class="bg-gradient-to-r from-indigo-600 to-blue-600 px-6 py-5 text-white text-center">
                @if ($school->logo_url)
                    <img src="{{ $school->logoSmallUrl() }}" alt="" class="mx-auto mb-2 h-16 w-16 rounded-full object-cover border-2 border-white/30">
                @endif
                <h2 class="text-xl font-bold uppercase">{{ $school->name }}</h2>
                <p class="text-sm text-white/80">{{ $school->address ?? '' }}</p>
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
                    <span class="text-zinc-400">{{ __('Term') }}</span>
                    <div class="font-medium text-zinc-900 dark:text-white">{{ $report->session->name ?? '' }} — {{ $report->term->name ?? '' }}</div>
                </div>
            </div>

            {{-- Subject Scores --}}
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
                                    <th class="px-3 py-3 text-center">{{ $comp['short_name'] ?? $comp['name'] ?? '' }} ({{ $comp['max_score'] ?? '' }})</th>
                                @endforeach
                                <th class="px-3 py-3 text-center bg-indigo-50/50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400">{{ __('Total') }}</th>
                                <th class="px-3 py-3 text-center">{{ __('Grade') }}</th>
                                @if ($config?->show_position)
                                    <th class="px-3 py-3 text-center">{{ __('Position') }}</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                            @foreach ($report->subject_scores_snapshot as $snapshot)
                                @php
                                    $compLookup = collect($snapshot['components'] ?? [])->keyBy($compKeyFn);
                                @endphp
                                <tr>
                                    <td class="px-4 py-2.5 font-medium text-zinc-900 dark:text-white">{{ $snapshot['subject_name'] ?? '—' }}</td>
                                    @foreach ($masterComponents as $comp)
                                        @php $ck = $compKeyFn($comp); @endphp
                                        <td class="px-3 py-2.5 text-center text-zinc-600 dark:text-zinc-400">{{ $compLookup[$ck]['score'] ?? '—' }}</td>
                                    @endforeach
                                    <td class="px-3 py-2.5 text-center font-semibold text-indigo-600 dark:text-indigo-400 bg-indigo-50/30 dark:bg-indigo-900/10">
                                        {{ isset($snapshot['weighted_total']) ? number_format($snapshot['weighted_total'], 1) : '—' }}
                                    </td>
                                    <td class="px-3 py-2.5 text-center font-bold">{{ $snapshot['grade'] ?? '—' }}</td>
                                    @if ($config?->show_position)
                                        <td class="px-3 py-2.5 text-center text-zinc-500">{{ $snapshot['position'] ?? '—' }}</td>
                                    @endif
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

            {{-- Comments --}}
            <div class="border-t border-zinc-200 dark:border-zinc-700 px-6 py-4 space-y-3">
                <div>
                    <span class="text-xs font-semibold uppercase text-zinc-500">{{ __("Class Teacher's Comment") }}</span>
                    <p class="mt-1 text-sm text-zinc-700 dark:text-zinc-300 italic">{{ $report->teacher_comment ?? __('No comment yet.') }}</p>
                </div>
                @if ($report->principal_comment)
                    <div>
                        <span class="text-xs font-semibold uppercase text-zinc-500">{{ __("Principal's Comment") }}</span>
                        <p class="mt-1 text-sm text-zinc-700 dark:text-zinc-300 italic">{{ $report->principal_comment }}</p>
                    </div>
                @endif
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

        {{-- Add Comment (if draft) --}}
        @if ($report->status === 'draft')
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6 space-y-4">
                <h3 class="font-semibold text-zinc-900 dark:text-white">{{ __('Add Your Comment & Submit') }}</h3>
                <form method="POST" action="{{ route('teacher.scores.reports.comment', $report) }}" class="space-y-3">
                    @csrf
                    <flux:textarea name="teacher_comment" rows="3" :placeholder="__('Write your comment about this student\'s performance...')">{{ $report->teacher_comment }}</flux:textarea>
                    <flux:button variant="primary" type="submit" icon="paper-airplane">{{ __('Submit for Approval') }}</flux:button>
                </form>
            </div>
        @endif
    </div>
</x-layouts::app>
