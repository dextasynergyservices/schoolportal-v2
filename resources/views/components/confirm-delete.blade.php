@props([
    'action',
    'method' => 'DELETE',
    'title' => __('Confirm Deletion'),
    'message' => __('Are you sure you want to delete this item? This action cannot be undone.'),
    'confirmLabel' => __('Delete'),
    'cancelLabel' => __('Cancel'),
    'variant' => 'danger',
    'buttonVariant' => 'subtle',
    'buttonSize' => 'xs',
    'buttonIcon' => 'trash',
    'buttonLabel' => null,
    'ariaLabel' => __('Delete'),
])

{{--
    Confirm-delete modal component using Flux UI.
    Replaces `onsubmit="return confirm('...')"` with a proper accessible modal.

    Usage:
        <x-confirm-delete
            :action="route('admin.classes.destroy', $class)"
            :title="__('Delete Class')"
            :message="__('This will remove the class and unlink all students.')"
            :ariaLabel="__('Delete :name', ['name' => $class->name])"
        />
--}}

<div x-data="{ open: false }">
    {{-- Trigger button --}}
    @if ($buttonLabel)
        <flux:button
            :variant="$buttonVariant"
            :size="$buttonSize"
            :icon="$buttonIcon"
            x-on:click.prevent="open = true"
            :aria-label="$ariaLabel"
        >
            {{ $buttonLabel }}
        </flux:button>
    @else
        <flux:button
            :variant="$buttonVariant"
            :size="$buttonSize"
            :icon="$buttonIcon"
            x-on:click.prevent="open = true"
            :aria-label="$ariaLabel"
        />
    @endif

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
            :aria-label="'{{ addslashes($title) }}'"
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
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30 mb-4">
                    <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                </div>

                {{-- Content --}}
                <div class="text-center">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $title }}</h3>
                    <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ $message }}</p>
                </div>

                {{-- Actions --}}
                <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-center" x-data="{ deleting: false }">
                    <flux:button variant="ghost" x-on:click="open = false" x-bind:disabled="deleting">
                        {{ $cancelLabel }}
                    </flux:button>

                    <form method="POST" action="{{ $action }}" x-on:submit="deleting = true">
                        @csrf
                        @method($method)
                        <flux:button type="submit" variant="danger" x-bind:disabled="deleting" class="w-full sm:w-auto">
                            <span x-show="!deleting">{{ $confirmLabel }}</span>
                            <span x-show="deleting" x-cloak class="inline-flex items-center gap-2">
                                <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ __('Deleting...') }}
                            </span>
                        </flux:button>
                    </form>
                </div>
            </div>
        </div>
    </template>
</div>
