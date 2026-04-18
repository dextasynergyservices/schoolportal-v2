<x-layouts::app :title="__('Bulk Upload Preview')">
    <div class="space-y-6">
        <x-admin-header :title="__('Bulk Upload Preview')">
            <flux:button variant="subtle" size="sm" href="{{ route('admin.results.bulk') }}" wire:navigate icon="arrow-left">
                {{ __('Back') }}
            </flux:button>
        </x-admin-header>

        <div class="flex flex-wrap gap-4 text-sm">
            <div><span class="text-zinc-500">{{ __('Class:') }}</span> <span class="font-medium">{{ $class->name }}</span></div>
            <div><span class="text-zinc-500">{{ __('Term:') }}</span> <span class="font-medium">{{ $term->name }}</span></div>
        </div>

        {{-- Summary --}}
        <div class="flex gap-4">
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
                <div class="text-2xl font-bold text-green-600">{{ count($matches) }}</div>
                <div class="text-sm text-zinc-500">{{ __('Matched') }}</div>
            </div>
            @if (count($unmatched) > 0)
                <div class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 p-4">
                    <div class="text-2xl font-bold text-red-600">{{ count($unmatched) }}</div>
                    <div class="text-sm text-zinc-500">{{ __('Unmatched') }}</div>
                </div>
            @endif
        </div>

        {{-- Unmatched Files --}}
        @if (count($unmatched) > 0)
            <flux:callout variant="warning" icon="exclamation-triangle">
                <div>
                    <p class="font-medium mb-1">{{ __('These files could not be matched to students:') }}</p>
                    <ul class="list-disc list-inside text-sm">
                        @foreach ($unmatched as $filename)
                            <li>{{ $filename }}</li>
                        @endforeach
                    </ul>
                    <p class="text-sm mt-2">{{ __('Rename files to match student usernames (e.g. john.doe.pdf) and re-upload.') }}</p>
                </div>
            </flux:callout>
        @endif

        {{-- Matched Results --}}
        @if (count($matches) > 0)
            <form method="POST" action="{{ route('admin.results.bulk.store') }}" x-data="{ confirmOpen: false, uploading: false }" data-no-loading>
                @csrf
                <input type="hidden" name="class_id" value="{{ $classId }}">
                <input type="hidden" name="session_id" value="{{ $sessionId }}">
                <input type="hidden" name="term_id" value="{{ $termId }}">

                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('File') }}</flux:table.column>
                        <flux:table.column>{{ __('Matched Student') }}</flux:table.column>
                        <flux:table.column>{{ __('Username') }}</flux:table.column>
                        <flux:table.column>{{ __('Status') }}</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($matches as $i => $match)
                            <flux:table.row>
                                <flux:table.cell class="font-medium">{{ $match['filename'] }}</flux:table.cell>
                                <flux:table.cell>{{ $match['student_name'] }}</flux:table.cell>
                                <flux:table.cell class="text-zinc-500">{{ $match['username'] }}</flux:table.cell>
                                <flux:table.cell>
                                    @if ($match['already_exists'])
                                        <flux:badge color="yellow" size="sm">{{ __('Will Replace') }}</flux:badge>
                                    @else
                                        <flux:badge color="green" size="sm">{{ __('New') }}</flux:badge>
                                    @endif
                                </flux:table.cell>
                            </flux:table.row>
                            <input type="hidden" name="imports[{{ $i }}][student_id]" value="{{ $match['student_id'] }}">
                            <input type="hidden" name="imports[{{ $i }}][temp_path]" value="{{ $match['temp_path'] }}">
                        @endforeach
                    </flux:table.rows>
                </flux:table>

                <div class="mt-4 flex gap-3">
                    <flux:button variant="primary" type="button" x-on:click="confirmOpen = true">
                        {{ __('Upload :count Results', ['count' => count($matches)]) }}
                    </flux:button>
                    <flux:button variant="ghost" href="{{ route('admin.results.bulk') }}" wire:navigate>{{ __('Cancel') }}</flux:button>
                </div>

                {{-- Confirmation modal (no teleport — submit button stays inside form) --}}
                <div x-show="confirmOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" x-on:keydown.escape.window="confirmOpen = false" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" x-cloak>
                    <div class="fixed inset-0 bg-black/50 dark:bg-black/70" x-on:click="confirmOpen = false" aria-hidden="true"></div>
                    <div x-show="confirmOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" x-trap.noscroll="confirmOpen" class="relative w-full max-w-md rounded-xl bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 p-6 shadow-xl">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30 mb-4">
                            <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                        </div>
                        <div class="text-center">
                            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Upload Results') }}</h3>
                            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ __('This will upload :count results. Existing results will be replaced.', ['count' => count($matches)]) }}</p>
                        </div>
                        <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-center">
                            <flux:button variant="ghost" x-on:click="confirmOpen = false" x-bind:disabled="uploading">{{ __('Cancel') }}</flux:button>
                            <flux:button type="submit" variant="primary" x-bind:disabled="uploading" x-on:click="uploading = true" class="w-full sm:w-auto">
                                <span x-show="!uploading">{{ __('Upload Now') }}</span>
                                <span x-show="uploading" x-cloak class="inline-flex items-center gap-2">
                                    <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    {{ __('Uploading...') }}
                                </span>
                            </flux:button>
                        </div>
                    </div>
                </div>
            </form>
        @else
            <flux:callout variant="warning" icon="exclamation-triangle">
                {{ __('No files could be matched to students. Check that filenames match student usernames.') }}
            </flux:callout>
        @endif
    </div>
</x-layouts::app>
