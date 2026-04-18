<x-layouts::app :title="__('Add Assignment')">
    <div class="space-y-6">
        <x-admin-header :title="__('Add Assignment')" />

        <div class="max-w-2xl rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <form method="POST" action="{{ route('admin.assignments.store') }}" class="space-y-6">
                @csrf

                <flux:select name="class_id" :label="__('Class')" required>
                    <option value="">{{ __('Select class...') }}</option>
                    @foreach ($classes as $class)
                        <option value="{{ $class->id }}" @selected(old('class_id') == $class->id)>{{ $class->name }}</option>
                    @endforeach
                </flux:select>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <flux:input name="session_id" type="hidden" :value="$currentSession?->id" />
                    <flux:input :label="__('Session')" :value="$currentSession?->name ?? __('No active session')" disabled />
                    <flux:input name="term_id" type="hidden" :value="$currentTerm?->id" />
                    <flux:input :label="__('Term')" :value="$currentTerm?->name ?? __('No active term')" disabled />
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <flux:select name="week_number" :label="__('Week Number')" required>
                        @for ($i = 1; $i <= 12; $i++)
                            <option value="{{ $i }}" @selected(old('week_number') == $i)>{{ __('Week :num', ['num' => $i]) }}</option>
                        @endfor
                    </flux:select>
                    <flux:input name="due_date" :label="__('Due Date (optional)')" :value="old('due_date')" type="date" />
                </div>

                <flux:input name="title" :label="__('Title (optional)')" :value="old('title')" />

                <flux:textarea name="description" :label="__('Description (optional)')" rows="3">{{ old('description') }}</flux:textarea>

                {{-- Placeholder for Cloudinary upload --}}
                <flux:input name="file_url" :label="__('File URL (Cloudinary)')" :value="old('file_url')" type="url" placeholder="https://res.cloudinary.com/..." />

                <div class="flex gap-3">
                    <flux:button variant="primary" type="submit">{{ __('Create Assignment') }}</flux:button>
                    <flux:button variant="ghost" href="{{ route('admin.assignments.index') }}" wire:navigate>{{ __('Cancel') }}</flux:button>
                </div>
            </form>
        </div>
    </div>
</x-layouts::app>
