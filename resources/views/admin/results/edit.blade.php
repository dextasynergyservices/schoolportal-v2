<x-layouts::app :title="__('Replace Result')">
    <div class="space-y-6">
        <x-admin-header :title="__('Replace Result')">
            <flux:button variant="subtle" size="sm" href="{{ route('admin.results.show', $result) }}" wire:navigate icon="arrow-left">
                {{ __('Back to Result') }}
            </flux:button>
        </x-admin-header>

        @if (session('error'))
            <flux:callout variant="danger" icon="exclamation-circle">{{ session('error') }}</flux:callout>
        @endif

        {{-- Current result info (read-only) --}}
        <div class="max-w-2xl rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900 p-6">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-400 dark:text-zinc-500 mb-4">{{ __('Current Result') }}</p>
            <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                <div>
                    <dt class="text-zinc-500">{{ __('Student') }}</dt>
                    <dd class="font-medium">{{ $result->student?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-zinc-500">{{ __('Class') }}</dt>
                    <dd class="font-medium">{{ $result->class?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-zinc-500">{{ __('Session') }}</dt>
                    <dd class="font-medium">{{ $result->session?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-zinc-500">{{ __('Term') }}</dt>
                    <dd class="font-medium">{{ $result->term?->name ?? '—' }}</dd>
                </div>
            </dl>
            @if ($result->file_url)
                <div class="mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                    <flux:button variant="subtle" size="sm" href="{{ $result->file_url }}" target="_blank" icon="document-text">
                        {{ __('View Current PDF') }}
                    </flux:button>
                </div>
            @endif
        </div>

        {{-- Replacement form --}}
        <div class="max-w-2xl rounded-lg border border-amber-200 dark:border-amber-800 bg-white dark:bg-zinc-800 p-6">
            <p class="text-xs font-semibold uppercase tracking-wide text-amber-600 dark:text-amber-400 mb-4">{{ __('Upload Replacement') }}</p>

            <form method="POST" action="{{ route('admin.results.update', $result) }}" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @method('PUT')

                <div>
                    <label for="result_file" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                        {{ __('New Result PDF') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="file" name="result_file" id="result_file" accept=".pdf" required
                        class="block w-full text-sm text-zinc-500 dark:text-zinc-400
                            file:mr-4 file:py-2 file:px-4
                            file:rounded-lg file:border-0
                            file:text-sm file:font-medium
                            file:bg-zinc-100 file:text-zinc-700
                            dark:file:bg-zinc-700 dark:file:text-zinc-200
                            hover:file:bg-zinc-200 dark:hover:file:bg-zinc-600
                            file:cursor-pointer" />
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('PDF files only, max 10MB') }}</p>
                    @error('result_file')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <flux:textarea
                        name="replacement_reason"
                        :label="__('Reason for Replacement')"
                        :placeholder="__('e.g. Wrong student was selected, scores were entered incorrectly...')"
                        :value="old('replacement_reason')"
                        rows="3"
                        required
                    />
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Minimum 10 characters. This is recorded in the audit log.') }}</p>
                    @error('replacement_reason')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <flux:callout variant="warning" icon="exclamation-triangle" class="text-sm">
                    {{ __('The current PDF will be permanently deleted and replaced. This action is logged.') }}
                </flux:callout>

                <div class="flex items-center gap-3">
                    <flux:button type="submit" variant="danger">
                        {{ __('Replace Result') }}
                    </flux:button>
                    <flux:button variant="subtle" href="{{ route('admin.results.show', $result) }}" wire:navigate>
                        {{ __('Cancel') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </div>
</x-layouts::app>
