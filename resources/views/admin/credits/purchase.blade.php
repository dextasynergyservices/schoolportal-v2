<x-layouts::app :title="__('Purchase AI Credits')">
    <div class="space-y-6">
        <div>
            <flux:button variant="subtle" size="sm" href="{{ route('admin.credits.index') }}" wire:navigate class="mb-2">
                <flux:icon name="arrow-left" class="size-4 mr-1" /> {{ __('Back to Credits') }}
            </flux:button>
            <h1 class="text-xl font-bold text-zinc-900 dark:text-white">{{ __('Purchase AI Credits') }}</h1>
            <p class="text-sm text-zinc-500 mt-1">{{ __('Current balance:') }} {{ $balance }} {{ __('credits') }}</p>
        </div>

        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 sm:p-6" x-data="{ credits: 5 }">
            <h3 class="text-base font-semibold text-zinc-900 dark:text-white mb-4">{{ __('Select Amount') }}</h3>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
                @foreach ([5, 10, 25, 50] as $amount)
                    <button type="button" @click="credits = {{ $amount }}"
                        class="rounded-lg border-2 p-4 text-center transition-all"
                        :class="credits === {{ $amount }} ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'border-zinc-200 dark:border-zinc-700 hover:border-indigo-300'">
                        <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $amount }}</p>
                        <p class="text-sm text-zinc-500">{{ __('credits') }}</p>
                        <p class="text-sm font-semibold text-indigo-600 dark:text-indigo-400 mt-1">N{{ number_format(($amount / 5) * 1000) }}</p>
                    </button>
                @endforeach
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('Or enter custom amount (multiples of 5)') }}</label>
                <input type="number" x-model.number="credits" min="5" max="500" step="5"
                    class="w-32 rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 text-sm">
            </div>

            <div class="rounded-lg bg-zinc-50 dark:bg-zinc-900/50 p-4 mb-6">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-zinc-500">{{ __('Credits') }}</span>
                    <span class="font-semibold text-zinc-900 dark:text-white" x-text="credits"></span>
                </div>
                <div class="flex items-center justify-between text-sm mt-2">
                    <span class="text-zinc-500">{{ __('Rate') }}</span>
                    <span class="text-zinc-700 dark:text-zinc-300">N200 / {{ __('credit') }}</span>
                </div>
                <div class="border-t border-zinc-200 dark:border-zinc-700 mt-3 pt-3">
                    <div class="flex items-center justify-between">
                        <span class="font-semibold text-zinc-900 dark:text-white">{{ __('Total') }}</span>
                        <span class="text-xl font-bold text-indigo-600 dark:text-indigo-400" x-text="'N' + ((credits / 5) * 1000).toLocaleString()"></span>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.credits.purchase.process') }}">
                @csrf
                <input type="hidden" name="credits" :value="credits">
                <flux:button type="submit" variant="primary" class="w-full sm:w-auto">
                    <flux:icon name="credit-card" class="size-4 mr-1" />
                    {{ __('Pay with Paystack') }}
                </flux:button>
                <p class="text-xs text-zinc-500 mt-2">{{ __('You will be redirected to Paystack to complete payment securely. Credits are added to your balance on successful payment.') }}</p>
            </form>
        </div>

        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 sm:p-5">
            <h3 class="text-base font-semibold text-zinc-900 dark:text-white mb-2">{{ __('Pricing') }}</h3>
            <div class="text-sm text-zinc-500 space-y-1">
                <p>{{ __('1 credit = 1 AI generation (quiz or game, regardless of size)') }}</p>
                <p>{{ __('Must purchase in multiples of 5') }}</p>
                <p>{{ __('N1,000 per 5 credits (N200/credit)') }}</p>
                <p>{{ __('Purchased credits never expire') }}</p>
                <p>{{ __('15 free credits reset on the 1st of each month') }}</p>
                <p>{{ __('Manual creation is always free and unlimited') }}</p>
            </div>
        </div>
    </div>
</x-layouts::app>
