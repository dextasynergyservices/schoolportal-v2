<div class="space-y-4">
    {{-- Level & Class row --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        {{-- Level selector --}}
        <div>
            <label for="level_id" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('Level') }} <span class="text-red-500">*</span></label>
            <select wire:model.live="levelId" id="level_id"
                class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2 text-sm text-zinc-900 dark:text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                <option value="">{{ __('Select level...') }}</option>
                @foreach ($this->levels as $level)
                    <option value="{{ $level->id }}">{{ $level->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Class selector --}}
        <div>
            <label for="class_id" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('Class') }} <span class="text-red-500">*</span></label>
            <select wire:model.live="classId" id="class_id" name="class_id"
                class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2 text-sm text-zinc-900 dark:text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                @if (! $levelId) disabled @endif>
                <option value="">{{ $levelId ? __('Select class...') : __('Select a level first') }}</option>
                @foreach ($this->classes as $class)
                    <option value="{{ $class->id }}">{{ $class->name }}</option>
                @endforeach
            </select>
            @error('class_id')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    {{-- Student searchable combobox --}}
    <div>
        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('Student') }} <span class="text-red-500">*</span></label>

        @if ($studentId && $this->selectedStudent)
            {{-- Selected student card --}}
            <div class="flex items-center justify-between rounded-lg border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-900/20 px-3 py-2.5">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="flex-shrink-0 flex items-center justify-center w-8 h-8 rounded-full bg-blue-600 text-white text-xs font-bold">
                        {{ strtoupper(mb_substr($this->selectedStudent->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-zinc-900 dark:text-white truncate">{{ $this->selectedStudent->name }}</p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 truncate">
                            {{ $this->selectedStudent->username }}
                            @if ($this->selectedStudent->studentProfile?->admission_number)
                                &middot; {{ $this->selectedStudent->studentProfile->admission_number }}
                            @endif
                        </p>
                    </div>
                </div>
                <button type="button" wire:click="clearStudent" class="flex-shrink-0 ml-2 p-1 rounded-md text-zinc-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors" title="{{ __('Change student') }}">
                    <flux:icon name="x-mark" class="size-4" />
                </button>
            </div>
            <input type="hidden" name="student_id" value="{{ $studentId }}" />

        @elseif ($classId)
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
                        placeholder="{{ __('Search by name, username, or admission number...') }}"
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
                    @forelse ($this->students as $student)
                        <button
                            type="button"
                            wire:key="student-{{ $student->id }}"
                            wire:click="selectStudent({{ $student->id }})"
                            @click="open = false"
                            class="w-full text-left px-3 py-2.5 flex items-center gap-3 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors border-b border-zinc-100 dark:border-zinc-700/50 last:border-0"
                        >
                            <div class="flex-shrink-0 flex items-center justify-center w-8 h-8 rounded-full bg-zinc-200 dark:bg-zinc-600 text-zinc-600 dark:text-zinc-300 text-xs font-bold">
                                {{ strtoupper(mb_substr($student->name, 0, 1)) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-zinc-900 dark:text-white truncate">{{ $student->name }}</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 truncate">
                                    {{ $student->username }}
                                    @if ($student->studentProfile?->admission_number)
                                        &middot; {{ $student->studentProfile->admission_number }}
                                    @endif
                                </p>
                            </div>
                        </button>
                    @empty
                        <div class="px-3 py-8 text-center">
                            <flux:icon name="magnifying-glass" class="size-6 text-zinc-300 dark:text-zinc-600 mx-auto mb-2" />
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                @if ($search)
                                    {{ __('No students match ":search"', ['search' => $search]) }}
                                @else
                                    {{ __('No students in this class') }}
                                @endif
                            </p>
                        </div>
                    @endforelse

                    @if ($this->students->count() >= 100)
                        <div class="px-3 py-2 text-center text-xs text-zinc-500 dark:text-zinc-400 border-t border-zinc-100 dark:border-zinc-700/50 bg-zinc-50 dark:bg-zinc-800/50">
                            {{ __('Showing first 100 — refine your search') }}
                        </div>
                    @endif
                </div>
            </div>
            <input type="hidden" name="student_id" value="" />

        @else
            {{-- Disabled state --}}
            <div class="w-full rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 px-3 py-2 text-sm text-zinc-500 dark:text-zinc-400 cursor-not-allowed">
                {{ __('Select a class first') }}
            </div>
            <input type="hidden" name="student_id" value="" />
        @endif

        @error('student_id')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
</div>
