<div class="rounded-lg bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 p-6 text-center">
    <flux:icon name="exclamation-triangle" class="mx-auto h-10 w-10 text-amber-500" />
    <h3 class="mt-3 text-base font-semibold text-amber-800 dark:text-amber-200">{{ __('No AI Credits Remaining') }}</h3>
    <p class="mt-1 text-sm text-amber-700 dark:text-amber-300">
        {{ __('Your school has used all AI credits. Free credits reset on the 1st of each month.') }}
    </p>
    <div class="mt-4 space-y-2">
        <p class="text-sm text-amber-600 dark:text-amber-400">{{ __('You can still create content manually for free:') }}</p>
        <flux:button variant="primary" size="sm" @click="tab = 'manual'">
            {{ __('Switch to Manual Creation') }}
        </flux:button>
    </div>
    <p class="mt-3 text-xs text-amber-500">{{ __('Or ask your school admin to purchase more credits (5 credits = N1,000).') }}</p>
</div>
