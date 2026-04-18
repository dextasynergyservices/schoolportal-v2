<x-layouts::app :title="__('Classes')">
    <div class="space-y-6">
        <x-admin-header
            :title="__('Classes')"
            :description="__('Manage classes and assign teachers.')"
            :action="route('admin.classes.create')"
            :actionLabel="__('Add Class')"
        />

        @if (session('success'))
            <flux:callout variant="success" icon="check-circle">{{ session('success') }}</flux:callout>
        @endif
        @if (session('error'))
            <flux:callout variant="danger" icon="x-circle">{{ session('error') }}</flux:callout>
        @endif

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Class') }}</flux:table.column>
                <flux:table.column>{{ __('Level') }}</flux:table.column>
                <flux:table.column>{{ __('Teacher') }}</flux:table.column>
                <flux:table.column>{{ __('Students') }}</flux:table.column>
                <flux:table.column>{{ __('Capacity') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column class="w-32" />
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($classes as $class)
                    <flux:table.row>
                        <flux:table.cell class="font-medium">{{ $class->name }}</flux:table.cell>
                        <flux:table.cell>{{ $class->level?->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $class->teacher?->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $class->students_count }}</flux:table.cell>
                        <flux:table.cell>{{ $class->capacity ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            @if ($class->is_active)
                                <flux:badge color="green" size="sm">{{ __('Active') }}</flux:badge>
                            @else
                                <flux:badge color="zinc" size="sm">{{ __('Inactive') }}</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-1">
                                <flux:button variant="subtle" size="xs" icon="pencil-square" href="{{ route('admin.classes.edit', $class) }}" wire:navigate aria-label="{{ __('Edit :name', ['name' => $class->name]) }}" />
                                @if ($class->students_count === 0)
                                    <x-confirm-delete
                                        :action="route('admin.classes.destroy', $class)"
                                        :title="__('Delete Class')"
                                        :message="__('Are you sure you want to delete this class? This action cannot be undone.')"
                                        :ariaLabel="__('Delete :name', ['name' => $class->name])"
                                    />
                                @endif
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="text-center py-8">
                            {{ __('No classes yet. Create school levels first, then add classes.') }}
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        {{ $classes->links() }}
    </div>
</x-layouts::app>
