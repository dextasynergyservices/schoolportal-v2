<x-layouts::app :title="__('Parents')">
    <div class="space-y-6">
        <x-admin-header
            :title="__('Parents')"
            :action="route('admin.parents.create')"
            :actionLabel="__('Add Parent')"
        />

        @if (session('success'))
            <flux:callout variant="success" icon="check-circle">{{ session('success') }}</flux:callout>
        @endif

        <form method="GET" action="{{ route('admin.parents.index') }}" class="flex items-end gap-3">
            <div class="flex-1 max-w-sm">
                <flux:input name="search" :value="request('search')" placeholder="{{ __('Search name or username...') }}" icon="magnifying-glass" aria-label="{{ __('Search parents') }}" />
            </div>
            <flux:button type="submit" variant="filled" size="sm">{{ __('Search') }}</flux:button>
        </form>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Name') }}</flux:table.column>
                <flux:table.column>{{ __('Username') }}</flux:table.column>
                <flux:table.column>{{ __('Phone') }}</flux:table.column>
                <flux:table.column>{{ __('Relationship') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column class="w-32" />
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($parents as $parent)
                    <flux:table.row>
                        <flux:table.cell class="font-medium">{{ $parent->name }}</flux:table.cell>
                        <flux:table.cell class="text-zinc-500">{{ $parent->username }}</flux:table.cell>
                        <flux:table.cell>{{ $parent->phone ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $parent->parentProfile?->relationship ? ucfirst($parent->parentProfile->relationship) : '—' }}</flux:table.cell>
                        <flux:table.cell>
                            @if ($parent->is_active)
                                <flux:badge color="green" size="sm">{{ __('Active') }}</flux:badge>
                            @else
                                <flux:badge color="zinc" size="sm">{{ __('Inactive') }}</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-1">
                                <flux:button variant="subtle" size="xs" icon="pencil-square" href="{{ route('admin.parents.edit', $parent) }}" wire:navigate aria-label="{{ __('Edit :name', ['name' => $parent->name]) }}" />
                                @if ($parent->is_active)
                                    <flux:modal.trigger :name="'deactivate-parent-' . $parent->id">
                                        <flux:button variant="subtle" size="xs" icon="pause-circle" aria-label="{{ __('Deactivate') }}" />
                                    </flux:modal.trigger>
                                @else
                                    <form method="POST" action="{{ route('admin.parents.activate', $parent) }}" class="inline">
                                        @csrf
                                        <flux:button type="submit" variant="subtle" size="xs" icon="play-circle" aria-label="{{ __('Activate') }}" />
                                    </form>
                                @endif
                            </div>

                            {{-- Deactivate modal --}}
                            @if ($parent->is_active)
                                <flux:modal :name="'deactivate-parent-' . $parent->id" class="max-w-md">
                                    <form method="POST" action="{{ route('admin.parents.deactivate', $parent) }}" class="space-y-4">
                                        @csrf
                                        <div>
                                            <flux:heading size="lg">{{ __('Deactivate :name', ['name' => $parent->name]) }}</flux:heading>
                                            <flux:text class="mt-1">{{ __('This parent will not be able to log in until you reactivate their account.') }}</flux:text>
                                        </div>
                                        <flux:textarea
                                            name="deactivation_reason"
                                            :label="__('Reason for deactivation')"
                                            :placeholder="__('e.g. No longer a guardian of any active student...')"
                                            required
                                            rows="3"
                                        />
                                        <div class="flex justify-end gap-2">
                                            <flux:modal.close>
                                                <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                                            </flux:modal.close>
                                            <flux:button type="submit" variant="danger">{{ __('Deactivate') }}</flux:button>
                                        </div>
                                    </form>
                                </flux:modal>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="text-center py-8">
                            {{ __('No parents found.') }}
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        {{ $parents->links() }}
    </div>
</x-layouts::app>
