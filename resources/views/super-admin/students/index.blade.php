<x-layouts::app :title="__('All Students')">
    <div class="space-y-6">
        <x-admin-header
            :title="__('All Students')"
            :description="__('Students across all schools on the platform.')"
            :action="route('super-admin.students.create')"
            :actionLabel="__('Add Student')"
        />

        @if (session('success'))
            <flux:callout variant="success" icon="check-circle">{{ session('success') }}</flux:callout>
        @endif

        {{-- Filters --}}
        <form method="GET" action="{{ route('super-admin.students.index') }}" class="flex flex-wrap items-end gap-3">
            <div class="min-w-48 flex-1">
                <flux:input
                    name="search"
                    :value="request('search')"
                    placeholder="{{ __('Search by name or username...') }}"
                    icon="magnifying-glass"
                    aria-label="{{ __('Search students') }}"
                />
            </div>
            <div class="w-full sm:w-48">
                <label for="school-filter" class="sr-only">{{ __('Filter by school') }}</label>
                <select
                    id="school-filter"
                    name="school_id"
                    onchange="this.form.submit()"
                    class="block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-[var(--color-primary)] focus:ring-[var(--color-primary)] dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
                >
                    <option value="">{{ __('All Schools') }}</option>
                    @foreach ($schools as $school)
                        <option value="{{ $school->id }}" @selected(request('school_id') == $school->id)>
                            {{ $school->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @if ($levels->isNotEmpty())
                <div class="w-full sm:w-40">
                    <label for="level-filter" class="sr-only">{{ __('Filter by level') }}</label>
                    <select
                        id="level-filter"
                        name="level_id"
                        onchange="this.form.submit()"
                        class="block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-[var(--color-primary)] focus:ring-[var(--color-primary)] dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
                    >
                        <option value="">{{ __('All Levels') }}</option>
                        @foreach ($levels as $level)
                            <option value="{{ $level->id }}" @selected(request('level_id') == $level->id)>
                                {{ $level->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
            @if ($classes->isNotEmpty())
                <div class="w-full sm:w-40">
                    <label for="class-filter" class="sr-only">{{ __('Filter by class') }}</label>
                    <select
                        id="class-filter"
                        name="class_id"
                        class="block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-[var(--color-primary)] focus:ring-[var(--color-primary)] dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
                    >
                        <option value="">{{ __('All Classes') }}</option>
                        @foreach ($classes as $class)
                            <option value="{{ $class->id }}" @selected(request('class_id') == $class->id)>
                                {{ $class->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
            <flux:button type="submit" variant="filled" size="sm">{{ __('Filter') }}</flux:button>
            @if (request()->hasAny(['search', 'school_id', 'level_id', 'class_id']))
                <flux:button variant="subtle" size="sm" href="{{ route('super-admin.students.index') }}" wire:navigate>
                    {{ __('Clear') }}
                </flux:button>
            @endif
        </form>

        {{-- Students table --}}
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Student') }}</flux:table.column>
                <flux:table.column>{{ __('School') }}</flux:table.column>
                <flux:table.column class="hidden md:table-cell">{{ __('Class') }}</flux:table.column>
                <flux:table.column class="hidden sm:table-cell">{{ __('Level') }}</flux:table.column>
                <flux:table.column class="hidden lg:table-cell">{{ __('Gender') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($students as $student)
                    <flux:table.row>
                        <flux:table.cell>
                            <div class="flex items-center gap-3">
                                <flux:avatar size="sm" :src="$student->avatar_url" :name="$student->name" />
                                <div class="min-w-0">
                                    <div class="truncate font-medium text-zinc-900 dark:text-white">{{ $student->name }}</div>
                                    <flux:text size="xs" class="text-zinc-500">{{ $student->username }}</flux:text>
                                </div>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <a href="{{ route('super-admin.schools.show', $student->school_id) }}" wire:navigate class="text-sm text-[var(--color-primary)] hover:underline">
                                {{ $student->school?->name ?? '—' }}
                            </a>
                        </flux:table.cell>
                        <flux:table.cell class="hidden md:table-cell">
                            {{ $student->studentProfile?->class?->name ?? '—' }}
                        </flux:table.cell>
                        <flux:table.cell class="hidden sm:table-cell">
                            {{ $student->level?->name ?? '—' }}
                        </flux:table.cell>
                        <flux:table.cell class="hidden lg:table-cell">
                            {{ $student->gender ? ucfirst($student->gender) : '—' }}
                        </flux:table.cell>
                        <flux:table.cell>
                            @if ($student->is_active)
                                <flux:badge color="green" size="sm">{{ __('Active') }}</flux:badge>
                            @else
                                <flux:badge color="zinc" size="sm">{{ __('Inactive') }}</flux:badge>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="text-center">
                            <div class="py-8">
                                <flux:icon.academic-cap class="mx-auto size-8 text-zinc-400" />
                                <flux:text class="mt-2 text-zinc-500">
                                    {{ request()->hasAny(['search', 'school_id']) ? __('No students match your filters.') : __('No students found. Select a school or add a student to get started.') }}
                                </flux:text>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        {{ $students->links() }}
    </div>
</x-layouts::app>
