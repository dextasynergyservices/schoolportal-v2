<x-layouts::app :title="__('Edit Notice')">
    <div class="space-y-6">
        <x-admin-header :title="__('Edit Notice')" />

        <div class="max-w-2xl rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <form method="POST" action="{{ route('admin.notices.update', $notice) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <flux:input name="title" :label="__('Title')" :value="old('title', $notice->title)" required />

                <flux:textarea name="content" :label="__('Content')" rows="5" required>{{ old('content', $notice->content) }}</flux:textarea>

                @if ($levels->count())
                    <fieldset>
                        <legend class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">{{ __('Target Levels (leave empty for all)') }}</legend>
                        <div class="flex flex-wrap gap-3">
                            @foreach ($levels as $level)
                                <flux:checkbox name="target_levels[]" :value="$level->id" :label="$level->name" :checked="in_array($level->id, old('target_levels', $notice->target_levels ?? []))" />
                            @endforeach
                        </div>
                    </fieldset>
                @endif

                <fieldset>
                    <legend class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">{{ __('Target Roles (leave empty for all)') }}</legend>
                    <div class="flex flex-wrap gap-3">
                        <flux:checkbox name="target_roles[]" value="student" :label="__('Students')" :checked="in_array('student', old('target_roles', $notice->target_roles ?? []))" />
                        <flux:checkbox name="target_roles[]" value="parent" :label="__('Parents')" :checked="in_array('parent', old('target_roles', $notice->target_roles ?? []))" />
                        <flux:checkbox name="target_roles[]" value="teacher" :label="__('Teachers')" :checked="in_array('teacher', old('target_roles', $notice->target_roles ?? []))" />
                    </div>
                </fieldset>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <flux:input name="expires_at" :label="__('Expires On (optional)')" :value="old('expires_at', $notice->expires_at?->format('Y-m-d'))" type="date" />
                    <div class="flex items-end">
                        <flux:switch name="is_published" :label="__('Published')" :checked="old('is_published', $notice->is_published)" value="1" />
                    </div>
                </div>

                <div class="flex gap-3">
                    <flux:button variant="primary" type="submit">{{ __('Update Notice') }}</flux:button>
                    <flux:button variant="ghost" href="{{ route('admin.notices.index') }}" wire:navigate>{{ __('Cancel') }}</flux:button>
                </div>
            </form>
        </div>
    </div>
</x-layouts::app>
