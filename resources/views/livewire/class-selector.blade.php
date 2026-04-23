<div class="space-y-4">
    {{-- Level filter (optional) --}}
    <div>
        <label for="level_id" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('Level') }}</label>
        <select wire:model.live="levelId" id="level_id"
            class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2 text-sm text-zinc-900 dark:text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
            <option value="">{{ __('All levels') }}</option>
            @foreach ($this->levels as $level)
                <option value="{{ $level->id }}">{{ $level->name }}</option>
            @endforeach
        </select>
    </div>

    {{-- Class searchable combobox --}}
    <div>
        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('Class') }} <span class="text-red-500">*</span></label>

        @if ($classId && $this->selectedClass)
            {{-- Selected class card --}}
            <div class="flex items-center justify-between rounded-lg border border-purple-200 dark:border-purple-800 bg-purple-50 dark:bg-purple-900/20 px-3 py-2.5">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="flex-shrink-0 flex items-center justify-center w-8 h-8 rounded-full bg-purple-600 text-white">
                        <flux:icon name="academic-cap" class="size-4" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-zinc-900 dark:text-white truncate">{{ $this->selectedClass->name }}</p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $this->selectedClass->level?->name }}</p>
                    </div>
                </div>
                <button type="button" wire:click="clearClass" class="flex-shrink-0 ml-2 p-1 rounded-md text-zinc-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors" title="{{ __('Change class') }}">
                    <flux:icon name="x-mark" class="size-4" />
                </button>
            </div>
            <input type="hidden" name="class_id" value="{{ $classId }}" />

        @else
            {{-- Search combobox --}}
            <div x-data="{ open: false }" @click.outside="open = false" class="relative">
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <flux:icon name="magnifying-glass" class="size-4 text-zinc-400" />
                    </div>
                    <input
                        wire:model.live.debounce.300ms="search"
                        @focus="open = true"
                        @click="open = true"
                        @keydown.escape="open = false"
                        type="text"
                        autocomplete="off"
                        placeholder="{{ __('Search classes...') }}"
                        class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 pl-9 pr-9 py-2 text-sm text-zinc-900 dark:text-white placeholder-zinc-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                    />
                    @if ($search !== '')
                        <button type="button" wire:click="$set('search', '')" class="absolute inset-y-0 right-0 flex items-center pr-3 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
                            <flux:icon name="x-mark" class="size-4" />
                        </button>
                    @endif
                </div>

                {{-- Dropdown results --}}
                <div
                    x-show="open"
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 -translate-y-1"
                    x-cloak
                    class="absolute z-30 mt-1 w-full rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 shadow-lg max-h-64 overflow-y-auto"
                >
                    @forelse ($this->classes as $class)
                        <button
                            type="button"
                            wire:key="class-{{ $class->id }}"
                            wire:click="selectClass({{ $class->id }})"
                            @click="open = false"
                            class="w-full text-left px-3 py-2.5 flex items-center gap-3 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors border-b border-zinc-100 dark:border-zinc-700/50 last:border-0"
                        >
                            <div class="flex-shrink-0 flex items-center justify-center w-8 h-8 rounded-full bg-zinc-200 dark:bg-zinc-600 text-zinc-600 dark:text-zinc-300">
                                <flux:icon name="academic-cap" class="size-4" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-zinc-900 dark:text-white truncate">{{ $class->name }}</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $class->level?->name }}</p>
                            </div>
                        </button>
                    @empty
                        <div class="px-3 py-8 text-center">
                            <flux:icon name="academic-cap" class="size-6 text-zinc-300 dark:text-zinc-600 mx-auto mb-2" />
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                @if ($search)
                                    {{ __('No classes match ":search"', ['search' => $search]) }}
                                @else
                                    {{ __('No classes available') }}
                                @endif
                            </p>
                        </div>
                    @endforelse
                </div>
            </div>
            <input type="hidden" name="class_id" value="" />
        @endif

        @error('class_id')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
</div>
