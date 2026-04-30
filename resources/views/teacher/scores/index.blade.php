<x-layouts::app :title="__('Score Entry')">
    <div class="space-y-6" x-data="scoreGrid()">
        <x-score-workflow-steps current="scores" />

        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ __('Score Entry') }}</h1>
                <p class="text-sm text-zinc-500">{{ __('Enter and manage student scores for your assigned classes.') }}</p>
            </div>
            @if ($selectedClassId && $selectedTermId)
                <a href="{{ route('teacher.scores.export', ['class_id' => $selectedClassId, 'term_id' => $selectedTermId]) }}">
                    <flux:button variant="subtle" size="sm" icon="arrow-down-tray">{{ __('Export CSV') }}</flux:button>
                </a>
            @endif
        </div>

        @if (session('success'))
            <flux:callout variant="success" icon="check-circle">{{ session('success') }}</flux:callout>
        @endif
        @if (session('error'))
            <flux:callout variant="danger" icon="x-circle">{{ session('error') }}</flux:callout>
        @endif

        {{-- Filters --}}
        <form method="GET" action="{{ route('teacher.scores.index') }}" class="flex flex-wrap items-end gap-4">
            <div>
                <flux:label>{{ __('Class') }}</flux:label>
                <flux:select name="class_id" onchange="this.form.submit()">
                    <option value="">{{ __('Select Class') }}</option>
                    @foreach ($classes as $class)
                        <option value="{{ $class->id }}" @selected($selectedClassId == $class->id)>{{ $class->name }}</option>
                    @endforeach
                </flux:select>
            </div>
            <div>
                <flux:label>{{ __('Term') }}</flux:label>
                <flux:select name="term_id" onchange="this.form.submit()">
                    <option value="">{{ __('Select Term') }}</option>
                    @foreach ($sessions as $session)
                        @foreach ($session->terms as $term)
                            <option value="{{ $term->id }}" @selected($selectedTermId == $term->id)>{{ $session->name }} — {{ $term->name }}</option>
                        @endforeach
                    @endforeach
                </flux:select>
            </div>
            @if ($selectedClassId && $selectedTermId)
                <flux:button variant="subtle" size="sm" icon="document-text" href="{{ route('teacher.scores.reports', ['class_id' => $selectedClassId, 'term_id' => $selectedTermId]) }}" wire:navigate>
                    {{ __('View Reports') }}
                </flux:button>
                <flux:button variant="subtle" size="sm" icon="pencil-square" href="{{ route('teacher.scores.reports.bulk-edit-data', ['class_id' => $selectedClassId, 'term_id' => $selectedTermId]) }}" wire:navigate>
                    {{ __('Report Data Entry') }}
                </flux:button>
            @endif
        </form>

        {{-- Setup required callout --}}
        @if ($selectedClassId && $selectedTermId && $components->isEmpty())
            <flux:callout variant="warning" icon="exclamation-triangle">
                {{ __('No score components are configured yet. Please contact your school admin to set up grading components before scores can be entered.') }}
            </flux:callout>
        @endif

        {{-- Score Grid (Editable) --}}
        @if ($grid)
            {{-- Source Legend --}}
            <div class="flex flex-wrap items-center gap-4 text-xs text-zinc-500">
                <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ __('Legend:') }}</span>
                <span class="inline-flex items-center gap-1">
                    <flux:icon name="computer-desktop" class="size-3.5 text-blue-500" />
                    {{ __('CBT (auto-graded)') }}
                </span>
                <span class="inline-flex items-center gap-1">
                    <flux:icon name="pencil" class="size-3.5 text-amber-500" />
                    {{ __('Manual entry') }}
                </span>
                <span class="inline-flex items-center gap-1">
                    <flux:icon name="lock-closed" class="size-3.5 text-zinc-400" />
                    {{ __('Locked (read-only)') }}
                </span>
                <span class="inline-flex items-center gap-1">
                    <span class="inline-block size-3 rounded border-2 border-dashed border-zinc-300"></span>
                    {{ __('Missing') }}
                </span>
            </div>

            {{-- Completion Summary --}}
            @php
                $totalCells = 0;
                $filledCells = 0;
                $cbtCells = 0;
                $manualCells = 0;
                $lockedCells = 0;
                foreach ($grid['students'] as $studentData) {
                    foreach ($grid['subjects'] as $subject) {
                        $subjectScores = $studentData['subjects'][$subject->id] ?? [];
                        foreach ($components as $comp) {
                            $totalCells++;
                            $compData = $subjectScores['components'][$comp->id] ?? null;
                            if ($compData && $compData['score'] !== null) {
                                $filledCells++;
                                if (($compData['source_type'] ?? '') === 'cbt') $cbtCells++;
                                else $manualCells++;
                                if ($compData['is_locked'] ?? false) $lockedCells++;
                            }
                        }
                    }
                }
                $fillPercent = $totalCells > 0 ? round(($filledCells / $totalCells) * 100) : 0;
                $missingCells = $totalCells - $filledCells;
            @endphp

            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-4">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">{{ __('Score Completion') }}</h3>
                    <span class="text-sm font-bold {{ $fillPercent === 100 ? 'text-emerald-600' : ($fillPercent >= 50 ? 'text-amber-600' : 'text-red-500') }}">{{ $fillPercent }}%</span>
                </div>
                <div class="w-full bg-zinc-100 dark:bg-zinc-800 rounded-full h-2 mb-3">
                    <div class="h-2 rounded-full transition-all duration-500 {{ $fillPercent === 100 ? 'bg-emerald-500' : ($fillPercent >= 50 ? 'bg-amber-500' : 'bg-red-400') }}" style="width: {{ $fillPercent }}%"></div>
                </div>
                <div class="flex flex-wrap gap-x-6 gap-y-1 text-xs text-zinc-500">
                    <span>{{ __(':count filled', ['count' => $filledCells]) }} / {{ $totalCells }}</span>
                    @if ($cbtCells > 0)
                        <span class="inline-flex items-center gap-1"><flux:icon name="computer-desktop" class="size-3 text-blue-500" /> {{ $cbtCells }} {{ __('CBT') }}</span>
                    @endif
                    @if ($manualCells > 0)
                        <span class="inline-flex items-center gap-1"><flux:icon name="pencil" class="size-3 text-amber-500" /> {{ $manualCells }} {{ __('manual') }}</span>
                    @endif
                    @if ($missingCells > 0)
                        <span class="text-red-500 font-medium">{{ $missingCells }} {{ __('missing') }}</span>
                    @endif
                    @if ($lockedCells > 0)
                        <span class="inline-flex items-center gap-1"><flux:icon name="lock-closed" class="size-3 text-zinc-400" /> {{ $lockedCells }} {{ __('locked') }}</span>
                    @endif
                </div>
            </div>

            {{-- Grid Table --}}
            <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                <form method="POST" action="{{ route('teacher.scores.save') }}" id="scoreForm">
                    @csrf
                    <input type="hidden" name="class_id" value="{{ $selectedClassId }}">
                    <input type="hidden" name="term_id" value="{{ $selectedTermId }}">

                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-zinc-50 dark:bg-zinc-800">
                                <th class="sticky left-0 z-10 bg-zinc-50 dark:bg-zinc-800 px-3 py-2 text-left text-xs font-medium uppercase text-zinc-500 border-b border-zinc-200 dark:border-zinc-700 min-w-[180px]">
                                    {{ __('Student') }}
                                </th>
                                @foreach ($grid['subjects'] as $subject)
                                    @foreach ($components as $comp)
                                        <th class="px-2 py-2 text-center text-xs font-medium uppercase text-zinc-500 border-b border-l border-zinc-200 dark:border-zinc-700 min-w-[70px]" title="{{ $subject->name }} - {{ $comp->name }}">
                                            <div class="truncate max-w-[80px]">{{ $subject->short_name ?? mb_substr($subject->name, 0, 3) }}</div>
                                            <div class="text-[10px] text-zinc-400">{{ $comp->short_name }} ({{ $comp->max_score }})</div>
                                        </th>
                                    @endforeach
                                    <th class="px-2 py-2 text-center text-xs font-semibold uppercase text-indigo-600 dark:text-indigo-400 border-b border-l border-zinc-200 dark:border-zinc-700 bg-indigo-50/50 dark:bg-indigo-900/20 min-w-[60px]" title="{{ $subject->name }} Total">
                                        <div class="truncate max-w-[60px]">{{ $subject->short_name ?? mb_substr($subject->name, 0, 3) }}</div>
                                        <div class="text-[10px]">{{ __('Total') }}</div>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700 bg-white dark:bg-zinc-900">
                            @forelse ($grid['students'] as $studentData)
                                <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50">
                                    <td class="sticky left-0 z-10 bg-white dark:bg-zinc-900 px-3 py-2 font-medium text-zinc-900 dark:text-white border-r border-zinc-200 dark:border-zinc-700">
                                        <div class="truncate max-w-[170px]">{{ $studentData['student_name'] }}</div>
                                        <div class="text-[10px] text-zinc-400">{{ $studentData['admission_number'] ?? '' }}</div>
                                    </td>
                                    @foreach ($grid['subjects'] as $subject)
                                        @php $subjectScores = $studentData['subjects'][$subject->id] ?? []; @endphp
                                        @foreach ($components as $comp)
                                            @php
                                                $compData = $subjectScores['components'][$comp->id] ?? null;
                                                $score = $compData['score'] ?? '';
                                                $isLocked = $compData['is_locked'] ?? false;
                                                $source = $compData['source_type'] ?? null;
                                            @endphp
                                            <td class="px-1 py-1 text-center border-l border-zinc-100 dark:border-zinc-700">
                                                @if ($isLocked)
                                                    <span class="inline-flex items-center gap-1 text-zinc-500">
                                                        {{ $score }}
                                                        @if ($source === 'cbt')
                                                            <flux:icon name="computer-desktop" class="size-3 text-blue-400" />
                                                        @elseif ($source === 'manual')
                                                            <flux:icon name="pencil" class="size-3 text-amber-400" />
                                                        @endif
                                                        <flux:icon name="lock-closed" class="size-3 text-zinc-300" />
                                                    </span>
                                                @else
                                                    <div class="relative">
                                                        <input type="number"
                                                            name="scores[{{ $studentData['student_id'] }}][{{ $subject->id }}][{{ $comp->id }}]"
                                                            value="{{ $score }}"
                                                            min="0" max="{{ $comp->max_score }}" step="0.01"
                                                            class="w-16 rounded border px-1.5 py-1 text-center text-xs focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:text-white {{ $source === 'cbt' ? 'border-blue-300 bg-blue-50 dark:bg-blue-900/20 dark:border-blue-700' : ($source === 'manual' ? 'border-amber-300 bg-amber-50/50 dark:bg-amber-900/10 dark:border-amber-700' : 'border-zinc-200 dark:border-zinc-600 bg-transparent') }}"
                                                            @change="markDirty()"
                                                        >
                                                        @if ($source === 'cbt')
                                                            <flux:icon name="computer-desktop" class="absolute -top-1 -right-1 size-3 text-blue-500" />
                                                        @elseif ($source === 'manual')
                                                            <flux:icon name="pencil" class="absolute -top-1 -right-1 size-3 text-amber-500" />
                                                        @endif
                                                    </div>
                                                @endif
                                            </td>
                                        @endforeach
                                        <td class="px-2 py-1 text-center font-semibold border-l border-zinc-200 dark:border-zinc-700 bg-indigo-50/30 dark:bg-indigo-900/10">
                                            @if (isset($subjectScores['weighted_total']) && $subjectScores['weighted_total'] > 0)
                                                <span class="text-indigo-600 dark:text-indigo-400">{{ number_format($subjectScores['weighted_total'], 1) }}%</span>
                                                @if (isset($subjectScores['grade']))
                                                    <div class="text-[10px] text-zinc-500">{{ $subjectScores['grade'] }}</div>
                                                @endif
                                            @else
                                                <span class="text-zinc-300">—</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="100" class="px-4 py-8 text-center text-zinc-400">{{ __('No students in this class.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </form>
            </div>

            {{-- Save Button --}}
            <div x-show="dirty" x-transition class="flex justify-end">
                <flux:button variant="primary" icon="check" form="scoreForm" type="submit">{{ __('Save Changes') }}</flux:button>
            </div>
        @elseif ($selectedClassId)
            <div class="rounded-lg border border-dashed border-zinc-300 dark:border-zinc-600 p-8 text-center">
                <flux:icon name="calculator" class="mx-auto size-10 text-zinc-400 mb-3" />
                <p class="text-sm text-zinc-500">{{ __('No scores found yet for this class and term.') }}</p>
            </div>
        @else
            <div class="rounded-lg border border-dashed border-zinc-300 dark:border-zinc-600 p-8 text-center">
                <flux:icon name="funnel" class="mx-auto size-10 text-zinc-400 mb-3" />
                <p class="text-sm text-zinc-500">{{ __('Select a class to view scores.') }}</p>
            </div>
        @endif
    </div>

    @push('scripts')
    <script>
        function scoreGrid() {
            return {
                dirty: false,
                markDirty() { this.dirty = true; }
            };
        }
    </script>
    @endpush
</x-layouts::app>
