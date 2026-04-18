<x-layouts::app :title="__('My Notices')">
    <div class="space-y-6">
        <x-admin-header
            :title="__('My Notices')"
            :action="route('teacher.notices.create')"
            :actionLabel="__('Post Notice')"
            actionIcon="megaphone"
        />

        @if (session('success'))
            <flux:callout variant="success" icon="check-circle">{{ session('success') }}</flux:callout>
        @endif

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Title') }}</flux:table.column>
                <flux:table.column class="hidden sm:table-cell">{{ __('Published') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column class="hidden md:table-cell">{{ __('Expires') }}</flux:table.column>
                <flux:table.column class="hidden sm:table-cell">{{ __('Created') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($notices as $notice)
                    <flux:table.row>
                        <flux:table.cell class="font-medium">{{ Str::limit($notice->title, 50) }}</flux:table.cell>
                        <flux:table.cell class="hidden sm:table-cell">
                            @if ($notice->is_published)
                                <flux:badge color="green" size="sm">{{ __('Yes') }}</flux:badge>
                            @else
                                <flux:badge color="zinc" size="sm">{{ __('No') }}</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            @if ($notice->status === 'approved')
                                <flux:badge color="green" size="sm">{{ __('Approved') }}</flux:badge>
                            @elseif ($notice->status === 'pending')
                                <flux:badge color="yellow" size="sm">{{ __('Pending') }}</flux:badge>
                            @else
                                <flux:badge color="red" size="sm">{{ __('Rejected') }}</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="hidden md:table-cell text-zinc-500">
                            {{ $notice->expires_at?->format('M j, Y') ?? '—' }}
                        </flux:table.cell>
                        <flux:table.cell class="hidden sm:table-cell text-zinc-500">{{ $notice->created_at->format('M j, Y') }}</flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="text-center py-8">
                            {{ __('No notices posted yet.') }}
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        {{ $notices->links() }}
    </div>
</x-layouts::app>
