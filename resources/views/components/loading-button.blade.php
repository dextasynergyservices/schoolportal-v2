@props([
    'loadingText' => __('Processing...'),
    'variant' => 'primary',
    'class' => '',
    'icon' => null,
    'size' => null,
])

{{--
    Loading button for traditional (non-Livewire) forms.
    Wraps the form with Alpine.js to show a spinner on submit.
    The parent <form> MUST have x-data="{ submitting: false }" and
    x-on:submit="submitting = true" attributes.

    Usage:
        <form x-data="{ submitting: false }" x-on:submit="submitting = true" ...>
            ...
            <x-loading-button>Save</x-loading-button>
        </form>
--}}

<flux:button
    type="submit"
    :variant="$variant"
    :size="$size"
    :icon="$icon"
    x-bind:disabled="submitting"
    :class="$class"
    {{ $attributes }}
>
    <span x-show="!submitting">{{ $slot }}</span>
    <span x-show="submitting" x-cloak class="inline-flex items-center gap-2">
        <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        {{ $loadingText }}
    </span>
</flux:button>
