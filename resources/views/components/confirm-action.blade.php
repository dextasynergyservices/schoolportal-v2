@props([
    'title' => __('Confirm Action'),
    'message',
    'confirmLabel' => __('Confirm'),
    'cancelLabel' => __('Cancel'),
    'variant' => 'primary',
    'buttonVariant' => 'primary',
    'buttonSize' => null,
    'buttonIcon' => null,
    'buttonLabel',
    'iconColor' => 'blue',
])

{{--
    Confirm-action modal for bulk operations (import, promote, upload).
    The form fields are passed as the slot content.

    Usage:
        <x-confirm-action
            :title="__('Import Students')"
            :message="__('This will import 25 students into the system.')"
            :confirmLabel="__('Import Now')"
            :buttonLabel="__('Import Students')"
        >
            <input type="hidden" name="temp_path" value="{{ $tempPath }}">
        </x-confirm-action>
--}}

<div x-data="{ open: false }">
    {{-- Trigger button --}}
    <flux:button
        type="button"
        :variant="$buttonVariant"
        :size="$buttonSize"
        :icon="$buttonIcon"
        x-on:click.prevent="open = true"
    >
        {{ $buttonLabel }}
    </flux:button>

    {{-- Modal overlay --}}
    <template x-teleport="body">
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            x-on:keydown.escape.window="open = false"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
            x-cloak
        >
            {{-- Backdrop --}}
            <div class="fixed inset-0 bg-black/50 dark:bg-black/70" x-on:click="open = false" aria-hidden="true"></div>

            {{-- Dialog --}}
            <div
                x-show="open"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                x-trap.noscroll="open"
                class="relative w-full max-w-md rounded-xl bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 p-6 shadow-xl"
            >
                {{-- Icon --}}
                @php
                    $iconBg = match($iconColor) {
                        'blue' => 'bg-blue-100 dark:bg-blue-900/30',
                        'amber' => 'bg-amber-100 dark:bg-amber-900/30',
                        'green' => 'bg-green-100 dark:bg-green-900/30',
                        default => 'bg-blue-100 dark:bg-blue-900/30',
                    };
                    $iconText = match($iconColor) {
                        'blue' => 'text-blue-600 dark:text-blue-400',
                        'amber' => 'text-amber-600 dark:text-amber-400',
                        'green' => 'text-green-600 dark:text-green-400',
                        default => 'text-blue-600 dark:text-blue-400',
                    };
                @endphp
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full {{ $iconBg }} mb-4">
                    <svg class="h-6 w-6 {{ $iconText }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>

                {{-- Content --}}
                <div class="text-center">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $title }}</h3>
                    <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ $message }}</p>
                </div>

                {{-- Actions --}}
                <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-center" x-data="{ processing: false }">
                    <flux:button variant="ghost" x-on:click="open = false" x-bind:disabled="processing">
                        {{ $cancelLabel }}
                    </flux:button>

                    <form method="POST" action="{{ $attributes->get('action') }}" x-on:submit="processing = true">
                        @csrf
                        {{ $slot }}
                        <flux:button type="submit" :variant="$variant" x-bind:disabled="processing" class="w-full sm:w-auto">
                            <span x-show="!processing">{{ $confirmLabel }}</span>
                            <span x-show="processing" x-cloak class="inline-flex items-center gap-2">
                                <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ __('Processing...') }}
                            </span>
                        </flux:button>
                    </form>
                </div>
            </div>
        </div>
    </template>
</div>
