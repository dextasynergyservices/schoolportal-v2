<x-layouts::app :title="__('Upload Result')">
    <div class="space-y-6">
        <x-admin-header :title="__('Upload Result')">
            <flux:button variant="subtle" size="sm" href="{{ route('teacher.results.index') }}" wire:navigate icon="arrow-left">
                {{ __('Back to Results') }}
            </flux:button>
        </x-admin-header>

        <flux:callout variant="info" icon="information-circle">
            {{ __('Results you upload will be submitted for admin approval before students can view them.') }}
        </flux:callout>

        <div class="max-w-2xl">
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                <form method="POST" action="{{ route('teacher.results.store') }}" class="space-y-4">
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

                    <flux:select name="student_id" :label="__('Student')" required>
                        <option value="">{{ __('Select student...') }}</option>
                        @foreach ($students as $student)
                            <option value="{{ $student->id }}" @selected(old('student_id') == $student->id)>
                                {{ $student->name }} ({{ $student->username }})
                            </option>
                        @endforeach
                    </flux:select>
                    @error('student_id')
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

                    {{-- TODO: Replace with Cloudinary upload component --}}
                    <flux:input name="file_url" :label="__('Result File URL')" type="url" required :value="old('file_url')" placeholder="https://res.cloudinary.com/..." />
                    @error('file_url')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror

                    <flux:textarea name="notes" :label="__('Notes (optional)')" rows="3" :value="old('notes')" placeholder="{{ __('Any additional notes about this result...') }}" />

                    <div class="flex gap-3 pt-2">
                        <flux:button variant="primary" type="submit">{{ __('Submit for Approval') }}</flux:button>
                        <flux:button variant="ghost" href="{{ route('teacher.results.index') }}" wire:navigate>{{ __('Cancel') }}</flux:button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts::app>
