<x-layouts::app :title="__('Subject Assignments')">
    <div class="space-y-6">
        <x-admin-header
            :title="__('Subject Assignments')"
            :description="__('Assign subjects to classes and optionally set subject teachers.')"
        >
            <flux:button variant="ghost" icon="arrow-left" href="{{ route('admin.subjects.index') }}" wire:navigate>
                {{ __('Back to Subjects') }}
            </flux:button>
        </x-admin-header>

        @if (session('success'))
            <flux:callout variant="success" icon="check-circle">{{ session('success') }}</flux:callout>
        @endif
        @if (session('error'))
            <flux:callout variant="danger" icon="x-circle">{{ session('error') }}</flux:callout>
        @endif

        @if ($classes->isEmpty())
            <flux:callout variant="warning" icon="information-circle">
                {{ __('No active classes found. Create classes first before assigning subjects.') }}
            </flux:callout>
        @elseif ($subjects->isEmpty())
            <flux:callout variant="warning" icon="information-circle">
                {{ __('No active subjects found. Add subjects first before making assignments.') }}
            </flux:callout>
        @else
            {{-- Quick Assign Section --}}
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                <h3 class="text-base font-semibold text-zinc-900 dark:text-white mb-4">{{ __('Quick Assign') }}</h3>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-4">{{ __('Select a class and check the subjects to assign all at once.') }}</p>

                <form method="POST" action="{{ route('admin.subjects.quick-assign') }}" x-data="{ classId: '', selectedSubjects: [] }">
                    @csrf
                    <div class="space-y-4">
                        <flux:select name="class_id" :label="__('Class')" x-model="classId" required>
                            <option value="">{{ __('Select a class...') }}</option>
                            @foreach ($classes as $class)
                                <option value="{{ $class->id }}">{{ $class->level?->name }} — {{ $class->name }}</option>
                            @endforeach
                        </flux:select>

                        <div x-show="classId" x-transition>
                            <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">{{ __('Select subjects:') }}</p>
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2">
                                @foreach ($subjects as $subject)
                                    <label class="flex items-center gap-2 rounded-md border border-zinc-200 dark:border-zinc-600 px-3 py-2 text-sm cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                                        <input type="checkbox" name="subject_ids[]" value="{{ $subject->id }}" x-model="selectedSubjects" class="rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500">
                                        <span>{{ $subject->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div x-show="classId && selectedSubjects.length > 0" x-transition>
                            <flux:button variant="primary" type="submit" icon="plus">
                                {{ __('Assign Selected Subjects') }}
                            </flux:button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- Current Assignments by Class --}}
            <div class="space-y-4">
                <h3 class="text-base font-semibold text-zinc-900 dark:text-white">{{ __('Current Assignments') }}</h3>

                @foreach ($classes as $class)
                    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 overflow-hidden">
                        <div class="flex items-center justify-between px-4 py-3 bg-zinc-50 dark:bg-zinc-700/50 border-b border-zinc-200 dark:border-zinc-700">
                            <div class="flex items-center gap-2">
                                <flux:icon name="building-library" class="size-4 text-zinc-400" />
                                <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $class->level?->name }} — {{ $class->name }}</span>
                                <flux:badge color="zinc" size="sm">{{ $class->subjects->count() }} {{ __('subjects') }}</flux:badge>
                            </div>
                        </div>

                        @if ($class->subjects->isEmpty())
                            <div class="px-4 py-6 text-center text-sm text-zinc-500">
                                {{ __('No subjects assigned yet.') }}
                            </div>
                        @else
                            <div class="divide-y divide-zinc-100 dark:divide-zinc-700">
                                @foreach ($class->subjects as $subject)
                                    <div class="flex items-center justify-between px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <flux:icon name="book-open" class="size-4 text-zinc-400" />
                                            <div>
                                                <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $subject->name }}</p>
                                                @if ($subject->short_name)
                                                    <p class="text-xs text-zinc-500">{{ $subject->short_name }}</p>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            @php
                                                $subjectTeacher = $subject->pivot->teacher_id
                                                    ? $teachers->firstWhere('id', $subject->pivot->teacher_id)
                                                    : null;
                                            @endphp
                                            @if ($subjectTeacher)
                                                <span class="text-xs text-zinc-500 flex items-center gap-1">
                                                    <flux:icon name="user" class="size-3" />
                                                    {{ $subjectTeacher->name }}
                                                </span>
                                            @else
                                                <span class="text-xs text-zinc-400">{{ __('No teacher') }}</span>
                                            @endif
                                            <form method="POST" action="{{ route('admin.subjects.remove-assignment') }}" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="class_id" value="{{ $class->id }}">
                                                <input type="hidden" name="subject_id" value="{{ $subject->id }}">
                                                <flux:button variant="subtle" size="xs" icon="x-mark" type="submit" aria-label="{{ __('Remove :subject from :class', ['subject' => $subject->name, 'class' => $class->name]) }}" />
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts::app>
