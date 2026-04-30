<x-layouts::app :title="__('Bulk Report Data Entry')">
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ __('Bulk Report Data Entry') }}</h1>
                <p class="text-sm text-zinc-500">{{ __('Enter attendance, psychomotor, affective ratings and comments for all students at once.') }}</p>
            </div>
        </div>

        @if (session('success'))
            <flux:callout variant="success" icon="check-circle">{{ session('success') }}</flux:callout>
        @endif
        @if (session('error'))
            <flux:callout variant="danger" icon="x-circle">{{ session('error') }}</flux:callout>
        @endif

        {{-- Filters --}}
        <form method="GET" action="{{ route('admin.scores.reports.bulk-edit-data') }}" class="flex flex-wrap items-end gap-4">
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
        </form>

        @if ($selectedClassId && $selectedTermId)
            @if ($reports->isEmpty())
                <flux:callout variant="warning" icon="information-circle">
                    {{ __('No editable reports found for this class/term. Reports that are already approved or published cannot be edited here.') }}
                </flux:callout>
            @else
                @php
                    $psychomotorTraits = $config?->psychomotor_traits ?? [];
                    $affectiveTraits = $config?->affective_traits ?? [];
                    $ratingScale = $config?->trait_rating_scale ?? [];
                @endphp

                <form method="POST" action="{{ route('admin.scores.reports.bulk-save-data') }}">
                    @csrf
                    <input type="hidden" name="class_id" value="{{ $selectedClassId }}">
                    <input type="hidden" name="term_id" value="{{ $selectedTermId }}">

                    {{-- Legend --}}
                    @if (count($ratingScale) > 0)
                        <div class="flex flex-wrap gap-3 text-sm text-zinc-500 mb-4">
                            <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ __('Rating Scale:') }}</span>
                            @foreach ($ratingScale as $scale)
                                <span><strong>{{ $scale['value'] }}</strong> = {{ $scale['label'] }}</span>
                            @endforeach
                        </div>
                    @endif

                    <div class="space-y-4">
                        @foreach ($reports as $report)
                            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-4 shadow-sm">
                                <div class="flex items-center gap-3 mb-3 pb-3 border-b border-zinc-100 dark:border-zinc-800">
                                    <span class="inline-flex items-center justify-center size-7 rounded-full bg-zinc-100 dark:bg-zinc-700 text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ $report->position ?? '—' }}</span>
                                    <span class="font-semibold text-zinc-900 dark:text-white">{{ $report->student->name ?? 'Unknown' }}</span>
                                    @php
                                        $statusColor = match($report->status) {
                                            'draft' => 'zinc',
                                            'pending_approval' => 'amber',
                                            default => 'zinc',
                                        };
                                    @endphp
                                    <flux:badge size="sm" :color="$statusColor">{{ ucfirst(str_replace('_', ' ', $report->status)) }}</flux:badge>
                                </div>

                                {{-- Attendance row --}}
                                <div class="grid grid-cols-3 gap-3 mb-3">
                                    <div>
                                        <flux:label class="text-xs">{{ __('Present') }}</flux:label>
                                        <flux:input type="number" name="reports[{{ $report->id }}][attendance_present]" min="0" size="sm" :value="$report->attendance_present" />
                                    </div>
                                    <div>
                                        <flux:label class="text-xs">{{ __('Absent') }}</flux:label>
                                        <flux:input type="number" name="reports[{{ $report->id }}][attendance_absent]" min="0" size="sm" :value="$report->attendance_absent" />
                                    </div>
                                    <div>
                                        <flux:label class="text-xs">{{ __('Total Days') }}</flux:label>
                                        <flux:input type="number" name="reports[{{ $report->id }}][attendance_total]" min="0" size="sm" :value="$report->attendance_total" />
                                    </div>
                                </div>

                                {{-- Psychomotor traits --}}
                                @if (count($psychomotorTraits) > 0)
                                    <div class="mb-3">
                                        <p class="text-xs font-medium text-zinc-500 mb-2">{{ __('Psychomotor') }}</p>
                                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2">
                                            @foreach ($psychomotorTraits as $trait)
                                                <div>
                                                    <flux:label class="text-xs truncate" :title="$trait">{{ $trait }}</flux:label>
                                                    <flux:select name="reports[{{ $report->id }}][psychomotor][{{ $trait }}]" size="sm">
                                                        <option value="">—</option>
                                                        @foreach ($ratingScale as $scale)
                                                            <option value="{{ $scale['value'] }}" @selected(($report->psychomotor_ratings[$trait] ?? '') == $scale['value'])>{{ $scale['value'] }}</option>
                                                        @endforeach
                                                    </flux:select>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                {{-- Affective traits --}}
                                @if (count($affectiveTraits) > 0)
                                    <div class="mb-3">
                                        <p class="text-xs font-medium text-zinc-500 mb-2">{{ __('Affective') }}</p>
                                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2">
                                            @foreach ($affectiveTraits as $trait)
                                                <div>
                                                    <flux:label class="text-xs truncate" :title="$trait">{{ $trait }}</flux:label>
                                                    <flux:select name="reports[{{ $report->id }}][affective][{{ $trait }}]" size="sm">
                                                        <option value="">—</option>
                                                        @foreach ($ratingScale as $scale)
                                                            <option value="{{ $scale['value'] }}" @selected(($report->affective_ratings[$trait] ?? '') == $scale['value'])>{{ $scale['value'] }}</option>
                                                        @endforeach
                                                    </flux:select>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                {{-- Comment --}}
                                <div>
                                    <flux:label class="text-xs">{{ __('Comment') }}</flux:label>
                                    <flux:textarea name="reports[{{ $report->id }}][teacher_comment]" rows="2" size="sm" :placeholder="__('Comment…')">{{ $report->teacher_comment }}</flux:textarea>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="flex justify-end mt-6">
                        <flux:button variant="primary" type="submit" icon="check">{{ __('Save All Report Data') }}</flux:button>
                    </div>
                </form>
            @endif
        @endif
    </div>
</x-layouts::app>
