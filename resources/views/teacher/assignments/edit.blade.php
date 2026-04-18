<x-layouts::app :title="__('Edit Assignment')">
    <div class="space-y-6">
        <x-admin-header :title="__('Edit Assignment')">
            <flux:button variant="subtle" size="sm" href="{{ route('teacher.assignments.index') }}" wire:navigate icon="arrow-left">
                {{ __('Back to Assignments') }}
            </flux:button>
        </x-admin-header>

        @if ($assignment->status === 'rejected')
            <flux:callout variant="danger" icon="exclamation-circle">
                {{ __('This assignment was rejected. You can edit and resubmit it.') }}
            </flux:callout>
        @endif

        <div class="max-w-2xl">
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                <form method="POST" action="{{ route('teacher.assignments.update', $assignment) }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <flux:select name="class_id" :label="__('Class')" required>
                        @foreach ($classes as $class)
                            <option value="{{ $class->id }}" @selected(old('class_id', $assignment->class_id) == $class->id)>{{ $class->name }}</option>
                        @endforeach
                    </flux:select>
                    @error('class_id')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <flux:select name="week_number" :label="__('Week')" required>
                            @for ($i = 1; $i <= 12; $i++)
                                <option value="{{ $i }}" @selected(old('week_number', $assignment->week_number) == $i)>{{ __('Week :week', ['week' => $i]) }}</option>
                            @endfor
                        </flux:select>

                        <flux:input name="due_date" :label="__('Due Date (optional)')" type="date" :value="old('due_date', $assignment->due_date?->format('Y-m-d'))" />
                    </div>

                    <flux:input name="title" :label="__('Title (optional)')" :value="old('title', $assignment->title)" />

                    <flux:textarea name="description" :label="__('Description (optional)')" rows="3" :value="old('description', $assignment->description)" />

                    @if ($assignment->file_url)
                        <div class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Current file:') }}
                            <a href="{{ $assignment->file_url }}" target="_blank" rel="noopener noreferrer" class="text-blue-600 dark:text-blue-400 underline">
                                {{ __('View file') }}
                            </a>
                        </div>
                    @endif

                    <div class="flex gap-3 pt-2">
                        <flux:button variant="primary" type="submit">{{ __('Update Assignment') }}</flux:button>
                        <flux:button variant="ghost" href="{{ route('teacher.assignments.index') }}" wire:navigate>{{ __('Cancel') }}</flux:button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts::app>
