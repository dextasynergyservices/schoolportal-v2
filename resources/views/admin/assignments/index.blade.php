<x-layouts::app :title="__('Assignments')">
    <div class="space-y-6">
        <x-admin-header
            :title="__('Assignments')"
            :action="route('admin.assignments.create')"
            :actionLabel="__('Add Assignment')"
        />

        @if (session('success'))
            <flux:callout variant="success" icon="check-circle">{{ session('success') }}</flux:callout>
        @endif

        <form method="GET" action="{{ route('admin.assignments.index') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <flux:select name="class_id" placeholder="{{ __('All Classes') }}">
                    <option value="">{{ __('All Classes') }}</option>
                    @foreach ($classes as $class)
                        <option value="{{ $class->id }}" @selected(request('class_id') == $class->id)>{{ $class->name }}</option>
                    @endforeach
                </flux:select>
            </div>
            <div>
                <flux:select name="term_id" placeholder="{{ __('All Terms') }}">
                    <option value="">{{ __('All Terms') }}</option>
                    @foreach ($terms as $term)
                        <option value="{{ $term->id }}" @selected(request('term_id') == $term->id)>{{ $term->name }}</option>
                    @endforeach
                </flux:select>
            </div>
            <flux:button type="submit" variant="filled" size="sm">{{ __('Filter') }}</flux:button>
            @if (request()->hasAny(['class_id', 'term_id']))
                <flux:button variant="subtle" size="sm" href="{{ route('admin.assignments.index') }}" wire:navigate>{{ __('Clear') }}</flux:button>
            @endif
        </form>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Title') }}</flux:table.column>
                <flux:table.column>{{ __('Class') }}</flux:table.column>
                <flux:table.column>{{ __('Week') }}</flux:table.column>
                <flux:table.column>{{ __('Term') }}</flux:table.column>
                <flux:table.column>{{ __('Due Date') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column class="w-32" />
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($assignments as $assignment)
                    <flux:table.row>
                        <flux:table.cell class="font-medium">{{ $assignment->title ?? __('Week :num Assignment', ['num' => $assignment->week_number]) }}</flux:table.cell>
                        <flux:table.cell>{{ $assignment->class?->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ __('Week :num', ['num' => $assignment->week_number]) }}</flux:table.cell>
                        <flux:table.cell>{{ $assignment->term?->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $assignment->due_date?->format('M j, Y') ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            @if ($assignment->status === 'approved')
                                <flux:badge color="green" size="sm">{{ __('Approved') }}</flux:badge>
                            @elseif ($assignment->status === 'pending')
                                <flux:badge color="yellow" size="sm">{{ __('Pending') }}</flux:badge>
                            @else
                                <flux:badge color="red" size="sm">{{ __('Rejected') }}</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-1">
                                <flux:button variant="subtle" size="xs" icon="pencil-square" href="{{ route('admin.assignments.edit', $assignment) }}" wire:navigate />
                                <x-confirm-delete
                                    :action="route('admin.assignments.destroy', $assignment)"
                                    :title="__('Delete Assignment')"
                                    :message="__('Are you sure you want to delete this assignment? This action cannot be undone.')"
                                    :ariaLabel="__('Delete assignment')"
                                />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="text-center py-8">
                            {{ __('No assignments found.') }}
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        {{ $assignments->links() }}
    </div>
</x-layouts::app>
