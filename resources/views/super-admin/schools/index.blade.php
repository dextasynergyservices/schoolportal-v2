<x-layouts::app :title="__('Schools')">
    <div class="space-y-6">
        <x-admin-header
            :title="__('Schools')"
            :description="__('All schools on the platform')"
            :action="route('super-admin.schools.create')"
            :actionLabel="__('New School')"
            actionIcon="plus"
        />

        @if (session('success'))
            <flux:callout variant="success" icon="check-circle">{{ session('success') }}</flux:callout>
        @endif
        @if (session('error'))
            <flux:callout variant="danger" icon="exclamation-triangle">{{ session('error') }}</flux:callout>
        @endif

        {{-- Filters --}}
        <form method="GET" action="{{ route('super-admin.schools.index') }}" class="flex flex-wrap items-end gap-3">
            <div class="min-w-48 flex-1">
                <flux:input
                    name="search"
                    :value="request('search')"
                    placeholder="{{ __('Search name, domain, or email...') }}"
                    icon="magnifying-glass"
                    aria-label="{{ __('Search schools') }}"
                />
            </div>
            <flux:select name="status" class="min-w-40" aria-label="{{ __('Filter by status') }}">
                <option value="">{{ __('All Statuses') }}</option>
                <option value="active" @selected(request('status') === 'active')>{{ __('Active') }}</option>
                <option value="inactive" @selected(request('status') === 'inactive')>{{ __('Inactive') }}</option>
            </flux:select>
            <flux:button type="submit" variant="filled" size="sm">{{ __('Filter') }}</flux:button>
            @if (request()->hasAny(['search', 'status']))
                <flux:button variant="subtle" size="sm" href="{{ route('super-admin.schools.index') }}" wire:navigate>
                    {{ __('Clear') }}
                </flux:button>
            @endif
        </form>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('School') }}</flux:table.column>
                <flux:table.column class="hidden md:table-cell">{{ __('Students') }}</flux:table.column>
                <flux:table.column class="hidden md:table-cell">{{ __('Teachers') }}</flux:table.column>
                <flux:table.column class="hidden lg:table-cell">{{ __('Credits') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column class="w-40" />
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($schools as $school)
                    <flux:table.row>
                        <flux:table.cell>
                            <a href="{{ route('super-admin.schools.show', $school) }}" wire:navigate class="block hover:underline">
                                <div class="font-medium text-zinc-900 dark:text-white">{{ $school->name }}</div>
                                <div class="truncate text-xs text-zinc-500">
                                    {{ $school->custom_domain ?? $school->email }}
                                </div>
                            </a>
                        </flux:table.cell>
                        <flux:table.cell class="hidden md:table-cell">
                            {{ number_format($school->students_count) }}
                        </flux:table.cell>
                        <flux:table.cell class="hidden md:table-cell">
                            {{ number_format($school->teachers_count) }}
                        </flux:table.cell>
                        <flux:table.cell class="hidden lg:table-cell text-xs text-zinc-500">
                            {{ __(':f free · :p purchased', [
                                'f' => $school->ai_free_credits,
                                'p' => $school->ai_purchased_credits,
                            ]) }}
                        </flux:table.cell>
                        <flux:table.cell>
                            @if ($school->is_active)
                                <flux:badge color="green" size="sm">{{ __('Active') }}</flux:badge>
                            @else
                                <flux:badge color="zinc" size="sm">{{ __('Inactive') }}</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-1">
                                <flux:button
                                    variant="subtle" size="xs" icon="eye"
                                    href="{{ route('super-admin.schools.show', $school) }}"
                                    wire:navigate
                                    aria-label="{{ __('View :name', ['name' => $school->name]) }}"
                                />
                                <flux:button
                                    variant="subtle" size="xs" icon="pencil-square"
                                    href="{{ route('super-admin.schools.edit', $school) }}"
                                    wire:navigate
                                    aria-label="{{ __('Edit :name', ['name' => $school->name]) }}"
                                />
                                @if ($school->is_active)
                                    <flux:modal.trigger :name="'deactivate-school-' . $school->id">
                                        <flux:button
                                            variant="subtle" size="xs" icon="pause-circle"
                                            aria-label="{{ __('Deactivate :name', ['name' => $school->name]) }}"
                                        />
                                    </flux:modal.trigger>
                                @else
                                    <form method="POST" action="{{ route('super-admin.schools.activate', $school) }}" class="inline">
                                        @csrf
                                        <flux:button
                                            type="submit" variant="subtle" size="xs" icon="play-circle"
                                            aria-label="{{ __('Activate :name', ['name' => $school->name]) }}"
                                        />
                                    </form>
                                @endif
                            </div>

                            {{-- Deactivate modal --}}
                            @if ($school->is_active)
                                <flux:modal :name="'deactivate-school-' . $school->id" class="max-w-md">
                                    <form method="POST" action="{{ route('super-admin.schools.deactivate', $school) }}" class="space-y-4">
                                        @csrf
                                        <div>
                                            <flux:heading size="lg">{{ __('Deactivate :name', ['name' => $school->name]) }}</flux:heading>
                                            <flux:text class="mt-1">{{ __('All users of this school will be unable to log in until the school is reactivated.') }}</flux:text>
                                        </div>
                                        <flux:textarea
                                            name="deactivation_reason"
                                            :label="__('Reason for deactivation')"
                                            :placeholder="__('e.g. Subscription expired, pending renewal...')"
                                            required
                                            rows="3"
                                        />
                                        <div class="flex justify-end gap-2">
                                            <flux:modal.close>
                                                <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                                            </flux:modal.close>
                                            <flux:button type="submit" variant="danger">{{ __('Deactivate') }}</flux:button>
                                        </div>
                                    </form>
                                </flux:modal>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="py-8 text-center text-zinc-500">
                            {{ __('No schools found.') }}
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        {{ $schools->links() }}
    </div>
</x-layouts::app>
