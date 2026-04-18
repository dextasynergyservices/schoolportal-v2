<x-layouts::app :title="__('Upload Result')">
    <div class="space-y-6">
        <x-admin-header :title="__('Upload Result')" />

        <div class="max-w-2xl rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <form method="POST" action="{{ route('admin.results.store') }}" class="space-y-6">
                @csrf

                <flux:select name="student_id" :label="__('Student')" required>
                    <option value="">{{ __('Select student...') }}</option>
                    @foreach ($students as $student)
                        <option value="{{ $student->id }}" @selected(old('student_id') == $student->id)>{{ $student->name }} ({{ $student->username }})</option>
                    @endforeach
                </flux:select>

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

                {{-- Placeholder for Cloudinary upload --}}
                <flux:input name="file_url" :label="__('Result PDF URL (Cloudinary)')" :value="old('file_url')" type="url" placeholder="https://res.cloudinary.com/..." required />

                <flux:textarea name="notes" :label="__('Notes (optional)')" rows="2">{{ old('notes') }}</flux:textarea>

                <div class="flex gap-3">
                    <flux:button variant="primary" type="submit">{{ __('Upload Result') }}</flux:button>
                    <flux:button variant="ghost" href="{{ route('admin.results.index') }}" wire:navigate>{{ __('Cancel') }}</flux:button>
                </div>
            </form>
        </div>
    </div>
</x-layouts::app>
