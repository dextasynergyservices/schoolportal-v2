<x-layouts::app :title="__('Manage Subjects')">
    <div
        class="mx-auto max-w-4xl space-y-6"
        x-data="{ mode: @js(old('subject_id') ? 'assign' : 'create') }"
    >
        <x-admin-header
            :title="__('Manage Subjects')"
            :description="__('Create a new school subject or assign an existing subject to your classes.')"
        />

        @if ($errors->any())
            <flux:callout variant="danger" icon="exclamation-circle">
                {{ $errors->first() }}
            </flux:callout>
        @endif

        <div class="inline-flex rounded-lg border border-zinc-200 bg-zinc-100 p-1 dark:border-zinc-700 dark:bg-zinc-800">
            <button
                type="button"
                class="rounded-md px-4 py-2 text-sm font-medium transition"
                :class="mode === 'create' ? 'bg-white text-zinc-900 shadow-sm dark:bg-zinc-700 dark:text-white' : 'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200'"
                x-on:click="mode = 'create'"
            >
                {{ __('Create New Subject') }}
            </button>
            <button
                type="button"
                class="rounded-md px-4 py-2 text-sm font-medium transition"
                :class="mode === 'assign' ? 'bg-white text-zinc-900 shadow-sm dark:bg-zinc-700 dark:text-white' : 'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200'"
                x-on:click="mode = 'assign'"
            >
                {{ __('Assign Existing Subject') }}
            </button>
        </div>

        <flux:card x-show="mode === 'create'" x-cloak>
            <form method="POST" action="{{ route('teacher.subjects.store') }}" class="space-y-6">
                @csrf

                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:input name="name" :label="__('Subject Name')" :value="old('name')" required />
                    <flux:input name="short_name" :label="__('Short Name')" :value="old('short_name')" />
                    <flux:input name="category" :label="__('Category')" :value="old('category')" />
                </div>

                <div>
                    <flux:label>{{ __('Assign New Subject to Classes') }}</flux:label>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('You can only select classes assigned to you.') }}</p>
                    <div class="mt-3 grid gap-2 sm:grid-cols-2">
                        @forelse ($classes as $class)
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-zinc-200 p-3 transition hover:border-zinc-400 dark:border-zinc-700 dark:hover:border-zinc-500">
                                <input type="checkbox" name="class_ids[]" value="{{ $class->id }}" @checked(in_array($class->id, old('class_ids', [])))>
                                <span class="text-sm font-medium">{{ $class->name }} <span class="text-zinc-500">({{ $class->level?->name }})</span></span>
                            </label>
                        @empty
                            <p class="text-sm text-zinc-500">{{ __('No classes are assigned to you.') }}</p>
                        @endforelse
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <flux:button href="{{ route('teacher.subjects.index') }}" variant="ghost">{{ __('Cancel') }}</flux:button>
                    <flux:button type="submit" variant="primary" icon="plus">{{ __('Create Subject') }}</flux:button>
                </div>
            </form>
        </flux:card>

        <flux:card
            x-show="mode === 'assign'"
            x-cloak
        >
            <form
                method="POST"
                action="{{ route('teacher.subjects.assign') }}"
                class="space-y-6"
                x-data="{
                    search: '',
                    selectedSubject: @js((int) old('subject_id')),
                    matches(text) {
                        return !this.search.trim() || String(text || '').toLowerCase().includes(this.search.trim().toLowerCase());
                    },
                    get visibleCount() {
                        return Array.from(this.$refs.subjectList?.querySelectorAll('[data-subject-option]') ?? [])
                            .filter(option => this.matches(option.dataset.search))
                            .length;
                    }
                }"
            >
                @csrf
                <input type="hidden" name="subject_id" x-bind:value="selectedSubject">

                <div>
                    <flux:label>{{ __('School Subject Pool') }}</flux:label>
                    <div class="relative mt-2">
                        <flux:icon name="magnifying-glass" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-zinc-400" />
                        <input
                            type="search"
                            x-model.debounce.100ms="search"
                            placeholder="{{ __('Search subjects...') }}"
                            class="w-full rounded-lg border border-zinc-200 bg-white py-2.5 pl-9 pr-3 text-sm text-zinc-900 shadow-xs outline-none transition placeholder:text-zinc-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white dark:placeholder:text-zinc-500"
                            autocomplete="off"
                        />
                    </div>

                    <div class="mt-3 max-h-72 space-y-2 overflow-y-auto pr-1" x-ref="subjectList">
                        @foreach ($subjects as $subject)
                            <button
                                type="button"
                                data-subject-option
                                data-search="{{ mb_strtolower($subject->name.' '.$subject->short_name) }}"
                                class="flex w-full items-center justify-between gap-3 rounded-lg border p-3 text-left transition"
                                x-show="matches($el.dataset.search)"
                                x-bind:class="selectedSubject === {{ $subject->id }} ? 'border-indigo-500 bg-indigo-50 dark:border-indigo-400 dark:bg-indigo-950/40' : 'border-zinc-200 hover:border-zinc-400 dark:border-zinc-700 dark:hover:border-zinc-500'"
                                x-on:click="selectedSubject = {{ $subject->id }}"
                            >
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-semibold text-zinc-900 dark:text-white">{{ $subject->name }}</span>
                                    <span class="block truncate text-xs text-zinc-500">
                                        @if ($subject->classes->isNotEmpty())
                                            {{ __('Already in: :classes', ['classes' => $subject->classes->pluck('name')->join(', ')]) }}
                                        @else
                                            {{ __('Not yet assigned to your classes') }}
                                        @endif
                                    </span>
                                </span>
                                <flux:icon name="check-circle" class="size-5 shrink-0 text-indigo-600" x-show="selectedSubject === {{ $subject->id }}" />
                            </button>
                        @endforeach
                        <p x-show="visibleCount === 0" class="py-6 text-center text-sm text-zinc-500">{{ __('No subjects match your search.') }}</p>
                    </div>
                </div>

                <div>
                    <flux:label>{{ __('Assign to Classes') }}</flux:label>
                    <div class="mt-3 grid gap-2 sm:grid-cols-2">
                        @forelse ($classes as $class)
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-zinc-200 p-3 transition hover:border-zinc-400 dark:border-zinc-700 dark:hover:border-zinc-500">
                                <input type="checkbox" name="class_ids[]" value="{{ $class->id }}" @checked(in_array($class->id, old('class_ids', [])))>
                                <span class="text-sm font-medium">{{ $class->name }} <span class="text-zinc-500">({{ $class->level?->name }})</span></span>
                            </label>
                        @empty
                            <p class="text-sm text-zinc-500">{{ __('No classes are assigned to you.') }}</p>
                        @endforelse
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <flux:button href="{{ route('teacher.subjects.index') }}" variant="ghost">{{ __('Cancel') }}</flux:button>
                    <flux:button type="submit" variant="primary" icon="link" x-bind:disabled="!selectedSubject">{{ __('Assign Subject') }}</flux:button>
                </div>
            </form>
        </flux:card>
    </div>
</x-layouts::app>
