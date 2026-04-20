<x-layouts::app :title="__('AI Credits')">
    <div class="space-y-6">
        <x-admin-header :title="__('AI Credits')" />

        @if (session('success'))
            <flux:callout variant="success" icon="check-circle">{{ session('success') }}</flux:callout>
        @endif
        @if (session('error'))
            <flux:callout variant="danger" icon="x-circle">{{ session('error') }}</flux:callout>
        @endif

        {{-- Balance Overview --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 text-center">
                <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $balance }}</p>
                <p class="text-xs text-zinc-500">{{ __('Total Available') }}</p>
            </div>
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 text-center">
                <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $school->ai_free_credits }}</p>
                <p class="text-xs text-zinc-500">{{ __('Free (resets monthly)') }}</p>
            </div>
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 text-center">
                <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $school->ai_purchased_credits }}</p>
                <p class="text-xs text-zinc-500">{{ __('Purchased') }}</p>
            </div>
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 text-center">
                <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $monthlyUsage['total'] }}</p>
                <p class="text-xs text-zinc-500">{{ __('Used This Month') }}</p>
            </div>
        </div>

        <div class="flex flex-wrap gap-3">
            <flux:button variant="primary" href="{{ route('admin.credits.purchase') }}" wire:navigate>{{ __('Purchase Credits') }}</flux:button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Monthly Usage Breakdown --}}
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 sm:p-5">
                <h3 class="text-base font-semibold text-zinc-900 dark:text-white mb-3">{{ __('Usage This Month') }}</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-zinc-500">{{ __('Quizzes Generated') }}</span>
                        <span class="font-semibold text-zinc-900 dark:text-white">{{ $monthlyUsage['quizzes'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-zinc-500">{{ __('Games Generated') }}</span>
                        <span class="font-semibold text-zinc-900 dark:text-white">{{ $monthlyUsage['games'] }}</span>
                    </div>
                </div>
            </div>

            {{-- Level Allocations --}}
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 sm:p-5">
                <h3 class="text-base font-semibold text-zinc-900 dark:text-white mb-3">{{ __('Level Allocations') }}</h3>
                @if ($levels->isNotEmpty())
                    <form method="POST" action="{{ route('admin.credits.allocate') }}" class="space-y-3">
                        @csrf
                        @foreach ($levels as $level)
                            @php $alloc = $allocations->get($level->id); @endphp
                            <div class="flex items-center gap-3">
                                <span class="text-sm text-zinc-700 dark:text-zinc-300 w-24">{{ $level->name }}</span>
                                <input type="hidden" name="allocations[{{ $loop->index }}][level_id]" value="{{ $level->id }}">
                                <input type="number" name="allocations[{{ $loop->index }}][credits]"
                                    value="{{ $alloc?->allocated_credits ?? 0 }}"
                                    min="0" class="w-24 rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 text-sm">
                                @if ($alloc)
                                    <span class="text-xs text-zinc-500">({{ __('Used:') }} {{ $alloc->used_credits }})</span>
                                @endif
                            </div>
                        @endforeach
                        <flux:button type="submit" variant="filled" size="sm">{{ __('Update Allocations') }}</flux:button>
                    </form>
                @else
                    <p class="text-sm text-zinc-500">{{ __('No school levels configured.') }}</p>
                @endif
            </div>
        </div>

        {{-- Recent Purchases --}}
        @if ($purchases->isNotEmpty())
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 sm:p-5">
                <h3 class="text-base font-semibold text-zinc-900 dark:text-white mb-3">{{ __('Recent Purchases') }}</h3>
                <div class="space-y-2">
                    @foreach ($purchases as $purchase)
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-zinc-700 dark:text-zinc-300">
                                {{ $purchase->created_at->format('M j, Y') }} &mdash; {{ $purchase->credits }} {{ __('credits') }}
                            </span>
                            <span class="font-semibold text-zinc-900 dark:text-white">N{{ number_format($purchase->amount_naira) }}</span>
                        </div>
                    @endforeach
                </div>
                @if ($purchases->hasPages())
                    <div class="mt-3">{{ $purchases->links() }}</div>
                @endif
            </div>
        @endif

        {{-- Recent Usage --}}
        @if ($recentUsage->isNotEmpty())
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 sm:p-5">
                <h3 class="text-base font-semibold text-zinc-900 dark:text-white mb-3">{{ __('Recent Usage') }}</h3>
                <div class="space-y-2">
                    @foreach ($recentUsage as $log)
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-zinc-700 dark:text-zinc-300">
                                {{ $log->user?->name }} &mdash; {{ ucfirst($log->usage_type) }}
                                @if ($log->level)
                                    ({{ $log->level->name }})
                                @endif
                            </span>
                            <span class="text-xs text-zinc-500">{{ $log->created_at->format('M j, H:i') }}</span>
                        </div>
                    @endforeach
                </div>
                @if ($recentUsage->hasPages())
                    <div class="mt-3">{{ $recentUsage->links() }}</div>
                @endif
            </div>
        @endif
    </div>
</x-layouts::app>
