<x-layouts::app :title="__('Add Subject')">
    <div class="space-y-6">
        <x-admin-header :title="__('Add Subject')" />

        <div class="max-w-xl rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <form method="POST" action="{{ route('admin.subjects.store') }}" class="space-y-6">
                @csrf
                <flux:input name="name" :label="__('Subject Name')" :value="old('name')" placeholder="e.g. Mathematics, English Language" required />

                <div class="grid grid-cols-2 gap-4">
                    <flux:input name="short_name" :label="__('Short Name')" :value="old('short_name')" placeholder="e.g. MATH, ENG" />
                    <flux:input name="category" :label="__('Category')" :value="old('category')" placeholder="e.g. Science, Arts" />
                </div>

                <flux:input name="sort_order" :label="__('Sort Order')" :value="old('sort_order', 0)" type="number" min="0" />

                @if($classes->isNotEmpty())
                    <div
                        x-data="{
                            search: '',
                            open: false,
                            selected: @js(old('class_ids', [])).map(Number),
                            classes: @js($classes->map(fn($c) => ['id' => $c->id, 'label' => ($c->level?->name ?? '') . ' — ' . $c->name])),
                            get filtered() {
                                if (!this.search) return this.classes;
                                const q = this.search.toLowerCase();
                                return this.classes.filter(c => c.label.toLowerCase().includes(q));
                            },
                            toggle(id) {
                                const idx = this.selected.indexOf(id);
                                if (idx === -1) { this.selected.push(id); } else { this.selected.splice(idx, 1); }
                            },
                            isSelected(id) { return this.selected.includes(id); },
                            get selectedCount() { return this.selected.length; }
                        }"
                        class="relative"
                    >
                        <flux:label class="mb-1.5">{{ __('Assign to Classes') }}</flux:label>

                        {{-- Trigger button --}}
                        <button
                            type="button"
                            @click="open = !open"
                            class="flex w-full items-center justify-between rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm shadow-xs dark:border-zinc-600 dark:bg-zinc-700"
                        >
                            <span x-show="selectedCount === 0" class="text-zinc-400 dark:text-zinc-500">{{ __('Select classes...') }}</span>
                            <span x-show="selectedCount > 0" class="text-zinc-700 dark:text-zinc-200" x-text="selectedCount + ' class' + (selectedCount === 1 ? '' : 'es') + ' selected'"></span>
                            <svg class="size-4 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                        </button>

                        {{-- Dropdown panel --}}
                        <div
                            x-show="open"
                            @click.outside="open = false"
                            x-transition.origin.top
                            class="absolute z-50 mt-1 w-full rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-600 dark:bg-zinc-700"
                        >
                            {{-- Search input --}}
                            <div class="border-b border-zinc-200 p-2 dark:border-zinc-600">
                                <input
                                    type="text"
                                    x-model="search"
                                    x-ref="searchInput"
                                    placeholder="{{ __('Search classes...') }}"
                                    class="w-full rounded-md border-0 bg-zinc-50 px-2.5 py-1.5 text-sm text-zinc-700 placeholder:text-zinc-400 focus:outline-none focus:ring-0 dark:bg-zinc-800 dark:text-zinc-200 dark:placeholder:text-zinc-500"
                                />
                            </div>

                            {{-- Options --}}
                            <div class="max-h-52 overflow-y-auto p-1">
                                <template x-for="cls in filtered" :key="cls.id">
                                    <label
                                        @click="toggle(cls.id)"
                                        class="flex cursor-pointer items-center gap-2 rounded-md px-2.5 py-1.5 text-sm transition hover:bg-zinc-100 dark:hover:bg-zinc-600"
                                    >
                                        <span
                                            class="flex size-4 shrink-0 items-center justify-center rounded border transition"
                                            :class="isSelected(cls.id) ? 'border-zinc-800 bg-zinc-800 text-white dark:border-zinc-300 dark:bg-zinc-300 dark:text-zinc-800' : 'border-zinc-300 dark:border-zinc-500'"
                                        >
                                            <svg x-show="isSelected(cls.id)" class="size-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                        </span>
                                        <span class="text-zinc-700 dark:text-zinc-200" x-text="cls.label"></span>
                                    </label>
                                </template>
                                <div x-show="filtered.length === 0" class="px-2.5 py-2 text-sm text-zinc-400 dark:text-zinc-500">
                                    {{ __('No classes found.') }}
                                </div>
                            </div>
                        </div>

                        {{-- Hidden inputs for form submission --}}
                        <template x-for="id in selected" :key="id">
                            <input type="hidden" name="class_ids[]" :value="id" />
                        </template>

                        <flux:description class="mt-1">{{ __('Optional — you can also assign classes later from the Subjects page.') }}</flux:description>
                        @error('class_ids')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                <div class="flex gap-3">
                    <flux:button variant="primary" type="submit">{{ __('Add Subject') }}</flux:button>
                    <flux:button variant="ghost" href="{{ route('admin.subjects.index') }}" wire:navigate>{{ __('Cancel') }}</flux:button>
                </div>
            </form>
        </div>
    </div>
</x-layouts::app>
