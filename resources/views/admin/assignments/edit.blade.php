<x-layouts::app :title="__('Edit Assignment')">
    <div class="space-y-6">
        <x-admin-header :title="__('Edit Assignment')" />

        <div class="max-w-2xl rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <form method="POST" action="{{ route('admin.assignments.update', $assignment) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <flux:select name="class_id" :label="__('Class')" required>
                    <option value="">{{ __('Select class...') }}</option>
                    @foreach ($classes as $class)
                        <option value="{{ $class->id }}" @selected(old('class_id', $assignment->class_id) == $class->id)>{{ $class->name }}</option>
                    @endforeach
                </flux:select>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <flux:input :label="__('Session')" :value="$assignment->session?->name ?? '—'" disabled />
                    <flux:input :label="__('Term')" :value="$assignment->term?->name ?? '—'" disabled />
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <flux:select name="week_number" :label="__('Week Number')" required>
                        @for ($i = 1; $i <= 12; $i++)
                            <option value="{{ $i }}" @selected(old('week_number', $assignment->week_number) == $i)>{{ __('Week :num', ['num' => $i]) }}</option>
                        @endfor
                    </flux:select>
                    <flux:input name="due_date" :label="__('Due Date (optional)')" :value="old('due_date', $assignment->due_date?->format('Y-m-d'))" type="date" />
                </div>

                <flux:input name="title" :label="__('Title (optional)')" :value="old('title', $assignment->title)" />

                <flux:textarea name="description" :label="__('Description (optional)')" rows="3">{{ old('description', $assignment->description) }}</flux:textarea>

                @if ($assignment->file_url)
                    <div class="text-sm">
                        <span class="text-zinc-500">{{ __('Current file:') }}</span>
                        <a href="{{ $assignment->file_url }}" target="_blank" class="text-blue-600 hover:underline">{{ __('View file') }}</a>
                    </div>
                @endif

                <div class="flex gap-3">
                    <flux:button variant="primary" type="submit">{{ __('Update Assignment') }}</flux:button>
                    <flux:button variant="ghost" href="{{ route('admin.assignments.index') }}" wire:navigate>{{ __('Cancel') }}</flux:button>
                </div>
            </form>
        </div>
    </div>
</x-layouts::app>
