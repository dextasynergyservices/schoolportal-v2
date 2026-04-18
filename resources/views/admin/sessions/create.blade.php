<x-layouts::app :title="__('Create Academic Session')">
    <div class="space-y-6">
        <x-admin-header :title="__('Create Academic Session')" :description="__('A new session will automatically include 3 terms.')" />

        <div class="max-w-xl rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <form method="POST" action="{{ route('admin.sessions.store') }}" class="space-y-6">
                @csrf

                <flux:input name="name" :label="__('Session Name')" :value="old('name')" placeholder="2025/2026" required />
                <div class="grid grid-cols-2 gap-4">
                    <flux:input name="start_date" :label="__('Start Date')" :value="old('start_date')" type="date" required />
                    <flux:input name="end_date" :label="__('End Date')" :value="old('end_date')" type="date" required />
                </div>

                <div class="flex gap-3">
                    <flux:button variant="primary" type="submit">{{ __('Create Session') }}</flux:button>
                    <flux:button variant="ghost" href="{{ route('admin.sessions.index') }}" wire:navigate>{{ __('Cancel') }}</flux:button>
                </div>
            </form>
        </div>
    </div>
</x-layouts::app>
