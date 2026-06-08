<x-layouts::app :title="__('Create Grading Scale')">
    <div class="space-y-6">
        <x-admin-header :title="__('Create Grading Scale')" />

        <div class="max-w-5xl rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <form method="POST" action="{{ route('admin.grading.scales.store') }}" x-data="{
                items: [
                    { grade: 'A', label: 'Excellent', min_score: 70, max_score: 100, sort_order: 1 },
                    { grade: 'B', label: 'Very Good', min_score: 60, max_score: 69, sort_order: 2 },
                    { grade: 'C', label: 'Good', min_score: 50, max_score: 59, sort_order: 3 },
                    { grade: 'D', label: 'Fair', min_score: 40, max_score: 49, sort_order: 4 },
                    { grade: 'E', label: 'Poor', min_score: 0, max_score: 39, sort_order: 5 },
                ],
                addItem() { this.items.push({ grade: '', label: '', min_score: 0, max_score: 0, sort_order: this.items.length + 1 }); },
                removeItem(i) { this.items.splice(i, 1); }
            }" class="space-y-6">
                @csrf

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:input name="name" :label="__('Scale Name')" required placeholder="e.g. Nigerian Standard" />
                    <div class="flex items-end">
                        <flux:switch name="is_default" :label="__('Make this the school default')" value="1" />
                    </div>
                </div>

                <div x-data="{ search: '' }" class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900/40">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h4 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Use for Selected Levels') }}</h4>
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                {{ __('If no level is selected, this scale will only be used when set as the school default. Levels without their own scale will use the school default scale.') }}
                            </p>
                        </div>
                        <div class="relative w-full sm:w-64">
                            <flux:icon name="magnifying-glass" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-zinc-400" />
                            <input type="search" x-model.debounce.100ms="search" placeholder="{{ __('Search levels...') }}"
                                class="w-full rounded-lg border border-zinc-200 bg-white py-2.5 pl-9 pr-3 text-sm text-zinc-900 shadow-xs outline-none transition placeholder:text-zinc-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white dark:placeholder:text-zinc-500">
                        </div>
                    </div>

                    @if ($levels->isEmpty())
                        <p class="mt-4 text-sm text-zinc-500">{{ __('No active levels found. Create levels first, then assign this scale.') }}</p>
                    @else
                        <div class="mt-4 grid grid-cols-1 gap-2 md:grid-cols-2">
                            @foreach ($levels as $level)
                                @php
                                    $assignment = $levelAssignments->get($level->id);
                                    $checked = in_array($level->id, old('level_ids', []));
                                @endphp
                                <label
                                    x-show="{{ Js::from(Str::lower($level->name)) }}.includes(search.trim().toLowerCase())"
                                    class="rounded-md border border-zinc-200 bg-white px-3 py-2 transition hover:border-indigo-200 hover:bg-indigo-50/60 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-indigo-900 dark:hover:bg-indigo-950/20">
                                    <div class="flex items-start gap-3">
                                        <input type="checkbox" name="level_ids[]" value="{{ $level->id }}" @checked($checked)
                                            class="mt-1 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500 dark:border-zinc-600 dark:bg-zinc-800">
                                        <div>
                                            <div class="text-sm font-medium text-zinc-900 dark:text-white">{{ $level->name }}</div>
                                            @if ($assignment)
                                                <p class="mt-0.5 text-xs text-amber-600 dark:text-amber-400">
                                                    {{ __('Currently using ":scale". Saving will move it to this scale.', ['scale' => $assignment->scale_name]) }}
                                                </p>
                                            @else
                                                <p class="mt-0.5 text-xs text-zinc-500">{{ __('Uses school default unless assigned.') }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div>
                    <h4 class="text-sm font-semibold text-zinc-900 dark:text-white mb-3">{{ __('Grade Items') }}</h4>
                    <div class="space-y-3">
                        <template x-for="(item, i) in items" :key="i">
                            <div class="grid grid-cols-12 gap-2 items-end">
                                <div class="col-span-2">
                                    <label class="text-xs text-zinc-500" x-show="i === 0">{{ __('Grade') }}</label>
                                    <input type="text" x-model="item.grade" :name="'items['+i+'][grade]'" class="mt-1 block w-full rounded-md border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 text-sm" placeholder="A" required>
                                </div>
                                <div class="col-span-3">
                                    <label class="text-xs text-zinc-500" x-show="i === 0">{{ __('Label') }}</label>
                                    <input type="text" x-model="item.label" :name="'items['+i+'][label]'" class="mt-1 block w-full rounded-md border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 text-sm" placeholder="Excellent" required>
                                </div>
                                <div class="col-span-2">
                                    <label class="text-xs text-zinc-500" x-show="i === 0">{{ __('Min %') }}</label>
                                    <input type="number" x-model="item.min_score" :name="'items['+i+'][min_score]'" class="mt-1 block w-full rounded-md border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 text-sm" min="0" max="100" required>
                                </div>
                                <div class="col-span-2">
                                    <label class="text-xs text-zinc-500" x-show="i === 0">{{ __('Max %') }}</label>
                                    <input type="number" x-model="item.max_score" :name="'items['+i+'][max_score]'" class="mt-1 block w-full rounded-md border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 text-sm" min="0" max="100" required>
                                </div>
                                <div class="col-span-2">
                                    <input type="hidden" :name="'items['+i+'][sort_order]'" :value="i + 1">
                                </div>
                                <div class="col-span-1">
                                    <button type="button" @click="removeItem(i)" x-show="items.length > 1" class="p-2 text-red-500 hover:text-red-700">
                                        <flux:icon name="x-mark" class="size-4" />
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                    <button type="button" @click="addItem" class="mt-3 text-sm text-indigo-600 hover:text-indigo-700 font-medium flex items-center gap-1">
                        <flux:icon name="plus" class="size-4" /> {{ __('Add Grade') }}
                    </button>
                </div>

                <div class="flex gap-3">
                    <flux:button variant="primary" type="submit">{{ __('Create Scale') }}</flux:button>
                    <flux:button variant="ghost" href="{{ route('admin.grading.index') }}" wire:navigate>{{ __('Cancel') }}</flux:button>
                </div>
            </form>
        </div>
    </div>
</x-layouts::app>
