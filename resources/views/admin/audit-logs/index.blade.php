<x-layouts::app :title="__('Audit Logs')">
    <div class="space-y-6">
        <x-admin-header :title="__('Audit Logs')">
            <flux:button variant="subtle" size="sm" icon="arrow-down-tray" href="{{ route('admin.audit-logs.export', request()->query()) }}">
                {{ __('Export CSV') }}
            </flux:button>
        </x-admin-header>

        <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 max-w-sm">
                <flux:input name="action" :value="request('action')" placeholder="{{ __('Filter by action (e.g. student.created)...') }}" icon="magnifying-glass" />
            </div>
            <flux:button type="submit" variant="filled" size="sm">{{ __('Filter') }}</flux:button>
            @if (request()->hasAny(['action', 'user_id']))
                <flux:button variant="subtle" size="sm" href="{{ route('admin.audit-logs.index') }}" wire:navigate>{{ __('Clear') }}</flux:button>
            @endif
        </form>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Action') }}</flux:table.column>
                <flux:table.column>{{ __('User') }}</flux:table.column>
                <flux:table.column>{{ __('Entity') }}</flux:table.column>
                <flux:table.column>{{ __('IP Address') }}</flux:table.column>
                <flux:table.column>{{ __('Date') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($logs as $log)
                    <flux:table.row>
                        <flux:table.cell>
                            <flux:badge size="sm">{{ $log->action }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="font-medium">{{ $log->user?->name ?? __('System') }}</flux:table.cell>
                        <flux:table.cell class="text-zinc-500">
                            @if ($log->entity_type)
                                {{ $log->entity_type }}#{{ $log->entity_id }}
                            @else
                                —
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="text-zinc-500">{{ $log->ip_address ?? '—' }}</flux:table.cell>
                        <flux:table.cell class="text-zinc-500">{{ $log->created_at->format('M j, Y g:i A') }}</flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="text-center py-8">
                            {{ __('No audit logs found.') }}
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        {{ $logs->links() }}
    </div>
</x-layouts::app>
