<x-layouts::app :title="$email->subject">
    <div class="space-y-6">
        <x-admin-header :title="__('Sent Email')" />

        <div class="max-w-3xl rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 overflow-hidden">
            {{-- Email header --}}
            <div class="border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 px-6 py-4">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $email->subject }}</h2>
                <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-zinc-500 dark:text-zinc-400">
                    <span class="flex items-center gap-1.5">
                        <flux:icon name="user" class="size-3.5" />
                        {{ $email->sender?->name ?? '—' }}
                    </span>
                    <span class="flex items-center gap-1.5">
                        <flux:icon name="clock" class="size-3.5" />
                        {{ $email->sent_at?->format('M j, Y g:i A') ?? '—' }}
                    </span>
                </div>
            </div>

            <div class="p-6 space-y-6">
                {{-- Delivery stats --}}
                <div class="flex gap-4">
                    <div class="flex-1 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-3 text-center">
                        <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $email->sent_count }}</p>
                        <p class="text-xs font-medium text-green-700 dark:text-green-300 mt-0.5">{{ __('Delivered') }}</p>
                    </div>
                    @if ($email->failed_count > 0)
                        <div class="flex-1 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-3 text-center">
                            <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $email->failed_count }}</p>
                            <p class="text-xs font-medium text-red-700 dark:text-red-300 mt-0.5">{{ __('Failed') }}</p>
                        </div>
                    @endif
                    <div class="flex-1 rounded-lg bg-zinc-50 dark:bg-zinc-700/50 border border-zinc-200 dark:border-zinc-600 p-3 text-center">
                        <p class="text-2xl font-bold text-zinc-700 dark:text-zinc-200">{{ $email->total_recipients }}</p>
                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 mt-0.5">{{ __('Recipients') }}</p>
                    </div>
                </div>

                {{-- Recipient schools --}}
                <div>
                    <dt class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-2">
                        {{ __('Recipient Schools') }}
                    </dt>
                    <dd>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($recipientSchools as $school)
                                <flux:badge size="sm">{{ $school->name }}</flux:badge>
                            @endforeach
                        </div>
                    </dd>
                </div>

                {{-- Email body --}}
                <div>
                    <dt class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-2">{{ __('Email Body') }}</dt>
                    <dd class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900 p-5">
                        <div class="prose dark:prose-invert max-w-none text-sm">
                            {!! $email->body !!}
                        </div>
                    </dd>
                </div>
            </div>
        </div>

        <flux:button variant="ghost" href="{{ route('super-admin.emails.index') }}" wire:navigate icon="arrow-left">
            {{ __('Back to Emails') }}
        </flux:button>
    </div>
</x-layouts::app>
