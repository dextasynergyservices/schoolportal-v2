<x-layouts::app :title="__('Upload Assignment')">
    <div class="space-y-6">
        <x-admin-header :title="__('Upload Assignment')">
            <flux:button variant="subtle" size="sm" href="{{ route('teacher.assignments.index') }}" wire:navigate icon="arrow-left">
                {{ __('Back to Assignments') }}
            </flux:button>
        </x-admin-header>

        <flux:callout variant="info" icon="information-circle">
            {{ __('Assignments you upload will be submitted for admin approval before students can view them.') }}
        </flux:callout>

        <div class="max-w-2xl">
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                <form method="POST" action="{{ route('teacher.assignments.store') }}" class="space-y-4">
                    @csrf

                    <flux:select name="class_id" :label="__('Class')" required>
                        <option value="">{{ __('Select class...') }}</option>
                        @foreach ($classes as $class)
                            <option value="{{ $class->id }}" @selected(old('class_id') == $class->id)>{{ $class->name }}</option>
                        @endforeach
                    </flux:select>
                    @error('class_id')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <input type="hidden" name="session_id" value="{{ $currentSession?->id }}">
                            <flux:input :label="__('Session')" :value="$currentSession?->name ?? __('No active session')" disabled />
                        </div>
                        <div>
                            <input type="hidden" name="term_id" value="{{ $currentTerm?->id }}">
                            <flux:input :label="__('Term')" :value="$currentTerm?->name ?? __('No active term')" disabled />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <flux:select name="week_number" :label="__('Week')" required>
                            <option value="">{{ __('Select week...') }}</option>
                            @for ($i = 1; $i <= 12; $i++)
                                <option value="{{ $i }}" @selected(old('week_number') == $i)>{{ __('Week :week', ['week' => $i]) }}</option>
                            @endfor
                        </flux:select>

                        <flux:input name="due_date" :label="__('Due Date (optional)')" type="date" :value="old('due_date')" />
                    </div>
                    @error('week_number')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror

                    <flux:input name="title" :label="__('Title (optional)')" :value="old('title')" placeholder="{{ __('e.g. Week 3 Mathematics Assignment') }}" />

                    <flux:textarea name="description" :label="__('Description (optional)')" rows="3" :value="old('description')" placeholder="{{ __('Instructions or details about this assignment...') }}" />

                    {{-- TODO: Replace with Cloudinary upload component --}}
                    <flux:input name="file_url" :label="__('Assignment File URL (optional)')" type="url" :value="old('file_url')" placeholder="https://res.cloudinary.com/..." />

                    <div class="flex gap-3 pt-2">
                        <flux:button variant="primary" type="submit">{{ __('Submit for Approval') }}</flux:button>
                        <flux:button variant="ghost" href="{{ route('teacher.assignments.index') }}" wire:navigate>{{ __('Cancel') }}</flux:button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts::app>
