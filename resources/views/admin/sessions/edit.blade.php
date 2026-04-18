<x-layouts::app :title="__('Edit Session')">
    <div class="space-y-6">
        <x-admin-header :title="__('Edit Session: :name', ['name' => $session->name])" />

        <div class="max-w-xl rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <form method="POST" action="{{ route('admin.sessions.update', $session) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <flux:input name="name" :label="__('Session Name')" :value="old('name', $session->name)" required />
                <div class="grid grid-cols-2 gap-4">
                    <flux:input name="start_date" :label="__('Start Date')" :value="old('start_date', $session->start_date?->format('Y-m-d'))" type="date" required />
                    <flux:input name="end_date" :label="__('End Date')" :value="old('end_date', $session->end_date?->format('Y-m-d'))" type="date" required />
                </div>

                <div class="flex gap-3">
                    <flux:button variant="primary" type="submit">{{ __('Update Session') }}</flux:button>
                    <flux:button variant="ghost" href="{{ route('admin.sessions.index') }}" wire:navigate>{{ __('Cancel') }}</flux:button>
                </div>
            </form>
        </div>

        {{-- Terms --}}
        @if ($session->terms->count())
            <div class="max-w-xl">
                <flux:heading size="lg" class="mb-4">{{ __('Terms') }}</flux:heading>
                <div class="space-y-3">
                    @foreach ($session->terms as $term)
                        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
                            <form method="POST" action="{{ route('admin.terms.update', $term) }}" class="space-y-4">
                                @csrf
                                @method('PUT')

                                <flux:input name="name" :label="__('Term Name')" :value="old('name', $term->name)" required />
                                <div class="grid grid-cols-2 gap-4">
                                    <flux:input name="start_date" :label="__('Start Date')" :value="old('start_date', $term->start_date?->format('Y-m-d'))" type="date" />
                                    <flux:input name="end_date" :label="__('End Date')" :value="old('end_date', $term->end_date?->format('Y-m-d'))" type="date" />
                                </div>
                                <flux:button variant="filled" size="sm" type="submit">{{ __('Update Term') }}</flux:button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-layouts::app>
