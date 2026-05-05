<x-layouts::app :title="__('Schools')">
    @php $allSchoolIds = array_map(fn ($s) => (string) $s->id, $schools->items()); @endphp
    <div
        class="space-y-6"
        x-data="{
            selected: [],
            allIds: {{ json_encode($allSchoolIds) }},
            get allSelected() { return this.allIds.length > 0 && this.selected.length === this.allIds.length; },
            get someSelected() { return this.selected.length > 0 && !this.allSelected; },
            toggleAll(checked) { this.selected = checked ? [...this.allIds] : []; },

            // Bulk form helpers
            submitToggle(value) { this.$refs.cbtSettingValue.value = value; this.$refs.bulkCbtForm.submit(); },
            submitActivate()    { this.$refs.bulkActivateForm.submit(); },
            submitDeactivate()  { this.showDeactivateModal = true; },
            submitCredits()     { this.showCreditsModal = true; },

            // Modals
            showDeactivateModal: false,
            deactivateReason: '',
            confirmDeactivate() {
                if (!this.deactivateReason.trim()) return;
                this.$refs.deactivateReason.value = this.deactivateReason;
                this.$refs.bulkDeactivateForm.submit();
            },

            showCreditsModal: false,
            freeDelta: 0,
            purchasedDelta: 0,
            creditReason: '',
            confirmCredits() { this.$refs.bulkCreditsForm.submit(); },
        }"
    >
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
            <flux:input
                name="location"
                :value="request('location')"
                placeholder="{{ __('City, state or country...') }}"
                class="min-w-48"
                icon="map-pin"
                aria-label="{{ __('Filter by city, state or country') }}"
            />
            <flux:select name="sort" class="min-w-48" aria-label="{{ __('Sort by') }}">
                <option value="" @selected(!request('sort'))>{{ __('Newest First') }}</option>
                <option value="students" @selected(request('sort') === 'students')>{{ __('Most Students') }}</option>
                <option value="teachers" @selected(request('sort') === 'teachers')>{{ __('Most Teachers') }}</option>
                <option value="credits" @selected(request('sort') === 'credits')>{{ __('Most AI Credits') }}</option>
                <option value="name" @selected(request('sort') === 'name')>{{ __('Name A→Z') }}</option>
                <option value="created" @selected(request('sort') === 'created')>{{ __('Oldest First') }}</option>
                <option value="health" @selected(request('sort') === 'health')>{{ __('Most Active') }}</option>
            </flux:select>
            <flux:button type="submit" variant="filled" size="sm">{{ __('Filter') }}</flux:button>
            @if (request()->hasAny(['search', 'status', 'sort', 'location']))
                <flux:button variant="subtle" size="sm" href="{{ route('super-admin.schools.index') }}" wire:navigate>
                    {{ __('Clear') }}
                </flux:button>
            @endif
        </form>

        {{-- ── Bulk Action Bar ───────────────────────────────────────────────── --}}
        <div
            x-show="selected.length > 0"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="flex flex-wrap items-center gap-3 rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-3 shadow-sm dark:border-indigo-800 dark:bg-indigo-950/30"
        >
            {{-- Selection count --}}
            <div class="flex items-center gap-2">
                <div class="flex h-7 w-7 items-center justify-center rounded-full bg-indigo-600 text-xs font-bold text-white">
                    <span x-text="selected.length"></span>
                </div>
                <span class="text-sm font-medium text-indigo-800 dark:text-indigo-200">
                    {{ __('school(s) selected') }}
                </span>
            </div>

            {{-- Divider --}}
            <div class="hidden h-6 w-px bg-indigo-200 dark:bg-indigo-700 sm:block"></div>

            {{-- Action buttons --}}
            <div class="flex flex-wrap items-center gap-2">
                {{-- Activate --}}
                <flux:button
                    size="sm" variant="filled"
                    icon="play-circle"
                    @click="submitActivate()"
                    class="bg-emerald-600 hover:bg-emerald-700 focus:ring-emerald-500"
                >
                    {{ __('Activate') }}
                </flux:button>

                {{-- Deactivate --}}
                <flux:button
                    size="sm" variant="ghost"
                    icon="pause-circle"
                    @click="submitDeactivate()"
                >
                    {{ __('Deactivate') }}
                </flux:button>

                {{-- Credits --}}
                <flux:button
                    size="sm" variant="ghost"
                    icon="sparkles"
                    @click="submitCredits()"
                >
                    {{ __('Adjust Credits') }}
                </flux:button>

                {{-- CBT Settings dropdown --}}
                <div x-data="{ open: false }" class="relative">
                    <flux:button size="sm" variant="ghost" icon="cog-6-tooth" @click="open = !open" @click.outside="open = false">
                        {{ __('Settings') }}
                        <span x-bind:class="open ? 'rotate-180' : ''" class="ml-1 w-3.5 h-3.5 transition-transform duration-150 inline-flex">
                            <flux:icon.chevron-down class="w-3.5 h-3.5" />
                        </span>
                    </flux:button>
                    <div
                        x-show="open"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="absolute left-0 top-full z-20 mt-1 w-52 rounded-lg border border-zinc-200 bg-white py-1 shadow-lg dark:border-zinc-700 dark:bg-zinc-800"
                    >
                        <button type="button" @click="submitToggle('1'); open = false"
                            class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-zinc-700 hover:bg-zinc-50 dark:text-zinc-300 dark:hover:bg-zinc-700">
                            <flux:icon.check-circle class="w-4 h-4 text-emerald-500" />
                            {{ __('Enable CBT Results') }}
                        </button>
                        <button type="button" @click="submitToggle('0'); open = false"
                            class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-zinc-700 hover:bg-zinc-50 dark:text-zinc-300 dark:hover:bg-zinc-700">
                            <flux:icon.x-circle class="w-4 h-4 text-zinc-400" />
                            {{ __('Disable CBT Results') }}
                        </button>
                    </div>
                </div>
            </div>

            {{-- Clear --}}
            <button
                type="button"
                @click="selected = []"
                class="ml-auto flex items-center gap-1 text-xs text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-200"
                aria-label="{{ __('Clear selection') }}"
            >
                <flux:icon.x-mark class="w-3.5 h-3.5" />
                {{ __('Clear') }}
            </button>
        </div>

        {{-- ── Hidden Bulk Forms ─────────────────────────────────────────────── --}}

        {{-- CBT Toggle --}}
        <form x-ref="bulkCbtForm" method="POST" action="{{ route('super-admin.schools.bulk-toggle-setting') }}" class="hidden">
            @csrf
            <template x-for="id in selected" :key="id">
                <input type="hidden" name="school_ids[]" :value="id" />
            </template>
            <input type="hidden" name="setting_key" value="enable_cbt_results_for_parents" />
            <input type="hidden" name="setting_value" x-ref="cbtSettingValue" value="1" />
        </form>

        {{-- Bulk Activate --}}
        <form x-ref="bulkActivateForm" method="POST" action="{{ route('super-admin.schools.bulk-activate') }}" class="hidden">
            @csrf
            <template x-for="id in selected" :key="id">
                <input type="hidden" name="school_ids[]" :value="id" />
            </template>
        </form>

        {{-- Bulk Deactivate --}}
        <form x-ref="bulkDeactivateForm" method="POST" action="{{ route('super-admin.schools.bulk-deactivate') }}" class="hidden">
            @csrf
            <template x-for="id in selected" :key="id">
                <input type="hidden" name="school_ids[]" :value="id" />
            </template>
            <input type="hidden" name="deactivation_reason" x-ref="deactivateReason" />
        </form>

        {{-- Bulk Credit Adjustment --}}
        <form x-ref="bulkCreditsForm" method="POST" action="{{ route('super-admin.schools.bulk-adjust-credits') }}" class="hidden">
            @csrf
            <template x-for="id in selected" :key="id">
                <input type="hidden" name="school_ids[]" :value="id" />
            </template>
            <input type="hidden" name="free_delta"      :value="freeDelta" />
            <input type="hidden" name="purchased_delta" :value="purchasedDelta" />
            <input type="hidden" name="reason"          :value="creditReason" />
        </form>

        {{-- ── Deactivate Modal ──────────────────────────────────────────────── --}}
        <template x-teleport="body">
            <div
                x-show="showDeactivateModal"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @keydown.escape.window="showDeactivateModal = false"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                role="dialog" aria-modal="true" aria-labelledby="bulk-deactivate-title"
                x-cloak
            >
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showDeactivateModal = false" aria-hidden="true"></div>
                <div
                    x-show="showDeactivateModal"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="relative z-10 w-full max-w-md rounded-2xl border border-zinc-200 bg-white p-6 shadow-2xl dark:border-zinc-700 dark:bg-zinc-900"
                >
                    <div class="mb-4 flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/30">
                            <flux:icon.pause-circle class="h-5 w-5 text-amber-600 dark:text-amber-400" />
                        </div>
                        <div>
                            <h3 id="bulk-deactivate-title" class="text-base font-semibold text-zinc-900 dark:text-white">
                                {{ __('Bulk Deactivate Schools') }}
                            </h3>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                <span x-text="selected.length"></span> {{ __('school(s) will be deactivated') }}
                            </p>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                {{ __('Reason for deactivation') }} <span class="text-red-500">*</span>
                            </label>
                            <textarea
                                x-model="deactivateReason"
                                rows="3"
                                required
                                placeholder="{{ __('e.g. Subscription expired, terms violation...') }}"
                                class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 placeholder-zinc-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder-zinc-500"
                            ></textarea>
                        </div>
                        <div class="flex items-center justify-end gap-3 pt-2">
                            <flux:button variant="ghost" @click="showDeactivateModal = false">
                                {{ __('Cancel') }}
                            </flux:button>
                            <flux:button
                                variant="danger"
                                x-bind:disabled="!deactivateReason.trim()"
                                @click="confirmDeactivate()"
                            >
                                {{ __('Deactivate Schools') }}
                            </flux:button>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        {{-- ── Credits Modal ─────────────────────────────────────────────────── --}}
        <template x-teleport="body">
            <div
                x-show="showCreditsModal"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @keydown.escape.window="showCreditsModal = false"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                role="dialog" aria-modal="true" aria-labelledby="bulk-credits-title"
                x-cloak
            >
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showCreditsModal = false" aria-hidden="true"></div>
                <div
                    x-show="showCreditsModal"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="relative z-10 w-full max-w-md rounded-2xl border border-zinc-200 bg-white p-6 shadow-2xl dark:border-zinc-700 dark:bg-zinc-900"
                >
                    <div class="mb-4 flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-violet-100 dark:bg-violet-900/30">
                            <flux:icon.sparkles class="h-5 w-5 text-violet-600 dark:text-violet-400" />
                        </div>
                        <div>
                            <h3 id="bulk-credits-title" class="text-base font-semibold text-zinc-900 dark:text-white">
                                {{ __('Bulk Adjust AI Credits') }}
                            </h3>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                <span x-text="selected.length"></span> {{ __('school(s) will be updated') }}
                            </p>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ __('Free Credits') }}
                                    <span class="font-normal text-zinc-400">({{ __('e.g. +5 or -3') }})</span>
                                </label>
                                <input
                                    type="number"
                                    x-model.number="freeDelta"
                                    min="-500" max="500"
                                    placeholder="0"
                                    class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                                />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ __('Purchased Credits') }}
                                    <span class="font-normal text-zinc-400">({{ __('e.g. +10 or 0') }})</span>
                                </label>
                                <input
                                    type="number"
                                    x-model.number="purchasedDelta"
                                    min="-500" max="500"
                                    placeholder="0"
                                    class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                                />
                            </div>
                        </div>

                        {{-- Live summary --}}
                        <div class="rounded-lg bg-violet-50 px-3 py-2 text-sm dark:bg-violet-950/30">
                            <span class="text-violet-700 dark:text-violet-300">
                                {{ __('Each selected school will receive:') }}
                                <span x-text="(freeDelta >= 0 ? '+' : '') + freeDelta + ' free, ' + (purchasedDelta >= 0 ? '+' : '') + purchasedDelta + ' purchased'"
                                    class="font-semibold"></span>
                            </span>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                {{ __('Reason') }} <span class="font-normal text-zinc-400">({{ __('optional') }})</span>
                            </label>
                            <input
                                type="text"
                                x-model="creditReason"
                                placeholder="{{ __('e.g. Monthly top-up, promotional bonus...') }}"
                                class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 placeholder-zinc-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder-zinc-500"
                            />
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-2">
                            <flux:button variant="ghost" @click="showCreditsModal = false">
                                {{ __('Cancel') }}
                            </flux:button>
                            <flux:button
                                variant="filled"
                                x-bind:disabled="freeDelta === 0 && purchasedDelta === 0"
                                @click="confirmCredits()"
                            >
                                {{ __('Apply to All Selected') }}
                            </flux:button>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <flux:table>
            <flux:table.columns>
                <flux:table.column class="w-10">
                    <input
                        type="checkbox"
                        :checked="allSelected"
                        :indeterminate="someSelected"
                        @change="toggleAll($event.target.checked)"
                        class="size-4 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500 dark:border-zinc-600"
                        aria-label="{{ __('Select all schools on this page') }}"
                    />
                </flux:table.column>
                <flux:table.column>{{ __('School') }}</flux:table.column>
                <flux:table.column class="hidden md:table-cell">{{ __('Students') }}</flux:table.column>
                <flux:table.column class="hidden md:table-cell">{{ __('Teachers') }}</flux:table.column>
                <flux:table.column class="hidden lg:table-cell">{{ __('Credits') }}</flux:table.column>
                <flux:table.column>{{ __('Health') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column class="w-40" />
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($schools as $school)
                    <flux:table.row>
                        <flux:table.cell class="w-10">
                            <input
                                type="checkbox"
                                x-model="selected"
                                value="{{ $school->id }}"
                                class="size-4 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500 dark:border-zinc-600"
                                aria-label="{{ __('Select :name', ['name' => $school->name]) }}"
                            />
                        </flux:table.cell>
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
                        {{-- Health column (§2.3) --}}
                        <flux:table.cell>
                            @php $h = $healthData[$school->id] ?? null; @endphp
                            @if ($h)
                                @php
                                    [$dot, $dotTitle] = match ($h['status']) {
                                        'healthy'  => ['bg-emerald-500', __('Active within 7 days')],
                                        'moderate' => ['bg-yellow-400',  __('Active within 30 days')],
                                        'at_risk'  => ['bg-orange-400',  __('Active within 60 days')],
                                        'idle'     => ['bg-red-500',     __('Inactive 60+ days')],
                                        default    => ['bg-zinc-400',    __('Never logged in')],
                                    };
                                @endphp
                                <div class="flex items-center gap-2">
                                    <span class="relative flex h-2.5 w-2.5">
                                        @if ($h['status'] === 'healthy')
                                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full {{ $dot }} opacity-60"></span>
                                        @endif
                                        <span class="relative inline-flex h-2.5 w-2.5 rounded-full {{ $dot }}" title="{{ $dotTitle }}"></span>
                                    </span>
                                    <div class="min-w-0">
                                        <div class="text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                            @if ($h['last_login'])
                                                {{ $h['last_login']->diffForHumans() }}
                                            @else
                                                {{ __('Never') }}
                                            @endif
                                        </div>
                                        <div class="text-xs text-zinc-400">
                                            {{ __(':n content · :ai AI', [
                                                'n' => $h['recent_content'],
                                                'ai' => $h['ai_this_month'],
                                            ]) }}
                                        </div>
                                    </div>
                                </div>
                            @else
                                <span class="text-xs text-zinc-400">—</span>
                            @endif
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
                        <flux:table.cell colspan="8" class="py-8 text-center text-zinc-500">
                            {{ __('No schools found.') }}
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        {{ $schools->links() }}
    </div>
</x-layouts::app>
