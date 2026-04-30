@props(['title', 'description' => null, 'action' => null, 'actionLabel' => null, 'actionIcon' => 'plus'])

<div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <flux:heading size="xl">{{ $title }}</flux:heading>
        @if ($description)
            <flux:text class="mt-1">{{ $description }}</flux:text>
        @endif
    </div>
    <div class="flex items-center gap-2">
        {{ $slot }}
        @if ($action)
            <flux:button variant="primary" icon="{{ $actionIcon }}" href="{{ $action }}" wire:navigate>
                {{ $actionLabel }}
            </flux:button>
        @endif
    </div>
</div>
