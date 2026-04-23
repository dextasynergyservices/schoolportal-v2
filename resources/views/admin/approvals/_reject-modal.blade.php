{{-- Reusable reject reason modal partial --}}
{{-- Usage: @include('admin.approvals._reject-modal', ['action' => $action, 'entityLabel' => 'Result']) --}}
<div x-data="{ showReject: false, submitting: false }">
    <flux:button @click="showReject = true" variant="danger" size="sm">{{ __('Reject') }}</flux:button>

    <div x-show="showReject" x-cloak x-transition class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50" @click.self="showReject = false" @keydown.escape.window="showReject = false">
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-xl p-6 max-w-md w-full mx-4" @click.stop>
            <div class="flex items-center gap-3 mb-4">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                    <flux:icon name="x-circle" class="size-5 text-red-600" />
                </div>
                <div>
                    <h3 class="text-base font-semibold text-zinc-900 dark:text-white">{{ __('Reject :type', ['type' => $entityLabel]) }}</h3>
                    <p class="text-xs text-zinc-500">{{ __('by') }} {{ $action->teacher?->name }}</p>
                </div>
            </div>
            <form method="POST" action="{{ route('admin.approvals.reject', $action) }}" @submit="submitting = true">
                @csrf
                <div class="mb-4">
                    <label for="reject_reason_modal_{{ $action->id }}" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('Reason for rejection') }}</label>
                    <textarea
                        id="reject_reason_modal_{{ $action->id }}"
                        name="rejection_reason"
                        rows="3"
                        required
                        maxlength="500"
                        placeholder="{{ __('Explain why this is being rejected so the teacher can fix and resubmit...') }}"
                        class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-3 py-2 text-sm text-zinc-900 dark:text-white placeholder-zinc-400 focus:border-red-500 focus:ring-1 focus:ring-red-500"
                    ></textarea>
                </div>
                <div class="flex justify-end gap-2">
                    <flux:button type="button" variant="subtle" size="sm" @click="showReject = false">{{ __('Cancel') }}</flux:button>
                    <flux:button type="submit" variant="danger" size="sm" x-bind:disabled="submitting">
                        <span x-show="!submitting">{{ __('Reject') }}</span>
                        <span x-show="submitting" x-cloak class="inline-flex items-center gap-1">
                            <flux:icon name="arrow-path" class="size-3 animate-spin" /> {{ __('Rejecting...') }}
                        </span>
                    </flux:button>
                </div>
            </form>
        </div>
    </div>
</div>
