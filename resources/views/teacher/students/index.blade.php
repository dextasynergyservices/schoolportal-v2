<x-layouts::app :title="__('My Students')">
    <div class="space-y-6">
        <x-admin-header :title="__('My Students')">
            <flux:button variant="subtle" size="sm" icon="arrow-down-tray" href="{{ route('teacher.students.export', request()->query()) }}">
                {{ __('Export CSV') }}
            </flux:button>
        </x-admin-header>

        <form method="GET" action="{{ route('teacher.students.index') }}" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 max-w-sm">
                <flux:input name="search" :value="request('search')" placeholder="{{ __('Search by name or username...') }}" icon="magnifying-glass" />
            </div>
            <div>
                <flux:select name="class_id">
                    @foreach ($classes as $class)
                        <option value="{{ $class->id }}" @selected($selectedClassId == $class->id)>
                            {{ $class->name }} ({{ $class->students_count }})
                        </option>
                    @endforeach
                </flux:select>
            </div>
            <flux:button type="submit" variant="filled" size="sm">{{ __('Filter') }}</flux:button>
            @if (request()->has('search'))
                <flux:button variant="subtle" size="sm" href="{{ route('teacher.students.index', ['class_id' => $selectedClassId]) }}" wire:navigate>{{ __('Clear') }}</flux:button>
            @endif
        </form>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Name') }}</flux:table.column>
                <flux:table.column>{{ __('Username') }}</flux:table.column>
                <flux:table.column class="hidden sm:table-cell">{{ __('Gender') }}</flux:table.column>
                <flux:table.column class="hidden md:table-cell">{{ __('Class') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($students as $student)
                    <flux:table.row>
                        <flux:table.cell>
                            <div class="flex items-center gap-3">
                                <flux:avatar size="sm" :src="$student->avatar_url" :name="$student->name" :initials="$student->initials()" />
                                <span class="font-medium">{{ $student->name }}</span>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell class="text-zinc-500">{{ $student->username }}</flux:table.cell>
                        <flux:table.cell class="hidden sm:table-cell capitalize">{{ $student->gender ?? '—' }}</flux:table.cell>
                        <flux:table.cell class="hidden md:table-cell">{{ $student->studentProfile?->class?->name ?? '—' }}</flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="4" class="text-center py-8">
                            {{ __('No students found in this class.') }}
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        {{ $students->links() }}
    </div>
</x-layouts::app>
