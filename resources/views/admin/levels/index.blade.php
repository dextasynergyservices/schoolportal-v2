<x-layouts::app :title="__('School Levels')">
    <div class="space-y-6">
        <x-admin-header
            :title="__('School Levels')"
            :description="__('Manage school levels like Nursery, Primary, Secondary.')"
            :action="route('admin.levels.create')"
            :actionLabel="__('Add Level')"
        />

        @if (session('success'))
            <flux:callout variant="success" icon="check-circle">{{ session('success') }}</flux:callout>
        @endif
        @if (session('error'))
            <flux:callout variant="danger" icon="x-circle">{{ session('error') }}</flux:callout>
        @endif

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Name') }}</flux:table.column>
                <flux:table.column>{{ __('Classes') }}</flux:table.column>
                <flux:table.column>{{ __('Order') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column class="w-32" />
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($levels as $level)
                    <flux:table.row>
                        <flux:table.cell class="font-medium">{{ $level->name }}</flux:table.cell>
                        <flux:table.cell>{{ $level->classes_count }}</flux:table.cell>
                        <flux:table.cell>{{ $level->sort_order }}</flux:table.cell>
                        <flux:table.cell>
                            @if ($level->is_active)
                                <flux:badge color="green" size="sm">{{ __('Active') }}</flux:badge>
                            @else
                                <flux:badge color="zinc" size="sm">{{ __('Inactive') }}</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-1">
                                <flux:button variant="subtle" size="xs" icon="pencil-square" href="{{ route('admin.levels.edit', $level) }}" wire:navigate />
                                @if ($level->classes_count === 0)
                                    <x-confirm-delete
                                        :action="route('admin.levels.destroy', $level)"
                                        :title="__('Delete Level')"
                                        :message="__('Are you sure you want to delete this level? This action cannot be undone.')"
                                        :ariaLabel="__('Delete level')"
                                    />
                                @endif
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="text-center py-8">
                            {{ __('No school levels yet. Add levels like Nursery, Primary, Secondary.') }}
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>
</x-layouts::app>
