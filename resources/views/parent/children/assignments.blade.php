<x-layouts::app :title="__(':name — Assignments', ['name' => $child->name])">
    <div class="space-y-6">
        {{-- Breadcrumb --}}
        <div class="flex items-center gap-2 flex-wrap">
            <flux:link href="{{ route('parent.dashboard') }}" wire:navigate class="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">
                {{ __('Dashboard') }}
            </flux:link>
            <flux:icon.chevron-right class="w-3 h-3 text-zinc-400" />
            <flux:link href="{{ route('parent.children.show', $child) }}" wire:navigate class="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">
                {{ $child->name }}
            </flux:link>
            <flux:icon.chevron-right class="w-3 h-3 text-zinc-400" />
            <flux:text class="text-sm">{{ __('Assignments') }}</flux:text>
        </div>

        <x-admin-header
            :title="__(':name\'s Assignments', ['name' => $child->name])"
            :description="$child->studentProfile?->class?->name"
        />

        {{-- Filters --}}
        <form method="GET" action="{{ route('parent.children.assignments', $child) }}" class="flex flex-wrap items-end gap-3">
            <div>
                <flux:select name="session_id" label="{{ __('Session') }}">
                    @foreach ($sessions as $session)
                        <option value="{{ $session->id }}" @selected($selectedSessionId == $session->id)>
                            {{ $session->name }}
                        </option>
                    @endforeach
                </flux:select>
            </div>

            @if ($terms->isNotEmpty())
                <div>
                    <flux:select name="term_id" label="{{ __('Term') }}">
                        <option value="">{{ __('All Terms') }}</option>
                        @foreach ($terms as $term)
                            <option value="{{ $term->id }}" @selected($selectedTermId == $term->id)>
                                {{ $term->name }}
                            </option>
                        @endforeach
                    </flux:select>
                </div>
            @endif

            <div>
                <flux:select name="week" label="{{ __('Week') }}">
                    <option value="">{{ __('All Weeks') }}</option>
                    @for ($w = 1; $w <= $weeksPerTerm; $w++)
                        <option value="{{ $w }}" @selected($selectedWeek == $w)>
                            {{ __('Week :week', ['week' => $w]) }}
                        </option>
                    @endfor
                </flux:select>
            </div>

            <flux:button type="submit" variant="filled" size="sm">{{ __('Filter') }}</flux:button>

            @if (request()->hasAny(['session_id', 'term_id', 'week']))
                <flux:button variant="subtle" size="sm" href="{{ route('parent.children.assignments', $child) }}" wire:navigate>
                    {{ __('Clear') }}
                </flux:button>
            @endif
        </form>

        {{-- Assignments Table --}}
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Assignment') }}</flux:table.column>
                <flux:table.column class="hidden sm:table-cell">{{ __('Week') }}</flux:table.column>
                <flux:table.column class="hidden md:table-cell">{{ __('Due Date') }}</flux:table.column>
                <flux:table.column>{{ __('File') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($assignments as $assignment)
                    <flux:table.row>
                        <flux:table.cell>
                            <div>
                                <p class="font-medium text-zinc-900 dark:text-white">
                                    {{ $assignment->title ?? __('Week :week Assignment', ['week' => $assignment->week_number]) }}
                                </p>
                                @if ($assignment->description)
                                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400 line-clamp-2 mt-0.5">
                                        {{ Str::limit($assignment->description, 100) }}
                                    </flux:text>
                                @endif
                                <flux:text class="text-xs text-zinc-400 dark:text-zinc-500 mt-1 sm:hidden">
                                    {{ __('Week :week', ['week' => $assignment->week_number]) }}
                                    @if ($assignment->due_date)
                                        &mdash; {{ __('Due :date', ['date' => $assignment->due_date->format('M j')]) }}
                                    @endif
                                </flux:text>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell class="hidden sm:table-cell">
                            <flux:badge size="sm" color="zinc">
                                {{ __('Week :week', ['week' => $assignment->week_number]) }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="hidden md:table-cell text-zinc-500">
                            @if ($assignment->due_date)
                                @if ($assignment->due_date->isPast())
                                    <span class="text-red-600 dark:text-red-400">{{ $assignment->due_date->format('M j, Y') }}</span>
                                @else
                                    {{ $assignment->due_date->format('M j, Y') }}
                                @endif
                            @else
                                &mdash;
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            @if ($assignment->file_url)
                                <flux:button variant="subtle" size="xs" icon="arrow-down-tray" href="{{ $assignment->file_url }}" target="_blank" rel="noopener noreferrer">
                                    {{ __('Download') }}
                                </flux:button>
                            @else
                                <flux:text class="text-sm text-zinc-400">&mdash;</flux:text>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="4" class="text-center py-8">
                            <flux:icon.clipboard-document-list class="w-8 h-8 mx-auto text-zinc-300 dark:text-zinc-600" />
                            <flux:text class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('No assignments found for the selected filters.') }}
                            </flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        {{ $assignments->links() }}
    </div>
</x-layouts::app>
