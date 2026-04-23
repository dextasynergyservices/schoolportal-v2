<x-layouts::app :title="__('Upload Result')">
    <div class="space-y-6">
        <x-admin-header :title="__('Upload Result')" />

        <div class="max-w-2xl rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <form method="POST" action="{{ route('admin.results.store') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf

                {{-- Cascading Level → Class → Student selector --}}
                @livewire('student-selector', ['studentId' => old('student_id') ? (int) old('student_id') : null, 'classId' => old('class_id') ? (int) old('class_id') : null])

                <input type="hidden" name="session_id" value="{{ $currentSession?->id }}">
                <input type="hidden" name="term_id" value="{{ $currentTerm?->id }}">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <flux:input :label="__('Session')" :value="$currentSession?->name ?? __('No active session')" disabled />
                    <flux:input :label="__('Term')" :value="$currentTerm?->name ?? __('No active term')" disabled />
                </div>

                <div>
                    <label for="result_file" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('Result PDF') }} <span class="text-red-500">*</span></label>
                    <input type="file" name="result_file" id="result_file" accept=".pdf" required
                        class="block w-full text-sm text-zinc-500 dark:text-zinc-400
                            file:mr-4 file:py-2 file:px-4
                            file:rounded-lg file:border-0
                            file:text-sm file:font-medium
                            file:bg-zinc-100 file:text-zinc-700
                            dark:file:bg-zinc-700 dark:file:text-zinc-200
                            hover:file:bg-zinc-200 dark:hover:file:bg-zinc-600
                            file:cursor-pointer" />
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('PDF files only, max 10MB') }}</p>
                    @error('result_file')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <flux:textarea name="notes" :label="__('Notes (optional)')" rows="2">{{ old('notes') }}</flux:textarea>

                <div class="flex gap-3">
                    <flux:button variant="primary" type="submit">{{ __('Upload Result') }}</flux:button>
                    <flux:button variant="ghost" href="{{ route('admin.results.index') }}" wire:navigate>{{ __('Cancel') }}</flux:button>
                </div>
            </form>
        </div>
    </div>
</x-layouts::app>
