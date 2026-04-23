<x-layouts::app :title="__('Add Assignment')">
    <div class="space-y-6">
        <x-admin-header :title="__('Add Assignment')" />

        <div class="max-w-2xl rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <form method="POST" action="{{ route('admin.assignments.store') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf

                {{-- Cascading Level → Class selector --}}
                @livewire('class-selector', ['classId' => old('class_id') ? (int) old('class_id') : null])

                <input type="hidden" name="session_id" value="{{ $currentSession?->id }}">
                <input type="hidden" name="term_id" value="{{ $currentTerm?->id }}">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <flux:input :label="__('Session')" :value="$currentSession?->name ?? __('No active session')" disabled />
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

                <div>
                    <label for="assignment_file" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('Upload File (optional)') }}</label>
                    <input type="file" name="assignment_file" id="assignment_file" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.webp"
                        class="block w-full text-sm text-zinc-500 dark:text-zinc-400
                            file:mr-4 file:py-2 file:px-4
                            file:rounded-lg file:border-0
                            file:text-sm file:font-medium
                            file:bg-zinc-100 file:text-zinc-700
                            dark:file:bg-zinc-700 dark:file:text-zinc-200
                            hover:file:bg-zinc-200 dark:hover:file:bg-zinc-600
                            file:cursor-pointer" />
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('PDF, Word, PowerPoint, Excel, or image files. Max 10MB.') }}</p>
                    @error('assignment_file')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="relative flex items-center gap-3">
                    <div class="flex-grow border-t border-zinc-200 dark:border-zinc-700"></div>
                    <span class="text-xs text-zinc-500 dark:text-zinc-400 uppercase">{{ __('or') }}</span>
                    <div class="flex-grow border-t border-zinc-200 dark:border-zinc-700"></div>
                </div>

                <flux:input name="file_url" :label="__('Link to File (optional)')" :value="old('file_url')" type="url" placeholder="https://example.com/assignment.pdf" />
                @error('file_url')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror

                <div class="flex gap-3">
                    <flux:button variant="primary" type="submit">{{ __('Create Assignment') }}</flux:button>
                    <flux:button variant="ghost" href="{{ route('admin.assignments.index') }}" wire:navigate>{{ __('Cancel') }}</flux:button>
                </div>
            </form>
        </div>
    </div>
</x-layouts::app>
