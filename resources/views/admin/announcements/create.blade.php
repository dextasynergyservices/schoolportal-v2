<x-layouts::app :title="__('New Announcement')">
    <div class="space-y-6">
        <x-admin-header :title="__('New Announcement')" />

        <div class="max-w-2xl rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <form method="POST" action="{{ route('admin.announcements.store') }}" class="space-y-6">
                @csrf

                <flux:input name="title" :label="__('Title')" :value="old('title')" required />

                <flux:textarea name="content" :label="__('Message')" rows="5" required>{{ old('content') }}</flux:textarea>

                <flux:select name="priority" :label="__('Priority')">
                    <option value="info" @selected(old('priority', 'info') === 'info')>{{ __('Info — Blue banner') }}</option>
                    <option value="warning" @selected(old('priority') === 'warning')>{{ __('Warning — Amber banner') }}</option>
                    <option value="critical" @selected(old('priority') === 'critical')>{{ __('Critical — Red banner') }}</option>
                </flux:select>

                <fieldset>
                    <legend class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        {{ __('Send to (leave empty for all roles)') }}
                    </legend>
                    <div class="flex flex-wrap gap-3">
                        <flux:checkbox name="target_roles[]" value="teacher" :label="__('Teachers')" :checked="in_array('teacher', old('target_roles', []))" />
                        <flux:checkbox name="target_roles[]" value="student" :label="__('Students')" :checked="in_array('student', old('target_roles', []))" />
                        <flux:checkbox name="target_roles[]" value="parent" :label="__('Parents')" :checked="in_array('parent', old('target_roles', []))" />
                    </div>
                </fieldset>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <flux:input name="starts_at" :label="__('Starts At (optional)')" :value="old('starts_at')" type="date" />
                    <flux:input name="expires_at" :label="__('Expires At (optional)')" :value="old('expires_at')" type="date" />
                </div>

                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('Leave dates empty to show immediately with no expiry. Users can dismiss the banner.') }}
                </p>

                <div class="flex gap-3">
                    <flux:button variant="primary" type="submit">{{ __('Publish Announcement') }}</flux:button>
                    <flux:button variant="ghost" href="{{ route('admin.announcements.index') }}" wire:navigate>{{ __('Cancel') }}</flux:button>
                </div>
            </form>
        </div>
    </div>
</x-layouts::app>
