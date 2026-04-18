<x-layouts::app :title="__('Notices')">
    <div class="space-y-6">
        <x-admin-header
            :title="__('Notices')"
            :action="route('admin.notices.create')"
            :actionLabel="__('Add Notice')"
        />

        @if (session('success'))
            <flux:callout variant="success" icon="check-circle">{{ session('success') }}</flux:callout>
        @endif

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Title') }}</flux:table.column>
                <flux:table.column>{{ __('Created By') }}</flux:table.column>
                <flux:table.column>{{ __('Published') }}</flux:table.column>
                <flux:table.column>{{ __('Expires') }}</flux:table.column>
                <flux:table.column>{{ __('Date') }}</flux:table.column>
                <flux:table.column class="w-32" />
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($notices as $notice)
                    <flux:table.row>
                        <flux:table.cell class="font-medium">{{ Str::limit($notice->title, 50) }}</flux:table.cell>
                        <flux:table.cell class="text-zinc-500">{{ $notice->creator?->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            @if ($notice->is_published)
                                <flux:badge color="green" size="sm">{{ __('Yes') }}</flux:badge>
                            @else
                                <flux:badge color="zinc" size="sm">{{ __('No') }}</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>{{ $notice->expires_at?->format('M j, Y') ?? '—' }}</flux:table.cell>
                        <flux:table.cell class="text-zinc-500">{{ $notice->created_at->format('M j, Y') }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-1">
                                <flux:button variant="subtle" size="xs" icon="pencil-square" href="{{ route('admin.notices.edit', $notice) }}" wire:navigate />
                                <x-confirm-delete
                                    :action="route('admin.notices.destroy', $notice)"
                                    :title="__('Delete Notice')"
                                    :message="__('Are you sure you want to delete this notice? This action cannot be undone.')"
                                    :ariaLabel="__('Delete notice')"
                                />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="text-center py-8">
                            {{ __('No notices found.') }}
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        {{ $notices->links() }}
    </div>
</x-layouts::app>
