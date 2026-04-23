<x-layouts::app :title="__('Edit Announcement')">
    <div class="space-y-6">
        <x-admin-header :title="__('Edit Announcement')" />

        <div class="max-w-2xl rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <form method="POST" action="{{ route('super-admin.announcements.update', $announcement) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <flux:input name="title" :label="__('Title')" :value="old('title', $announcement->title)" required />

                <flux:textarea name="content" :label="__('Message')" rows="5" required>{{ old('content', $announcement->content) }}</flux:textarea>

                <flux:select name="priority" :label="__('Priority')">
                    <option value="info" @selected(old('priority', $announcement->priority) === 'info')>{{ __('Info — Blue banner') }}</option>
                    <option value="warning" @selected(old('priority', $announcement->priority) === 'warning')>{{ __('Warning — Amber banner') }}</option>
                    <option value="critical" @selected(old('priority', $announcement->priority) === 'critical')>{{ __('Critical — Red banner') }}</option>
                </flux:select>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <flux:input name="starts_at" :label="__('Starts At (optional)')" :value="old('starts_at', $announcement->starts_at?->format('Y-m-d'))" type="date" />
                    <flux:input name="expires_at" :label="__('Expires At (optional)')" :value="old('expires_at', $announcement->expires_at?->format('Y-m-d'))" type="date" />
                </div>

                <div class="flex gap-3">
                    <flux:button variant="primary" type="submit">{{ __('Update Announcement') }}</flux:button>
                    <flux:button variant="ghost" href="{{ route('super-admin.announcements.show', $announcement) }}" wire:navigate>{{ __('Cancel') }}</flux:button>
                </div>
            </form>
        </div>
    </div>
</x-layouts::app>
