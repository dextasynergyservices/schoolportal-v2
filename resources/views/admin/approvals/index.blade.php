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
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                @if ($action->entity_type === 'result')
                                    <flux:badge color="blue" size="sm">{{ __('Result') }}</flux:badge>
                                @elseif ($action->entity_type === 'assignment')
                                    <flux:badge color="purple" size="sm">{{ __('Assignment') }}</flux:badge>
                                @elseif ($action->entity_type === 'notice')
                                    <flux:badge color="amber" size="sm">{{ __('Notice') }}</flux:badge>
                                @elseif ($action->entity_type === 'quiz')
                                    <flux:badge color="indigo" size="sm">{{ __('Quiz') }}</flux:badge>
                                @elseif ($action->entity_type === 'game')
                                    <flux:badge color="cyan" size="sm">{{ __('Game') }}</flux:badge>
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

                        <div class="flex items-center gap-2 shrink-0">
                            {{-- Preview link for quizzes and games --}}
                            @if ($action->entity_type === 'quiz')
                                <flux:button variant="subtle" size="xs" href="{{ route('admin.quizzes.show', $action->entity_id) }}" wire:navigate>
                                    <flux:icon name="eye" class="size-3.5 mr-1" /> {{ __('Preview') }}
                                </flux:button>
                            @elseif ($action->entity_type === 'game')
                                <flux:button variant="subtle" size="xs" href="{{ route('admin.games.show', $action->entity_id) }}" wire:navigate>
                                    <flux:icon name="eye" class="size-3.5 mr-1" /> {{ __('Preview') }}
                                </flux:button>
                            @endif

                            @if ($action->status === 'pending')
                                <form method="POST" action="{{ route('admin.approvals.approve', $action) }}">
                                    @csrf
                                    <flux:button type="submit" variant="primary" size="xs">{{ __('Approve') }}</flux:button>
                                </form>

                                <div x-data="{ showRejectModal: false, rejecting: false }">
                                    <flux:button @click="showRejectModal = true" variant="danger" size="xs">{{ __('Reject') }}</flux:button>

                                    {{-- Rejection reason modal --}}
                                    <div x-show="showRejectModal" x-cloak x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="showRejectModal = false" @keydown.escape.window="showRejectModal = false">
                                        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-xl p-6 max-w-md w-full mx-4" @click.stop>
                                            <div class="flex items-center gap-3 mb-4">
                                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                                                    <flux:icon name="x-circle" class="size-5 text-red-600" />
                                                </div>
                                                <div>
                                                    <h3 class="text-base font-semibold text-zinc-900 dark:text-white">{{ __('Reject Submission') }}</h3>
                                                    <p class="text-xs text-zinc-500">{{ ucfirst($action->entity_type) }} {{ __('by') }} {{ $action->teacher?->name }}</p>
                                                </div>
                                            </div>
                                            <form method="POST" action="{{ route('admin.approvals.reject', $action) }}" @submit="rejecting = true">
                                                @csrf
                                                <div class="mb-4">
                                                    <label for="rejection_reason_{{ $action->id }}" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('Reason for rejection') }}</label>
                                                    <textarea
                                                        id="rejection_reason_{{ $action->id }}"
                                                        name="rejection_reason"
                                                        rows="3"
                                                        required
                                                        maxlength="500"
                                                        placeholder="{{ __('Explain why this submission is being rejected so the teacher can fix and resubmit...') }}"
                                                        class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-3 py-2 text-sm text-zinc-900 dark:text-white placeholder-zinc-400 focus:border-red-500 focus:ring-1 focus:ring-red-500"
                                                    ></textarea>
                                                </div>
                                                <div class="flex justify-end gap-2">
                                                    <flux:button type="button" variant="subtle" size="sm" @click="showRejectModal = false">{{ __('Cancel') }}</flux:button>
                                                    <flux:button type="submit" variant="danger" size="sm" x-bind:disabled="rejecting">
                                                        <span x-show="!rejecting">{{ __('Reject') }}</span>
                                                        <span x-show="rejecting" x-cloak class="inline-flex items-center gap-1">
                                                            <flux:icon name="arrow-path" class="size-3 animate-spin" /> {{ __('Rejecting...') }}
                                                        </span>
                                                    </flux:button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
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
