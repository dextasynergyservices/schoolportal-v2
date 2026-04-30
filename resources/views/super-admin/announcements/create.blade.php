<x-layouts::app :title="__('New Platform Announcement')">
    <div class="space-y-6">
        <x-admin-header :title="__('New Platform Announcement')" />

        <div class="max-w-2xl rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <form method="POST" action="{{ route('super-admin.announcements.store') }}" class="space-y-6">
                @csrf

                <flux:input name="title" :label="__('Title')" :value="old('title')" required />

                <x-rich-editor
                    name="content"
                    :label="__('Message')"
                    :value="old('content', '')"
                    :placeholder="__('Write your announcement message here...')"
                    required />

                <flux:select name="priority" :label="__('Priority')">
                    <option value="info" @selected(old('priority', 'info') === 'info')>{{ __('Info — Blue banner') }}</option>
                    <option value="warning" @selected(old('priority') === 'warning')>{{ __('Warning — Amber banner') }}</option>
                    <option value="critical" @selected(old('priority') === 'critical')>{{ __('Critical — Red banner') }}</option>
                </flux:select>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <flux:input name="starts_at" :label="__('Starts At (optional)')" :value="old('starts_at')" type="date" />
                    <flux:input name="expires_at" :label="__('Expires At (optional)')" :value="old('expires_at')" type="date" />
                </div>

                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('Leave dates empty to show immediately with no expiry. School admins must click "Mark as Read" to dismiss.') }}
                </p>

                <div class="flex gap-3">
                    <flux:button variant="primary" type="submit">{{ __('Publish Announcement') }}</flux:button>
                    <flux:button variant="ghost" href="{{ route('super-admin.announcements.index') }}" wire:navigate>{{ __('Cancel') }}</flux:button>
                </div>
            </form>
        </div>
    </div>
</x-layouts::app>
