<x-layouts::app :title="__('Score Entry')">
    <div class="space-y-6">
        <x-score-workflow-steps current="scores" />

        <x-admin-header
            :title="__('Score Entry')"
            :description="__('View, edit, and manage student scores by class and term.')"
        />

        @if (session('success'))
            <flux:callout variant="success" icon="check-circle">
                <div class="flex flex-wrap items-center justify-between gap-3 w-full">
                    <span>{{ session('success') }}</span>
                    @if ($selectedClassId && $selectedTermId)
                        <a href="{{ route('admin.scores.reports', ['class_id' => $selectedClassId, 'term_id' => $selectedTermId]) }}">
                            <flux:button variant="primary" size="sm" icon-trailing="arrow-right">{{ __('Generate Report Cards') }}</flux:button>
                        </a>
                    @endif
                </div>
            </flux:callout>
        @endif
        @if (session('error'))
            <flux:callout variant="danger" icon="x-circle">{{ session('error') }}</flux:callout>
        @endif

        {{-- Filters --}}
        <form method="GET" action="{{ route('admin.scores.index') }}" class="flex flex-wrap items-end gap-4">
            <div>
                <flux:label>{{ __('Class') }}</flux:label>
                <flux:select name="class_id" onchange="this.form.submit()">
                    <option value="">{{ __('Select Class') }}</option>
                    @foreach ($levels as $level)
                        @if ($level->classes->isNotEmpty())
                            <optgroup label="{{ $level->name }}">
                                @foreach ($level->classes as $cls)
                                    <option value="{{ $cls->id }}" @selected($selectedClassId == $cls->id)>{{ $cls->name }}</option>
                                @endforeach
                            </optgroup>
                        @endif
                    @endforeach
                </flux:select>
            </div>
            <div>
                <flux:label>{{ __('Session & Term') }}</flux:label>
                <flux:select name="term_id" onchange="this.form.submit()">
                    <option value="">{{ __('Select Term') }}</option>
                    @foreach ($sessions as $session)
                        @foreach ($session->terms as $term)
                            <option value="{{ $term->id }}" @selected($selectedTermId == $term->id)>{{ $session->name }} — {{ $term->name }}</option>
                        @endforeach
                    @endforeach
                </flux:select>
            </div>
            @if ($selectedClass && $subjects->isNotEmpty())
                <div>
                    <flux:label>{{ __('Subject (filter)') }}</flux:label>
                    <flux:select name="subject_id" onchange="this.form.submit()">
                        <option value="">{{ __('All Subjects') }}</option>
                        @foreach ($subjects as $subject)
                            <option value="{{ $subject->id }}" @selected($selectedSubjectId == $subject->id)>{{ $subject->name }}</option>
                        @endforeach
                    </flux:select>
                </div>
            @endif
        </form>

        {{-- Setup required callout --}}
        @if ($selectedClassId && $selectedTermId && $components->isEmpty())
            <flux:callout variant="warning" icon="exclamation-triangle">
                <div class="flex flex-wrap items-center justify-between gap-3 w-full">
                    <span>{{ __('No score components configured for this class. Set them up in Grading Setup first.') }}</span>
                    <a href="{{ route('admin.grading.index', ['tab' => 'components']) }}">
                        <flux:button variant="primary" size="sm" icon-trailing="arrow-right">{{ __('Go to Grading Setup') }}</flux:button>
                    </a>
                </div>
            </flux:callout>
        @endif

        {{-- Score Grid --}}
        @if ($grid)
            <div x-data="scoreGrid()" class="space-y-4">
                {{-- Actions Bar --}}
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2 text-sm text-zinc-500">
                        <flux:icon name="users" class="size-4" />
                        <span>{{ count($grid['students']) }} {{ __('students') }}</span>
                        <span class="mx-1">·</span>
                        <span>{{ $subjects->count() }} {{ __('subjects') }}</span>
                        <span class="mx-1">·</span>
                        <span>{{ $components->count() }} {{ __('components') }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('admin.scores.export', ['class_id' => $selectedClassId, 'term_id' => $selectedTermId]) }}">
                            <flux:button variant="subtle" size="sm" icon="arrow-down-tray">{{ __('Export CSV') }}</flux:button>
                        </a>
                        <form method="POST" action="{{ route('admin.scores.lock') }}">
                            @csrf
                            <input type="hidden" name="class_id" value="{{ $selectedClassId }}">
                            <input type="hidden" name="term_id" value="{{ $selectedTermId }}">
                            <flux:button variant="subtle" size="sm" icon="lock-closed" type="submit">{{ __('Lock Scores') }}</flux:button>
                        </form>
                        <div x-data="generateReportModal()" x-init="init()">
                            <flux:button variant="primary" size="sm" icon="document-text" @click="openModal()">{{ __('Generate Reports') }}</flux:button>

                            <div x-show="showModal" x-cloak @keydown.escape.window="showModal = false" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                                <div @click.outside="showModal = false" class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl dark:bg-zinc-800 max-h-[90vh] overflow-y-auto">
                                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Generate Report Cards') }}</h3>
                                    <p class="mt-1 text-sm text-zinc-500">{{ __('Choose class, scope, and report type.') }}</p>

                                    <form method="POST" action="{{ route('admin.scores.generate-reports') }}" class="mt-4 space-y-4">
                                        @csrf

                                        {{-- Class selection grouped by level --}}
                                        <div>
                                            <flux:label>{{ __('Class') }}</flux:label>
                                            <flux:select name="class_id" x-model="selectedClassId" x-on:change="onClassChange()">
                                                <option value="">{{ __('Select a class...') }}</option>
                                                @foreach ($levels as $level)
                                                    @if ($level->classes->isNotEmpty())
                                                        <optgroup label="{{ $level->name }}">
                                                            @foreach ($level->classes as $cls)
                                                                <option value="{{ $cls->id }}" @selected($selectedClassId == $cls->id)>{{ $cls->name }}</option>
                                                            @endforeach
                                                        </optgroup>
                                                    @endif
                                                @endforeach
                                            </flux:select>
                                        </div>

                                        {{-- Generation scope --}}
                                        <div x-show="selectedClassId">
                                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">{{ __('Generate For') }}</label>
                                            <div class="flex gap-3">
                                                <label class="flex-1 flex items-center gap-2 rounded-lg border border-zinc-200 dark:border-zinc-700 p-3 cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-700/50" :class="scope === 'bulk' && 'border-indigo-500 bg-indigo-50/50 dark:bg-indigo-900/20'">
                                                    <input type="radio" name="generation_scope" value="bulk" x-model="scope" class="text-indigo-600">
                                                    <div>
                                                        <div class="text-sm font-medium text-zinc-900 dark:text-white">{{ __('All Students') }}</div>
                                                        <div class="text-xs text-zinc-500">{{ __('Bulk generate for entire class') }}</div>
                                                    </div>
                                                </label>
                                                <label class="flex-1 flex items-center gap-2 rounded-lg border border-zinc-200 dark:border-zinc-700 p-3 cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-700/50" :class="scope === 'single' && 'border-indigo-500 bg-indigo-50/50 dark:bg-indigo-900/20'">
                                                    <input type="radio" name="generation_scope" value="single" x-model="scope" class="text-indigo-600">
                                                    <div>
                                                        <div class="text-sm font-medium text-zinc-900 dark:text-white">{{ __('Single Student') }}</div>
                                                        <div class="text-xs text-zinc-500">{{ __('Generate for one student') }}</div>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>

                                        {{-- Student picker --}}
                                        <div x-show="scope === 'single' && selectedClassId" x-cloak>
                                            <flux:label>{{ __('Student') }}</flux:label>
                                            <div class="relative">
                                                <flux:input type="text" x-model="studentSearch" x-on:input="filterStudents()" placeholder="{{ __('Search student...') }}" />
                                                <div x-show="loadingStudents" class="absolute right-3 top-2.5">
                                                    <flux:icon name="arrow-path" class="size-4 text-zinc-400 animate-spin" />
                                                </div>
                                            </div>
                                            <input type="hidden" name="student_id" :value="selectedStudentId">
                                            <div x-show="filteredStudents.length > 0 && !selectedStudentId" class="mt-1 max-h-40 overflow-y-auto rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800">
                                                <template x-for="student in filteredStudents" :key="student.id">
                                                    <button type="button" @click="selectStudent(student)" class="w-full px-3 py-2 text-left text-sm hover:bg-zinc-50 dark:hover:bg-zinc-700/50 flex items-center justify-between">
                                                        <span x-text="student.name" class="font-medium text-zinc-900 dark:text-white"></span>
                                                        <span x-text="student.admission_number" class="text-xs text-zinc-400"></span>
                                                    </button>
                                                </template>
                                            </div>
                                            <div x-show="selectedStudentId" class="mt-2 flex items-center gap-2 rounded-lg bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-700 px-3 py-2">
                                                <flux:icon name="user" class="size-4 text-indigo-500" />
                                                <span class="text-sm font-medium text-indigo-700 dark:text-indigo-300" x-text="selectedStudentName"></span>
                                                <button type="button" @click="clearStudent()" class="ml-auto text-indigo-400 hover:text-indigo-600">
                                                    <flux:icon name="x-mark" class="size-4" />
                                                </button>
                                            </div>
                                        </div>

                                        {{-- Report type --}}
                                        <div x-show="selectedClassId">
                                            <fieldset class="space-y-2">
                                                <legend class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Report Type') }}</legend>
                                                @if (in_array('midterm', $enabledReportTypes))
                                                    <label class="flex items-center gap-3 rounded-lg border border-zinc-200 dark:border-zinc-700 p-3 cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-700/50" :class="reportType === 'midterm' && 'border-indigo-500 bg-indigo-50/50 dark:bg-indigo-900/20'">
                                                        <input type="radio" name="report_type" value="midterm" x-model="reportType" class="text-indigo-600">
                                                        <div>
                                                            <div class="text-sm font-medium text-zinc-900 dark:text-white">{{ __('Mid-Term Report') }}</div>
                                                            <div class="text-xs text-zinc-500">{{ __('Based on current term scores only') }}</div>
                                                        </div>
                                                    </label>
                                                @endif
                                                @if (in_array('full_term', $enabledReportTypes))
                                                    <label class="flex items-center gap-3 rounded-lg border border-zinc-200 dark:border-zinc-700 p-3 cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-700/50" :class="reportType === 'full_term' && 'border-indigo-500 bg-indigo-50/50 dark:bg-indigo-900/20'">
                                                        <input type="radio" name="report_type" value="full_term" x-model="reportType" class="text-indigo-600">
                                                        <div>
                                                            <div class="text-sm font-medium text-zinc-900 dark:text-white">{{ __('Full Term Report') }}</div>
                                                            <div class="text-xs text-zinc-500">{{ __('Complete term report with all components') }}</div>
                                                        </div>
                                                    </label>
                                                @endif
                                                @if (in_array('session', $enabledReportTypes))
                                                    <label class="flex items-center gap-3 rounded-lg border border-zinc-200 dark:border-zinc-700 p-3 cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-700/50" :class="reportType === 'session' && 'border-indigo-500 bg-indigo-50/50 dark:bg-indigo-900/20'">
                                                        <input type="radio" name="report_type" value="session" x-model="reportType" class="text-indigo-600">
                                                        <div>
                                                            <div class="text-sm font-medium text-zinc-900 dark:text-white">{{ __('Session Report') }}</div>
                                                            <div class="text-xs text-zinc-500">{{ __('Aggregated report across all terms in a session') }}</div>
                                                        </div>
                                                    </label>
                                                @endif
                                            </fieldset>
                                        </div>

                                        {{-- Term (hidden for session reports) --}}
                                        <div x-show="selectedClassId && reportType !== 'session'">
                                            <input type="hidden" name="term_id" value="{{ $selectedTermId }}">
                                            <p class="text-sm text-zinc-500"><span class="font-medium">{{ __('Term:') }}</span> {{ $selectedTerm?->name ?? 'Current' }}</p>
                                        </div>

                                        {{-- Session selector (for session reports) --}}
                                        <div x-show="selectedClassId && reportType === 'session'" x-cloak>
                                            <flux:label>{{ __('Session') }}</flux:label>
                                            <flux:select name="session_id">
                                                @foreach ($sessions as $session)
                                                    <option value="{{ $session->id }}" @selected($session->is_current)>{{ $session->name }}</option>
                                                @endforeach
                                            </flux:select>
                                        </div>

                                        <div class="flex justify-end gap-2 pt-2">
                                            <flux:button variant="subtle" size="sm" @click="showModal = false" type="button">{{ __('Cancel') }}</flux:button>
                                            <flux:button variant="primary" size="sm" icon="document-text" type="submit" x-bind:disabled="!selectedClassId || (scope === 'single' && !selectedStudentId)">{{ __('Generate') }}</flux:button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <flux:button variant="subtle" size="sm" icon="pencil-square" href="{{ route('admin.scores.reports.bulk-edit-data', ['class_id' => $selectedClassId, 'term_id' => $selectedTermId]) }}" wire:navigate>
                            {{ __('Report Data Entry') }}
                        </flux:button>
                        <flux:button variant="subtle" size="sm" icon="document-chart-bar" href="{{ route('admin.scores.reports', ['class_id' => $selectedClassId, 'term_id' => $selectedTermId]) }}" wire:navigate>
                            {{ __('View Report Cards') }}
                        </flux:button>
                    </div>
                </div>

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
                        {{ __('Locked') }}
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
                            if ($selectedSubjectId && $selectedSubjectId != $subject->id) continue;
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
                    <form method="POST" action="{{ route('admin.scores.save') }}" id="scoreForm">
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
                                        @if (!$selectedSubjectId || $selectedSubjectId == $subject->id)
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
                                        @endif
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                                @forelse ($grid['students'] as $studentData)
                                    <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50">
                                        <td class="sticky left-0 z-10 bg-white dark:bg-zinc-900 px-3 py-2 font-medium text-zinc-900 dark:text-white border-r border-zinc-200 dark:border-zinc-700">
                                            <div class="truncate max-w-[170px]">{{ $studentData['student_name'] }}</div>
                                            <div class="text-[10px] text-zinc-400">{{ $studentData['admission_number'] ?? '' }}</div>
                                        </td>
                                        @foreach ($grid['subjects'] as $subject)
                                            @if (!$selectedSubjectId || $selectedSubjectId == $subject->id)
                                                @php $subjectScores = $studentData['subjects'][$subject->id] ?? []; @endphp
                                                @foreach ($components as $comp)
                                                    @php
                                                        $compData = $subjectScores['components'][$comp->id] ?? null;
                                                        $score = $compData['score'] ?? '';
                                                        $isLocked = $compData['is_locked'] ?? false;
                                                        $source = $compData['source_type'] ?? 'manual';
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
                                                    @if (isset($subjectScores['weighted_total']))
                                                        <span class="text-indigo-600 dark:text-indigo-400">{{ number_format($subjectScores['weighted_total'], 1) }}%</span>
                                                        @if (isset($subjectScores['grade']))
                                                            <div class="text-[10px] text-zinc-500">{{ $subjectScores['grade'] }}</div>
                                                        @endif
                                                    @else
                                                        <span class="text-zinc-300">—</span>
                                                    @endif
                                                </td>
                                            @endif
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

                {{-- Save Button (shows when form is dirty) --}}
                <div x-show="dirty" x-transition class="flex justify-end">
                    <flux:button variant="primary" icon="check" form="scoreForm" type="submit">{{ __('Save Changes') }}</flux:button>
                </div>
            </div>
        @elseif ($selectedClassId)
            <div class="rounded-lg border border-dashed border-zinc-300 dark:border-zinc-600 p-8 text-center">
                <flux:icon name="calculator" class="mx-auto size-10 text-zinc-400 mb-3" />
                <p class="text-sm text-zinc-500">{{ __('No score data found. Scores are automatically populated when students complete CBT exams.') }}</p>
            </div>
        @else
            <div class="rounded-lg border border-dashed border-zinc-300 dark:border-zinc-600 p-8 text-center">
                <flux:icon name="funnel" class="mx-auto size-10 text-zinc-400 mb-3" />
                <p class="text-sm text-zinc-500">{{ __('Select a class and term to view scores.') }}</p>
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

        function generateReportModal() {
            const classStudentsBaseUrl = '{{ route('admin.scores.class-students', ['class' => '__CLASS_ID__'], false) }}'.replace('__CLASS_ID__', '');

            return {
                showModal: false,
                selectedClassId: '{{ $selectedClassId ?? '' }}',
                reportType: 'full_term',
                scope: 'bulk',
                students: [],
                filteredStudents: [],
                studentSearch: '',
                selectedStudentId: '',
                selectedStudentName: '',
                loadingStudents: false,

                init() {},

                openModal() {
                    this.showModal = true;
                    if (this.selectedClassId && this.students.length === 0) {
                        this.fetchStudents();
                    }
                },

                async onClassChange() {
                    this.clearStudent();
                    this.students = [];
                    this.filteredStudents = [];
                    if (this.selectedClassId) {
                        await this.fetchStudents();
                    }
                },

                async fetchStudents() {
                    this.loadingStudents = true;
                    try {
                        const response = await fetch(classStudentsBaseUrl + this.selectedClassId);
                        this.students = await response.json();
                        this.filteredStudents = this.students;
                    } catch (e) {
                        this.students = [];
                    }
                    this.loadingStudents = false;
                },

                filterStudents() {
                    const q = this.studentSearch.toLowerCase();
                    this.filteredStudents = this.students.filter(s =>
                        s.name.toLowerCase().includes(q) ||
                        (s.admission_number && s.admission_number.toLowerCase().includes(q))
                    );
                    this.selectedStudentId = '';
                    this.selectedStudentName = '';
                },

                selectStudent(student) {
                    this.selectedStudentId = student.id;
                    this.selectedStudentName = student.name;
                    this.studentSearch = '';
                    this.filteredStudents = [];
                },

                clearStudent() {
                    this.selectedStudentId = '';
                    this.selectedStudentName = '';
                    this.studentSearch = '';
                    this.filteredStudents = this.students;
                }
            };
        }
    </script>
    @endpush
</x-layouts::app>
