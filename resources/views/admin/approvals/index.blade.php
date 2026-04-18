<x-layouts::app :title="__('Approvals')">
    <div class="space-y-6">
        <x-admin-header :title="__('Teacher Approvals')" :description="__(':count pending', ['count' => $pendingCount])" />

        @if (session('success'))
            <flux:callout variant="success" icon="check-circle">{{ session('success') }}</flux:callout>
        @endif

        <div class="flex gap-2">
            <flux:button variant="{{ $status === 'pending' ? 'filled' : 'subtle' }}" size="sm" href="{{ route('admin.approvals.index', ['status' => 'pending']) }}" wire:navigate>
                {{ __('Pending') }}
            </flux:button>
            <flux:button variant="{{ $status === 'approved' ? 'filled' : 'subtle' }}" size="sm" href="{{ route('admin.approvals.index', ['status' => 'approved']) }}" wire:navigate>
                {{ __('Approved') }}
            </flux:button>
            <flux:button variant="{{ $status === 'rejected' ? 'filled' : 'subtle' }}" size="sm" href="{{ route('admin.approvals.index', ['status' => 'rejected']) }}" wire:navigate>
                {{ __('Rejected') }}
            </flux:button>
            <flux:button variant="{{ $status === 'all' ? 'filled' : 'subtle' }}" size="sm" href="{{ route('admin.approvals.index', ['status' => 'all']) }}" wire:navigate>
                {{ __('All') }}
            </flux:button>
        </div>

        <div class="space-y-4">
            @forelse ($actions as $action)
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                @if ($action->entity_type === 'result')
                                    <flux:badge color="blue" size="sm">{{ __('Result') }}</flux:badge>
                                @elseif ($action->entity_type === 'assignment')
                                    <flux:badge color="purple" size="sm">{{ __('Assignment') }}</flux:badge>
                                @elseif ($action->entity_type === 'notice')
                                    <flux:badge color="amber" size="sm">{{ __('Notice') }}</flux:badge>
                                @else
                                    <flux:badge size="sm">{{ ucfirst($action->entity_type) }}</flux:badge>
                                @endif

                                @if ($action->status === 'pending')
                                    <flux:badge color="yellow" size="sm">{{ __('Pending') }}</flux:badge>
                                @elseif ($action->status === 'approved')
                                    <flux:badge color="green" size="sm">{{ __('Approved') }}</flux:badge>
                                @else
                                    <flux:badge color="red" size="sm">{{ __('Rejected') }}</flux:badge>
                                @endif
                            </div>
                            <p class="text-sm font-medium">
                                {{ ucfirst(str_replace('_', ' ', $action->action_type)) }}
                                {{ __('by') }} {{ $action->teacher?->name ?? __('Unknown') }}
                            </p>
                            <p class="text-xs text-zinc-500 mt-1">{{ $action->created_at->diffForHumans() }}</p>
                            @if ($action->rejection_reason)
                                <p class="text-xs text-red-600 mt-1">{{ __('Reason:') }} {{ $action->rejection_reason }}</p>
                            @endif
                        </div>

                        @if ($action->status === 'pending')
                            <div class="flex items-center gap-2 shrink-0">
                                <form method="POST" action="{{ route('admin.approvals.approve', $action) }}">
                                    @csrf
                                    <flux:button type="submit" variant="primary" size="xs">{{ __('Approve') }}</flux:button>
                                </form>

                                <div x-data="{ open: false }">
                                    <flux:button @click="open = !open" variant="danger" size="xs">{{ __('Reject') }}</flux:button>
                                    <div x-show="open" x-cloak class="mt-2">
                                        <form method="POST" action="{{ route('admin.approvals.reject', $action) }}" class="flex gap-2">
                                            @csrf
                                            <flux:input name="rejection_reason" placeholder="{{ __('Reason for rejection...') }}" required size="sm" />
                                            <flux:button type="submit" variant="danger" size="sm">{{ __('Confirm') }}</flux:button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-8 text-zinc-500">
                    {{ __('No :status approvals found.', ['status' => $status === 'all' ? '' : $status]) }}
                </div>
            @endforelse
        </div>

        {{ $actions->links() }}
    </div>
</x-layouts::app>
