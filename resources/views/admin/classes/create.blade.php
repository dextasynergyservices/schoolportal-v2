<x-layouts::app :title="__('Add Class')">
    <div class="space-y-6">
        <x-admin-header :title="__('Add Class')" />

        <div class="max-w-xl rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <form method="POST" action="{{ route('admin.classes.store') }}" class="space-y-6">
                @csrf
                <flux:input name="name" :label="__('Class Name')" :value="old('name')" placeholder="e.g. Primary 3, Nursery 1" required />

                <flux:select name="level_id" :label="__('School Level')" required>
                    <option value="">{{ __('Select level...') }}</option>
                    @foreach ($levels as $level)
                        <option value="{{ $level->id }}" @selected(old('level_id') == $level->id)>{{ $level->name }}</option>
                    @endforeach
                </flux:select>

                <flux:select name="teacher_id" :label="__('Assigned Teacher (optional)')">
                    <option value="">{{ __('No teacher assigned') }}</option>
                    @foreach ($teachers as $teacher)
                        <option value="{{ $teacher->id }}" @selected(old('teacher_id') == $teacher->id)>{{ $teacher->name }}</option>
                    @endforeach
                </flux:select>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input name="capacity" :label="__('Capacity')" :value="old('capacity')" type="number" min="1" placeholder="Optional" />
                    <flux:input name="sort_order" :label="__('Sort Order')" :value="old('sort_order', 0)" type="number" min="0" />
                </div>

                <div class="flex gap-3">
                    <flux:button variant="primary" type="submit">{{ __('Add Class') }}</flux:button>
                    <flux:button variant="ghost" href="{{ route('admin.classes.index') }}" wire:navigate>{{ __('Cancel') }}</flux:button>
                </div>
            </form>
        </div>
    </div>
</x-layouts::app>
