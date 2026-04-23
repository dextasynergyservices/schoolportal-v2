<x-layouts::app :title="__('Edit Notice')">
    <div class="space-y-6">
        <x-admin-header :title="__('Edit Notice')" />

        <div class="max-w-2xl rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <form method="POST" action="{{ route('admin.notices.update', $notice) }}" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @method('PUT')

                <flux:input name="title" :label="__('Title')" :value="old('title', $notice->title)" required />

                <flux:textarea name="content" :label="__('Content')" rows="5" required>{{ old('content', $notice->content) }}</flux:textarea>

                {{-- File Upload --}}
                <div>
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1 block">{{ __('Attachment (optional)') }}</label>
                    @if ($notice->file_url)
                        <div class="mb-2 flex items-center gap-3 rounded-lg bg-zinc-50 dark:bg-zinc-700/50 p-3">
                            <flux:icon.paper-clip class="w-5 h-5 text-zinc-500" />
                            <a href="{{ $notice->file_url }}" target="_blank" class="text-sm text-blue-600 dark:text-blue-400 hover:underline truncate">
                                {{ $notice->file_name ?? __('Attached file') }}
                            </a>
                            <label class="ml-auto flex items-center gap-2 text-sm text-red-600 cursor-pointer">
                                <input type="checkbox" name="remove_file" value="1" class="rounded border-zinc-300 dark:border-zinc-600" />
                                {{ __('Remove') }}
                            </label>
                        </div>
                    @endif
                    <input type="file" name="file" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx"
                        class="block w-full text-sm text-zinc-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-zinc-100 dark:file:bg-zinc-700 file:text-zinc-700 dark:file:text-zinc-300 hover:file:bg-zinc-200 dark:hover:file:bg-zinc-600" />
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Upload a new file to replace the existing one. Images (JPG, PNG, GIF, WebP) or Documents (PDF, DOC, DOCX). Max 10MB.') }}</p>
                    @error('file')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

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

                @if ($classes->count())
                    <fieldset>
                        <legend class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">{{ __('Target Classes (leave empty for all)') }}</legend>
                        <div class="space-y-3">
                            @foreach ($classes->groupBy('level_id') as $levelId => $levelClasses)
                                @php $level = $levels->firstWhere('id', $levelId); @endphp
                                @if ($level)
                                    <div>
                                        <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1">{{ $level->name }}</p>
                                        <div class="flex flex-wrap gap-3">
                                            @foreach ($levelClasses as $class)
                                                <flux:checkbox name="target_classes[]" :value="$class->id" :label="$class->name" :checked="in_array($class->id, old('target_classes', $notice->target_classes ?? []))" />
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
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
