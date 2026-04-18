<x-layouts::app :title="__('Edit Class')">
    <div class="space-y-6">
        <x-admin-header :title="__('Edit Class: :name', ['name' => $class->name])" />

        <div class="max-w-xl rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <form method="POST" action="{{ route('admin.classes.update', $class) }}" class="space-y-6">
                @csrf
                @method('PUT')
                <flux:input name="name" :label="__('Class Name')" :value="old('name', $class->name)" required />

                <flux:select name="level_id" :label="__('School Level')" required>
                    @foreach ($levels as $level)
                        <option value="{{ $level->id }}" @selected(old('level_id', $class->level_id) == $level->id)>{{ $level->name }}</option>
                    @endforeach
                </flux:select>

                <flux:select name="teacher_id" :label="__('Assigned Teacher')">
                    <option value="">{{ __('No teacher assigned') }}</option>
                    @foreach ($teachers as $teacher)
                        <option value="{{ $teacher->id }}" @selected(old('teacher_id', $class->teacher_id) == $teacher->id)>{{ $teacher->name }}</option>
                    @endforeach
                </flux:select>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input name="capacity" :label="__('Capacity')" :value="old('capacity', $class->capacity)" type="number" min="1" />
                    <flux:input name="sort_order" :label="__('Sort Order')" :value="old('sort_order', $class->sort_order)" type="number" min="0" />
                </div>

                <flux:switch name="is_active" :label="__('Active')" :checked="old('is_active', $class->is_active)" value="1" />

                <div class="flex gap-3">
                    <flux:button variant="primary" type="submit">{{ __('Update Class') }}</flux:button>
                    <flux:button variant="ghost" href="{{ route('admin.classes.index') }}" wire:navigate>{{ __('Cancel') }}</flux:button>
                </div>
            </form>
        </div>
    </div>
</x-layouts::app>
