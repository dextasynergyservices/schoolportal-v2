<x-layouts::app :title="__('Import Preview')">
    <div class="space-y-6">
        <x-admin-header :title="__('Import Preview')">
            <flux:button variant="subtle" size="sm" href="{{ route('teacher.students.import') }}" wire:navigate icon="arrow-left">
                {{ __('Back') }}
            </flux:button>
        </x-admin-header>

        {{-- Summary --}}
        <div class="flex gap-4">
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
                <div class="text-2xl font-bold text-green-600">{{ $validCount }}</div>
                <div class="text-sm text-zinc-500">{{ __('Valid rows') }}</div>
            </div>
            @if ($errorCount > 0)
                <div class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 p-4">
                    <div class="text-2xl font-bold text-red-600">{{ $errorCount }}</div>
                    <div class="text-sm text-zinc-500">{{ __('Rows with errors') }}</div>
                </div>
            @endif
        </div>

        {{-- Errors --}}
        @if (count($errors) > 0)
            <flux:callout variant="danger" icon="exclamation-triangle">
                <div>
                    <p class="font-medium mb-2">{{ __('The following errors were found:') }}</p>
                    <ul class="list-disc list-inside text-sm space-y-1">
                        @foreach ($errors as $error)
                            <li>{{ __('Row :line: :message', ['line' => $error['line'], 'message' => $error['message']]) }}</li>
                        @endforeach
                    </ul>
                </div>
            </flux:callout>
        @endif

        {{-- Preview Table --}}
        @if (count($rows) > 0)
            <flux:table>
                <flux:table.columns>
                    <flux:table.column class="w-10">{{ __('#') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                    <flux:table.column>{{ __('Name') }}</flux:table.column>
                    <flux:table.column>{{ __('Username') }}</flux:table.column>
                    <flux:table.column>{{ __('Gender') }}</flux:table.column>
                    <flux:table.column>{{ __('Class') }}</flux:table.column>
                    <flux:table.column>{{ __('Admission No.') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($rows as $row)
                        <flux:table.row class="{{ !$row['_valid'] ? 'bg-red-50 dark:bg-red-900/10' : '' }}">
                            <flux:table.cell class="text-zinc-500">{{ $row['_line'] }}</flux:table.cell>
                            <flux:table.cell>
                                @if ($row['_valid'])
                                    <flux:badge color="green" size="sm">{{ __('OK') }}</flux:badge>
                                @else
                                    <flux:badge color="red" size="sm">{{ __('Error') }}</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell class="font-medium">{{ $row['name'] ?? '' }}</flux:table.cell>
                            <flux:table.cell>{{ $row['username'] ?? '' }}</flux:table.cell>
                            <flux:table.cell>{{ ucfirst($row['gender'] ?? '') }}</flux:table.cell>
                            <flux:table.cell>{{ $row['class'] ?? '' }}</flux:table.cell>
                            <flux:table.cell>{{ $row['admission_number'] ?? '—' }}</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif

        {{-- Import Action --}}
        @if ($validCount > 0)
            <div class="flex gap-3">
                <x-confirm-action
                    action="{{ route('teacher.students.import.store') }}"
                    :title="__('Import Students')"
                    :message="__('This will import :count students into the system. This action cannot be undone.', ['count' => $validCount])"
                    :confirmLabel="__('Import Now')"
                    :buttonLabel="__('Import :count Valid Students', ['count' => $validCount])"
                    iconColor="green"
                >
                    <input type="hidden" name="temp_path" value="{{ $tempPath }}">
                    <input type="hidden" name="default_password" value="{{ $defaultPassword }}">
                </x-confirm-action>
                <flux:button variant="ghost" href="{{ route('teacher.students.import') }}" wire:navigate>{{ __('Cancel') }}</flux:button>
            </div>
            @if ($errorCount > 0)
                <p class="text-sm text-zinc-500">{{ __('Rows with errors will be skipped.') }}</p>
            @endif
        @else
            <flux:callout variant="warning" icon="exclamation-triangle">
                {{ __('No valid rows to import. Please fix the errors and upload again.') }}
            </flux:callout>
        @endif
    </div>
</x-layouts::app>
