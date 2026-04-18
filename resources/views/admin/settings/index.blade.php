<x-layouts::app :title="__('School Settings')">
    <div class="space-y-6">
        <x-admin-header :title="__('School Settings')" />

        @if (session('success'))
            <flux:callout variant="success" icon="check-circle">{{ session('success') }}</flux:callout>
        @endif

        <div class="max-w-2xl space-y-8">
            {{-- School Information --}}
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                <flux:heading size="sm" class="mb-4">{{ __('School Information') }}</flux:heading>
                <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <flux:input name="name" :label="__('School Name')" :value="old('name', $school->name)" required />

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <flux:input name="email" :label="__('Email')" :value="old('email', $school->email)" type="email" required />
                        <flux:input name="phone" :label="__('Phone')" :value="old('phone', $school->phone)" />
                    </div>

                    <flux:textarea name="address" :label="__('Address')" rows="2">{{ old('address', $school->address) }}</flux:textarea>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <flux:input name="city" :label="__('City')" :value="old('city', $school->city)" />
                        <flux:input name="state" :label="__('State')" :value="old('state', $school->state)" />
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <flux:input name="website" :label="__('Website')" :value="old('website', $school->website)" type="url" />
                        <flux:input name="motto" :label="__('Motto')" :value="old('motto', $school->motto)" />
                    </div>

                    <flux:button variant="primary" type="submit">{{ __('Save Information') }}</flux:button>
                </form>
            </div>

            {{-- Branding --}}
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                <flux:heading size="sm" class="mb-4">{{ __('Branding Colors') }}</flux:heading>
                <form method="POST" action="{{ route('admin.settings.branding') }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('Primary Color') }}</label>
                            <input type="color" name="primary_color" value="{{ old('primary_color', $school->settings['branding']['primary_color'] ?? '#4F46E5') }}" class="h-10 w-full rounded border border-zinc-300 cursor-pointer" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('Secondary Color') }}</label>
                            <input type="color" name="secondary_color" value="{{ old('secondary_color', $school->settings['branding']['secondary_color'] ?? '#F59E0B') }}" class="h-10 w-full rounded border border-zinc-300 cursor-pointer" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('Accent Color') }}</label>
                            <input type="color" name="accent_color" value="{{ old('accent_color', $school->settings['branding']['accent_color'] ?? '#10B981') }}" class="h-10 w-full rounded border border-zinc-300 cursor-pointer" />
                        </div>
                    </div>

                    <flux:button variant="primary" type="submit">{{ __('Save Branding') }}</flux:button>
                </form>
            </div>

            {{-- Portal Settings --}}
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                <flux:heading size="sm" class="mb-4">{{ __('Portal Settings') }}</flux:heading>
                <form method="POST" action="{{ route('admin.settings.portal') }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    @php
                        $portalSettings = $school->settings['portal'] ?? [];
                    @endphp

                    <div class="space-y-3">
                        <flux:switch name="enable_parent_portal" :label="__('Enable Parent Portal')" :checked="old('enable_parent_portal', $portalSettings['enable_parent_portal'] ?? true)" value="1" />
                        <flux:switch name="enable_quiz_generator" :label="__('Enable AI Quiz Generator')" :checked="old('enable_quiz_generator', $portalSettings['enable_quiz_generator'] ?? true)" value="1" />
                        <flux:switch name="enable_game_generator" :label="__('Enable AI Game Generator')" :checked="old('enable_game_generator', $portalSettings['enable_game_generator'] ?? true)" value="1" />
                        <flux:switch name="enable_teacher_approval" :label="__('Require Teacher Approval')" :checked="old('enable_teacher_approval', $portalSettings['enable_teacher_approval'] ?? true)" value="1" />
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <flux:input name="session_timeout_minutes" :label="__('Session Timeout (minutes)')" :value="old('session_timeout_minutes', $portalSettings['session_timeout_minutes'] ?? 30)" type="number" min="5" max="120" required />
                        <flux:input name="max_file_upload_mb" :label="__('Max File Upload (MB)')" :value="old('max_file_upload_mb', $portalSettings['max_file_upload_mb'] ?? 10)" type="number" min="1" max="50" required />
                    </div>

                    <flux:button variant="primary" type="submit">{{ __('Save Portal Settings') }}</flux:button>
                </form>
            </div>

            {{-- School Domain Info (read-only) --}}
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                <flux:heading size="sm" class="mb-4">{{ __('Domain') }}</flux:heading>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-zinc-500">{{ __('Custom Domain') }}</dt>
                        <dd class="font-medium">{{ $school->custom_domain ?? __('Not configured') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-zinc-500">{{ __('School Slug') }}</dt>
                        <dd class="font-medium">{{ $school->slug }}</dd>
                    </div>
                </dl>
                <p class="text-xs text-zinc-500 mt-3">{{ __('Contact platform administrator to change domain settings.') }}</p>
            </div>
        </div>
    </div>
</x-layouts::app>
