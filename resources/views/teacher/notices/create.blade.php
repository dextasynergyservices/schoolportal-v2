<x-layouts::app :title="__('Post Notice')">
    <div class="space-y-6">
        <x-admin-header :title="__('Post Notice')">
            <flux:button variant="subtle" size="sm" href="{{ route('teacher.notices.index') }}" wire:navigate icon="arrow-left">
                {{ __('Back to Notices') }}
            </flux:button>
        </x-admin-header>

        <flux:callout variant="info" icon="information-circle">
            {{ __('Notices you post will be submitted for admin approval before they become visible.') }}
        </flux:callout>

        <div class="max-w-2xl">
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                <form method="POST" action="{{ route('teacher.notices.store') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf

                    <flux:input name="title" :label="__('Title')" required :value="old('title')" placeholder="{{ __('e.g. Sports Day Announcement') }}" />
                    @error('title')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror

                    <flux:textarea name="content" :label="__('Content')" required rows="5" :value="old('content')" placeholder="{{ __('Write your notice here...') }}" />
                    @error('content')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror

                    {{-- File Upload --}}
                    <div>
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1 block">{{ __('Attachment (optional)') }}</label>
                        <input type="file" name="file" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx"
                            class="block w-full text-sm text-zinc-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-zinc-100 dark:file:bg-zinc-700 file:text-zinc-700 dark:file:text-zinc-300 hover:file:bg-zinc-200 dark:hover:file:bg-zinc-600" />
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Images (JPG, PNG, GIF, WebP) or Documents (PDF, DOC, DOCX). Max 10MB.') }}</p>
                        @error('file')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    @if ($levels->isNotEmpty())
                        <fieldset>
                            <legend class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">{{ __('Target Levels (optional — leave unchecked for all your levels)') }}</legend>
                            <div class="flex flex-wrap gap-4">
                                @foreach ($levels as $level)
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" name="target_levels[]" value="{{ $level->id }}"
                                            class="rounded border-zinc-300 dark:border-zinc-600"
                                            @checked(is_array(old('target_levels')) && in_array($level->id, old('target_levels')))>
                                        {{ $level->name }}
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    @endif

                    @if ($classes->isNotEmpty())
                        <fieldset>
                            <legend class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">{{ __('Target Classes (optional — leave unchecked for all your classes)') }}</legend>
                            <div class="flex flex-wrap gap-4">
                                @foreach ($classes as $class)
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" name="target_classes[]" value="{{ $class->id }}"
                                            class="rounded border-zinc-300 dark:border-zinc-600"
                                            @checked(is_array(old('target_classes')) && in_array($class->id, old('target_classes')))>
                                        {{ $class->name }}
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    @endif

                    <fieldset>
                        <legend class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">{{ __('Target Audience (optional — leave unchecked for all)') }}</legend>
                        <div class="flex flex-wrap gap-4">
                            @foreach (['student' => __('Students'), 'parent' => __('Parents')] as $value => $label)
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="target_roles[]" value="{{ $value }}"
                                        class="rounded border-zinc-300 dark:border-zinc-600"
                                        @checked(is_array(old('target_roles')) && in_array($value, old('target_roles')))>
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <flux:input name="expires_at" :label="__('Expires On (optional)')" type="date" :value="old('expires_at')" />

                    <div class="flex gap-3 pt-2">
                        <flux:button variant="primary" type="submit">{{ __('Submit for Approval') }}</flux:button>
                        <flux:button variant="ghost" href="{{ route('teacher.notices.index') }}" wire:navigate>{{ __('Cancel') }}</flux:button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts::app>
