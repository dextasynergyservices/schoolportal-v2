<x-layouts::app :title="__('Import Students')">
    <div class="space-y-6">
        <x-admin-header :title="__('Import Students from CSV')">
            <flux:button variant="subtle" size="sm" href="{{ route('admin.students.index') }}" wire:navigate icon="arrow-left">
                {{ __('Back to Students') }}
            </flux:button>
        </x-admin-header>

        @if (session('error'))
            <flux:callout variant="danger" icon="exclamation-triangle">{{ session('error') }}</flux:callout>
        @endif

        <div class="max-w-2xl space-y-6">
            {{-- Instructions --}}
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                <flux:heading size="sm" class="mb-3">{{ __('Instructions') }}</flux:heading>
                <ul class="text-sm text-zinc-600 dark:text-zinc-400 space-y-1 list-disc list-inside">
                    <li>{{ __('Download the CSV template and fill in student data.') }}</li>
                    <li>{{ __('Required columns: name, username, gender, class') }}</li>
                    <li>{{ __('Optional columns: admission_number, date_of_birth, address, blood_group') }}</li>
                    <li>{{ __('Gender must be: male, female, or other') }}</li>
                    <li>{{ __('Class name must match exactly (e.g. "Primary 1", "Nursery 2")') }}</li>
                    <li>{{ __('All imported students will use the default password you set below.') }}</li>
                </ul>
                <div class="mt-4">
                    <flux:button variant="subtle" size="sm" href="{{ route('admin.students.import.template') }}" icon="arrow-down-tray">
                        {{ __('Download Template') }}
                    </flux:button>
                </div>
            </div>

            {{-- Upload Form --}}
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                <flux:heading size="sm" class="mb-4">{{ __('Upload CSV') }}</flux:heading>
                <form method="POST" action="{{ route('admin.students.import.preview') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('CSV File') }}</label>
                        <input type="file" name="csv_file" accept=".csv,.txt" required class="block w-full text-sm text-zinc-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-medium file:bg-zinc-100 file:text-zinc-700 hover:file:bg-zinc-200 dark:file:bg-zinc-700 dark:file:text-zinc-300" />
                        @error('csv_file')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <x-password-input name="default_password" :label="__('Default Password for All Students')" required :description="__('Students will be required to change this on first login.')" />

                    <flux:button variant="primary" type="submit">{{ __('Upload & Preview') }}</flux:button>
                </form>
            </div>
        </div>
    </div>
</x-layouts::app>
