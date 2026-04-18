<x-layouts::app :title="__('Edit Level')">
    <div class="space-y-6">
        <x-admin-header :title="__('Edit Level: :name', ['name' => $level->name])" />

        <div class="max-w-xl rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <form method="POST" action="{{ route('admin.levels.update', $level) }}" class="space-y-6">
                @csrf
                @method('PUT')
                <flux:input name="name" :label="__('Level Name')" :value="old('name', $level->name)" required />
                <flux:input name="sort_order" :label="__('Sort Order')" :value="old('sort_order', $level->sort_order)" type="number" min="0" />
                <flux:switch name="is_active" :label="__('Active')" :checked="old('is_active', $level->is_active)" value="1" />

                <div class="flex gap-3">
                    <flux:button variant="primary" type="submit">{{ __('Update Level') }}</flux:button>
                    <flux:button variant="ghost" href="{{ route('admin.levels.index') }}" wire:navigate>{{ __('Cancel') }}</flux:button>
                </div>
            </form>
        </div>
    </div>
</x-layouts::app>
