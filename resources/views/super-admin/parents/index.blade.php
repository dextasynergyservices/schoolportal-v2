<x-layouts::app :title="__('All Parents')">
    <div class="space-y-6">
        <x-admin-header
            :title="__('All Parents')"
            :description="__('Parents across all schools on the platform.')"
            :action="route('super-admin.parents.create')"
            :actionLabel="__('Add Parent')"
        />

        @if (session('success'))
            <flux:callout variant="success" icon="check-circle">{{ session('success') }}</flux:callout>
        @endif

        {{-- Filters --}}
        <form method="GET" action="{{ route('super-admin.parents.index') }}" class="flex flex-wrap items-end gap-3">
            <div class="min-w-48 flex-1">
                <flux:input
                    name="search"
                    :value="request('search')"
                    placeholder="{{ __('Search by name or username...') }}"
                    icon="magnifying-glass"
                    aria-label="{{ __('Search parents') }}"
                />
            </div>
            <div class="w-full sm:w-48">
                <label for="school-filter" class="sr-only">{{ __('Filter by school') }}</label>
                <select
                    id="school-filter"
                    name="school_id"
                    onchange="this.form.submit()"
                    class="block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-[var(--color-primary)] focus:ring-[var(--color-primary)] dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
                >
                    <option value="">{{ __('All Schools') }}</option>
                    @foreach ($schools as $school)
                        <option value="{{ $school->id }}" @selected(request('school_id') == $school->id)>
                            {{ $school->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <flux:button type="submit" variant="filled" size="sm">{{ __('Filter') }}</flux:button>
            @if (request()->hasAny(['search', 'school_id']))
                <flux:button variant="subtle" size="sm" href="{{ route('super-admin.parents.index') }}" wire:navigate>
                    {{ __('Clear') }}
                </flux:button>
            @endif
        </form>

        {{-- Parents table --}}
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Parent') }}</flux:table.column>
                <flux:table.column>{{ __('School') }}</flux:table.column>
                <flux:table.column class="hidden md:table-cell">{{ __('Linked Students') }}</flux:table.column>
                <flux:table.column class="hidden sm:table-cell">{{ __('Relationship') }}</flux:table.column>
                <flux:table.column class="hidden lg:table-cell">{{ __('Phone') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($parents as $parent)
                    <flux:table.row>
                        <flux:table.cell>
                            <div class="flex items-center gap-3">
                                <flux:avatar size="sm" :src="$parent->avatar_url" :name="$parent->name" />
                                <div class="min-w-0">
                                    <div class="truncate font-medium text-zinc-900 dark:text-white">{{ $parent->name }}</div>
                                    <flux:text size="xs" class="text-zinc-500">{{ $parent->username }}</flux:text>
                                </div>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <a href="{{ route('super-admin.schools.show', $parent->school_id) }}" wire:navigate class="text-sm text-[var(--color-primary)] hover:underline">
                                {{ $parent->school?->name ?? '—' }}
                            </a>
                        </flux:table.cell>
                        <flux:table.cell class="hidden md:table-cell">
                            @if ($parent->children->isNotEmpty())
                                <div class="space-y-1">
                                    @foreach ($parent->children as $link)
                                        <div class="flex items-center gap-1">
                                            <span class="text-sm text-zinc-900 dark:text-white">{{ $link->student?->name ?? '—' }}</span>
                                            @if ($link->student?->studentProfile?->class)
                                                <flux:badge size="sm">{{ $link->student->studentProfile->class->name }}</flux:badge>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <flux:text size="sm" class="text-zinc-400">{{ __('No students linked') }}</flux:text>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="hidden sm:table-cell">
                            {{ $parent->parentProfile?->relationship ? ucfirst($parent->parentProfile->relationship) : '—' }}
                        </flux:table.cell>
                        <flux:table.cell class="hidden lg:table-cell">
                            {{ $parent->phone ?? '—' }}
                        </flux:table.cell>
                        <flux:table.cell>
                            @if ($parent->is_active)
                                <flux:badge color="green" size="sm">{{ __('Active') }}</flux:badge>
                            @else
                                <flux:badge color="zinc" size="sm">{{ __('Inactive') }}</flux:badge>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="text-center">
                            <div class="py-8">
                                <flux:icon.users class="mx-auto size-8 text-zinc-400" />
                                <flux:text class="mt-2 text-zinc-500">
                                    {{ request()->hasAny(['search', 'school_id']) ? __('No parents match your filters.') : __('No parents found. Select a school or add a parent to get started.') }}
                                </flux:text>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        {{ $parents->links() }}
    </div>
</x-layouts::app>
