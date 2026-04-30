<x-layouts::app :title="__('Create Grading Scale')">
    <div class="space-y-6">
        <x-admin-header :title="__('Create Grading Scale')" />

        <div class="max-w-2xl rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
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

                <div class="grid grid-cols-2 gap-4">
                    <flux:input name="name" :label="__('Scale Name')" required placeholder="e.g. Nigerian Standard" />
                    <div class="flex items-end">
                        <flux:switch name="is_default" :label="__('Set as Default')" value="1" />
                    </div>
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
