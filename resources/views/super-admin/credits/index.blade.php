<x-layouts::app :title="__('AI Credits')">
    <div class="space-y-6">
        <x-admin-header
            :title="__('AI Credits')"
            :description="__('View and adjust credit balances across schools.')"
        />

        @include('partials.credits-subnav')

        @if (session('success'))
            <flux:callout variant="success" icon="check-circle">{{ session('success') }}</flux:callout>
        @endif
        @if ($errors->any())
            <flux:callout variant="danger" icon="exclamation-triangle">
                <ul class="list-disc space-y-1 pl-5 text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </flux:callout>
        @endif

        {{-- Search --}}
        <form method="GET" action="{{ route('super-admin.credits.index') }}" class="flex flex-wrap items-end gap-3">
            <div class="min-w-48 flex-1">
                <flux:input
                    name="search"
                    :value="request('search')"
                    placeholder="{{ __('Search school name...') }}"
                    icon="magnifying-glass"
                    aria-label="{{ __('Search schools') }}"
                />
            </div>
            <flux:button type="submit" variant="filled" size="sm">{{ __('Search') }}</flux:button>
            @if (request('search'))
                <flux:button variant="subtle" size="sm" href="{{ route('super-admin.credits.index') }}" wire:navigate>
                    {{ __('Clear') }}
                </flux:button>
            @endif
        </form>

        {{-- Schools with credit balances --}}
        <div class="space-y-3">
            @forelse ($schools as $school)
                <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <a
                                    href="{{ route('super-admin.schools.show', $school) }}"
                                    wire:navigate
                                    class="truncate font-medium text-zinc-900 hover:underline dark:text-white"
                                >
                                    {{ $school->name }}
                                </a>
                                @if (! $school->is_active)
                                    <flux:badge color="zinc" size="sm">{{ __('Inactive') }}</flux:badge>
                                @endif
                            </div>
                            <flux:text size="xs" class="truncate text-zinc-500">
                                {{ $school->custom_domain ?? $school->email }}
                            </flux:text>

                            <dl class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
                                <div>
                                    <dt class="text-xs uppercase tracking-wide text-zinc-500">{{ __('Free') }}</dt>
                                    <dd class="mt-1 text-lg font-semibold text-zinc-900 dark:text-white">
                                        {{ number_format($school->ai_free_credits) }}
                                        <span class="text-xs font-normal text-zinc-500">/ 15</span>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs uppercase tracking-wide text-zinc-500">{{ __('Purchased') }}</dt>
                                    <dd class="mt-1 text-lg font-semibold text-zinc-900 dark:text-white">
                                        {{ number_format($school->ai_purchased_credits) }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs uppercase tracking-wide text-zinc-500">{{ __('Total') }}</dt>
                                    <dd class="mt-1 text-lg font-semibold text-zinc-900 dark:text-white">
                                        {{ number_format($school->ai_free_credits + $school->ai_purchased_credits) }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs uppercase tracking-wide text-zinc-500">{{ __('Lifetime') }}</dt>
                                    <dd class="mt-1 text-lg font-semibold text-zinc-900 dark:text-white">
                                        {{ number_format($school->ai_credits_total_purchased) }}
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        {{-- Adjust form --}}
                        <form
                            method="POST"
                            action="{{ route('super-admin.credits.adjust', $school) }}"
                            class="flex flex-wrap items-end gap-2 lg:shrink-0"
                            aria-label="{{ __('Adjust credits for :name', ['name' => $school->name]) }}"
                        >
                            @csrf
                            <div class="w-28">
                                <label for="free-{{ $school->id }}" class="block text-xs font-medium text-zinc-500">
                                    {{ __('Free Δ') }}
                                </label>
                                <input
                                    type="number"
                                    id="free-{{ $school->id }}"
                                    name="free_delta"
                                    value="0"
                                    step="1"
                                    class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-2 py-1.5 text-sm shadow-sm focus:border-[var(--color-primary)] focus:ring-[var(--color-primary)] dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
                                />
                            </div>
                            <div class="w-28">
                                <label for="purchased-{{ $school->id }}" class="block text-xs font-medium text-zinc-500">
                                    {{ __('Purchased Δ') }}
                                </label>
                                <input
                                    type="number"
                                    id="purchased-{{ $school->id }}"
                                    name="purchased_delta"
                                    value="0"
                                    step="1"
                                    class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-2 py-1.5 text-sm shadow-sm focus:border-[var(--color-primary)] focus:ring-[var(--color-primary)] dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
                                />
                            </div>
                            <flux:button type="submit" variant="filled" size="sm" icon="adjustments-horizontal">
                                {{ __('Adjust') }}
                            </flux:button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="rounded-lg border border-dashed border-zinc-300 p-8 text-center dark:border-zinc-700">
                    <flux:text class="text-zinc-500">{{ __('No schools found.') }}</flux:text>
                </div>
            @endforelse
        </div>

        {{ $schools->links() }}

        <flux:callout variant="secondary" icon="information-circle">
            <flux:text size="sm">
                {{ __('Use positive values to add credits, negative to subtract. Free credits reset to 15 on the 1st of each month; purchased credits never expire. Adjustments apply immediately.') }}
            </flux:text>
        </flux:callout>
    </div>
</x-layouts::app>
