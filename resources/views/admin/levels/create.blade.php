<x-layouts::app :title="__('Add School Level')">
    <div class="space-y-6">
        <x-admin-header :title="__('Add School Level')" />

        <div class="max-w-xl rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <form method="POST" action="{{ route('admin.levels.store') }}" class="space-y-6">
                @csrf
                <flux:input name="name" :label="__('Level Name')" :value="old('name')" placeholder="e.g. Nursery, Primary, Secondary" required />
                <flux:input name="sort_order" :label="__('Sort Order')" :value="old('sort_order', 0)" type="number" min="0" />

                <div class="flex gap-3">
                    <flux:button variant="primary" type="submit">{{ __('Add Level') }}</flux:button>
                    <flux:button variant="ghost" href="{{ route('admin.levels.index') }}" wire:navigate>{{ __('Cancel') }}</flux:button>
                </div>
            </form>
        </div>
    </div>
</x-layouts::app>
