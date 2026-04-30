@props(['status' => true, 'okLabel' => null, 'failLabel' => null])

@if ($status)
    <flux:badge color="green" size="sm">{{ $okLabel ?? __('OK') }}</flux:badge>
@else
    <flux:badge color="red" size="sm">{{ $failLabel ?? __('Error') }}</flux:badge>
@endif
