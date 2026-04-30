<x-layouts::app :title="__('Edit Report Data — :name', ['name' => $report->student->name ?? 'Student'])">
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ __('Report Data Entry') }}</h1>
                <p class="text-sm text-zinc-500">{{ ($report->student->name ?? 'Student') . ' — ' . ($report->session->name ?? '') . ' ' . ($report->term->name ?? '') }}</p>
            </div>
            <flux:button variant="subtle" size="sm" icon="arrow-left" href="{{ route('admin.scores.reports', ['class_id' => $report->class_id, 'term_id' => $report->term_id]) }}" wire:navigate>
                {{ __('Back') }}
            </flux:button>
        </div>

        @if (session('success'))
            <flux:callout variant="success" icon="check-circle">{{ session('success') }}</flux:callout>
        @endif
        @if (session('error'))
            <flux:callout variant="danger" icon="x-circle">{{ session('error') }}</flux:callout>
        @endif

        <form method="POST" action="{{ route('admin.scores.reports.save-data', $report) }}" class="space-y-6">
            @csrf

            {{-- Attendance --}}
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">{{ __('Attendance') }}</h2>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <flux:label>{{ __('Days Present') }}</flux:label>
                        <flux:input type="number" name="attendance_present" min="0" :value="old('attendance_present', $report->attendance_present)" />
                        @error('attendance_present') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <flux:label>{{ __('Days Absent') }}</flux:label>
                        <flux:input type="number" name="attendance_absent" min="0" :value="old('attendance_absent', $report->attendance_absent)" />
                        @error('attendance_absent') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <flux:label>{{ __('Total School Days') }}</flux:label>
                        <flux:input type="number" name="attendance_total" min="0" :value="old('attendance_total', $report->attendance_total)" />
                        @error('attendance_total') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- Psychomotor Traits --}}
            @if ($config && $config->psychomotor_traits && count($config->psychomotor_traits) > 0)
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-1">{{ __('Psychomotor Development') }}</h2>
                    <p class="text-sm text-zinc-500 mb-4">
                        {{ __('Rate each trait using the scale.') }}
                        @if ($config->trait_rating_scale)
                            @foreach ($config->trait_rating_scale as $scale)
                                <span class="inline-block ml-1"><strong>{{ $scale['value'] }}</strong> = {{ $scale['label'] }}</span>{{ !$loop->last ? ',' : '' }}
                            @endforeach
                        @endif
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach ($config->psychomotor_traits as $trait)
                            <div>
                                <flux:label>{{ $trait }}</flux:label>
                                <flux:select name="psychomotor[{{ $trait }}]">
                                    <option value="">{{ __('—') }}</option>
                                    @if ($config->trait_rating_scale)
                                        @foreach ($config->trait_rating_scale as $scale)
                                            <option value="{{ $scale['value'] }}" @selected(old("psychomotor.{$trait}", $report->psychomotor_ratings[$trait] ?? '') == $scale['value'])>
                                                {{ $scale['value'] }} — {{ $scale['label'] }}
                                            </option>
                                        @endforeach
                                    @endif
                                </flux:select>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Affective Traits --}}
            @if ($config && $config->affective_traits && count($config->affective_traits) > 0)
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-1">{{ __('Affective (Behavioural) Development') }}</h2>
                    <p class="text-sm text-zinc-500 mb-4">
                        {{ __('Rate each trait using the scale.') }}
                        @if ($config->trait_rating_scale)
                            @foreach ($config->trait_rating_scale as $scale)
                                <span class="inline-block ml-1"><strong>{{ $scale['value'] }}</strong> = {{ $scale['label'] }}</span>{{ !$loop->last ? ',' : '' }}
                            @endforeach
                        @endif
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach ($config->affective_traits as $trait)
                            <div>
                                <flux:label>{{ $trait }}</flux:label>
                                <flux:select name="affective[{{ $trait }}]">
                                    <option value="">{{ __('—') }}</option>
                                    @if ($config->trait_rating_scale)
                                        @foreach ($config->trait_rating_scale as $scale)
                                            <option value="{{ $scale['value'] }}" @selected(old("affective.{$trait}", $report->affective_ratings[$trait] ?? '') == $scale['value'])>
                                                {{ $scale['value'] }} — {{ $scale['label'] }}
                                            </option>
                                        @endforeach
                                    @endif
                                </flux:select>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Teacher Comment --}}
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">{{ __("Class Teacher's Comment") }}</h2>

                @if ($config && $config->comment_presets && count($config->comment_presets) > 0)
                    <div class="mb-3">
                        <flux:label>{{ __('Quick Presets') }}</flux:label>
                        <flux:select x-data x-on:change="if ($event.target.value) { $refs.commentBox.value = $event.target.value; $event.target.value = ''; }">
                            <option value="">{{ __('Select a preset…') }}</option>
                            @foreach ($config->comment_presets as $category => $presets)
                                <optgroup label="{{ ucfirst($category) }}">
                                    @foreach ((array) $presets as $preset)
                                        <option value="{{ $preset }}">{{ Str::limit($preset, 60) }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </flux:select>
                    </div>
                @endif

                <flux:textarea x-ref="commentBox" name="teacher_comment" rows="3" :placeholder="__('Enter comment about this student…')">{{ old('teacher_comment', $report->teacher_comment) }}</flux:textarea>
                @error('teacher_comment') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end">
                <flux:button variant="primary" type="submit" icon="check">{{ __('Save Report Data') }}</flux:button>
            </div>
        </form>
    </div>
</x-layouts::app>
