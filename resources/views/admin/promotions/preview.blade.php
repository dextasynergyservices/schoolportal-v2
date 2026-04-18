<x-layouts::app :title="__('Preview Promotion')">
    <div class="space-y-6">
        <x-admin-header :title="__('Preview Promotion')">
            <flux:button variant="subtle" size="sm" href="{{ route('admin.promotions.index') }}" wire:navigate icon="arrow-left">
                {{ __('Back') }}
            </flux:button>
        </x-admin-header>

        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <div class="flex flex-wrap gap-6 mb-6 text-sm">
                <div>
                    <span class="text-zinc-500">{{ __('From:') }}</span>
                    <span class="font-medium">{{ $fromClass->name }}</span>
                </div>
                <div>
                    <span class="text-zinc-500">{{ __('To:') }}</span>
                    <span class="font-medium">{{ $toClass->name }}</span>
                </div>
                <div>
                    <span class="text-zinc-500">{{ __('Session:') }}</span>
                    <span class="font-medium">{{ $toSession->name }}</span>
                </div>
                <div>
                    <span class="text-zinc-500">{{ __('Students:') }}</span>
                    <span class="font-medium">{{ $students->count() }}</span>
                </div>
            </div>

            @if ($students->count())
                <form method="POST" action="{{ route('admin.promotions.store') }}" x-data="{ confirmOpen: false, promoting: false }" data-no-loading>
                    @csrf
                    <input type="hidden" name="from_class_id" value="{{ $fromClass->id }}">
                    <input type="hidden" name="to_class_id" value="{{ $toClass->id }}">
                    <input type="hidden" name="from_session_id" value="{{ $currentSession?->id }}">
                    <input type="hidden" name="to_session_id" value="{{ $toSession->id }}">

                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column class="w-10">
                                <input type="checkbox" checked onclick="document.querySelectorAll('.student-check').forEach(c => c.checked = this.checked)" />
                            </flux:table.column>
                            <flux:table.column>{{ __('Name') }}</flux:table.column>
                            <flux:table.column>{{ __('Username') }}</flux:table.column>
                            <flux:table.column>{{ __('Gender') }}</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach ($students as $profile)
                                <flux:table.row>
                                    <flux:table.cell>
                                        <input type="checkbox" name="student_ids[]" value="{{ $profile->user_id }}" checked class="student-check rounded border-zinc-300" />
                                    </flux:table.cell>
                                    <flux:table.cell class="font-medium">{{ $profile->user?->name }}</flux:table.cell>
                                    <flux:table.cell class="text-zinc-500">{{ $profile->user?->username }}</flux:table.cell>
                                    <flux:table.cell>{{ $profile->user?->gender ? ucfirst($profile->user->gender) : '—' }}</flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>

                    <div class="mt-4 flex gap-3">
                        <flux:button variant="primary" type="button" x-on:click="confirmOpen = true">{{ __('Promote Selected Students') }}</flux:button>
                        <flux:button variant="ghost" href="{{ route('admin.promotions.index') }}" wire:navigate>{{ __('Cancel') }}</flux:button>
                    </div>

                    {{-- Confirmation modal --}}
                    <div x-show="confirmOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" x-on:keydown.escape.window="confirmOpen = false" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" x-cloak>
                        <div class="fixed inset-0 bg-black/50 dark:bg-black/70" x-on:click="confirmOpen = false" aria-hidden="true"></div>
                        <div x-show="confirmOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" x-trap.noscroll="confirmOpen" class="relative w-full max-w-md rounded-xl bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 p-6 shadow-xl">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/30 mb-4">
                                <svg class="h-6 w-6 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                            </div>
                            <div class="text-center">
                                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Promote Students') }}</h3>
                                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ __('This will promote :count students from :from to :to. This action cannot be undone.', ['count' => $students->count(), 'from' => $fromClass->name, 'to' => $toClass->name]) }}</p>
                            </div>
                            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-center">
                                <flux:button variant="ghost" x-on:click="confirmOpen = false" x-bind:disabled="promoting">{{ __('Cancel') }}</flux:button>
                                <flux:button type="submit" variant="primary" x-bind:disabled="promoting" x-on:click="promoting = true" class="w-full sm:w-auto">
                                    <span x-show="!promoting">{{ __('Promote Now') }}</span>
                                    <span x-show="promoting" x-cloak class="inline-flex items-center gap-2">
                                        <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                        {{ __('Promoting...') }}
                                    </span>
                                </flux:button>
                            </div>
                        </div>
                    </div>
                </form>
            @else
                <div class="text-center py-8 text-zinc-500">
                    {{ __('No students found in :class.', ['class' => $fromClass->name]) }}
                </div>
            @endif
        </div>
    </div>
</x-layouts::app>
