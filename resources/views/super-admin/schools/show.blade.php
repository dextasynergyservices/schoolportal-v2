<x-layouts::app :title="$school->name">
    @php
        $branding = $school->settings['branding'] ?? [];
        $primaryColor = $branding['primary_color'] ?? '#4F46E5';
        $secondaryColor = $branding['secondary_color'] ?? '#F59E0B';
        $accentColor = $branding['accent_color'] ?? '#10B981';
        $serverIp = config('schoolportal.server_ip', '203.0.113.10');
    @endphp

    <div class="space-y-6">
        <x-admin-header :title="$school->name" :description="$school->motto">
            <flux:button variant="subtle" size="sm" icon="arrow-left" href="{{ route('super-admin.schools.index') }}" wire:navigate>
                {{ __('Back') }}
            </flux:button>
            <flux:button variant="filled" size="sm" icon="pencil-square" href="{{ route('super-admin.schools.edit', $school) }}" wire:navigate>
                {{ __('Edit') }}
            </flux:button>
        </x-admin-header>

        @if (session('success'))
            <flux:callout variant="success" icon="check-circle">{{ session('success') }}</flux:callout>
        @endif

        {{-- Status + quick actions --}}
        <div class="flex flex-wrap items-center gap-3 rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center gap-2">
                <flux:text size="sm" class="text-zinc-500">{{ __('Status:') }}</flux:text>
                @if ($school->is_active)
                    <flux:badge color="green">{{ __('Active') }}</flux:badge>
                @else
                    <flux:badge color="zinc">{{ __('Inactive') }}</flux:badge>
                @endif
            </div>
            <div class="ml-auto flex flex-wrap items-center gap-2">
                @if ($school->is_active)
                    <flux:modal.trigger name="deactivate-school">
                        <flux:button variant="subtle" size="sm" icon="pause-circle">{{ __('Deactivate') }}</flux:button>
                    </flux:modal.trigger>
                @else
                    <form method="POST" action="{{ route('super-admin.schools.activate', $school) }}">
                        @csrf
                        <flux:button type="submit" variant="subtle" size="sm" icon="play-circle">{{ __('Activate') }}</flux:button>
                    </form>
                @endif
                <x-confirm-delete
                    :action="route('super-admin.schools.destroy', $school)"
                    :title="__('Delete School')"
                    :message="__('This will permanently delete this school and ALL its data including students, teachers, results, and quizzes. This action cannot be undone.')"
                    :confirmLabel="__('Delete School')"
                    buttonVariant="danger"
                    buttonSize="sm"
                    :buttonLabel="__('Delete')"
                    :ariaLabel="__('Delete :name', ['name' => $school->name])"
                />
            </div>
        </div>

        @if (! $school->is_active && $school->deactivation_reason)
            <flux:callout variant="danger" icon="exclamation-triangle">
                <flux:callout.heading>{{ __('School Deactivated') }}</flux:callout.heading>
                <flux:callout.text>{{ $school->deactivation_reason }}</flux:callout.text>
                @if ($school->deactivated_at)
                    <flux:callout.text class="text-xs mt-1">{{ __('Deactivated on :date', ['date' => $school->deactivated_at->format('M j, Y g:i A')]) }}</flux:callout.text>
                @endif
            </flux:callout>
        @endif

        {{-- Deactivate modal --}}
        @if ($school->is_active)
            <flux:modal name="deactivate-school" class="max-w-md">
                <form method="POST" action="{{ route('super-admin.schools.deactivate', $school) }}" class="space-y-4">
                    @csrf
                    <div>
                        <flux:heading size="lg">{{ __('Deactivate :name', ['name' => $school->name]) }}</flux:heading>
                        <flux:text class="mt-1">{{ __('All users of this school will be unable to log in. This message will be shown to them.') }}</flux:text>
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

        {{-- Stats grid --}}
        <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-4">
            @foreach ([
                ['label' => __('Students'), 'value' => $school->students_count, 'icon' => 'academic-cap'],
                ['label' => __('Teachers'), 'value' => $school->teachers_count, 'icon' => 'user-group'],
                ['label' => __('Parents'), 'value' => $school->parents_count, 'icon' => 'users'],
                ['label' => __('Classes'), 'value' => $school->classes_count, 'icon' => 'building-library'],
            ] as $stat)
                <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex items-center gap-2 text-zinc-500">
                        <flux:icon :name="$stat['icon']" class="size-4" />
                        <flux:text size="xs" class="uppercase tracking-wide">{{ $stat['label'] }}</flux:text>
                    </div>
                    <div class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-white">
                        {{ number_format($stat['value']) }}
                    </div>
                </div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            {{-- School info --}}
            <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                    <flux:heading size="lg">{{ __('School Information') }}</flux:heading>
                </div>
                <dl class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ([
                        __('Email') => $school->email,
                        __('Phone') => $school->phone,
                        __('Website') => $school->website,
                        __('Address') => $school->address,
                        __('City') => $school->city,
                        __('State') => $school->state,
                        __('Country') => $school->country,
                        __('Levels') => $school->levels_count,
                        __('Created') => $school->created_at->format('M j, Y'),
                    ] as $label => $value)
                        <div class="flex items-start justify-between gap-3 px-4 py-2.5">
                            <dt class="text-sm text-zinc-500">{{ $label }}</dt>
                            <dd class="text-right text-sm font-medium">{{ $value ?: '—' }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>

            {{-- Primary admin --}}
            <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                    <flux:heading size="lg">{{ __('Primary Admin') }}</flux:heading>
                </div>
                <div class="p-4">
                    @if ($primaryAdmin)
                        <div class="flex items-center gap-3">
                            <flux:avatar :name="$primaryAdmin->name" size="md" />
                            <div class="min-w-0">
                                <div class="truncate font-medium">{{ $primaryAdmin->name }}</div>
                                <flux:text size="sm" class="text-zinc-500">
                                    {{ $primaryAdmin->email }} · @{{ $primaryAdmin->username }}
                                </flux:text>
                                @if ($primaryAdmin->last_login_at)
                                    <flux:text size="xs" class="text-zinc-500">
                                        {{ __('Last login: :ago', ['ago' => $primaryAdmin->last_login_at->diffForHumans()]) }}
                                    </flux:text>
                                @else
                                    <flux:text size="xs" class="text-zinc-500">{{ __('Never logged in') }}</flux:text>
                                @endif
                            </div>
                        </div>

                        {{-- Reset admin password --}}
                        <div class="mt-4 border-t border-zinc-200 pt-4 dark:border-zinc-700" x-data="{ showForm: false }">
                            <flux:button variant="subtle" size="sm" icon="key" x-on:click="showForm = !showForm" aria-controls="reset-pw-form" x-bind:aria-expanded="showForm">
                                {{ __('Reset Password') }}
                            </flux:button>

                            <form
                                method="POST"
                                action="{{ route('super-admin.schools.reset-admin-password', $school) }}"
                                x-show="showForm"
                                x-cloak
                                x-transition
                                id="reset-pw-form"
                                class="mt-3 space-y-3"
                            >
                                @csrf
                                <input type="hidden" name="admin_id" value="{{ $primaryAdmin->id }}">
                                <flux:field>
                                    <flux:label for="admin-new-pw">{{ __('New Password') }}</flux:label>
                                    <flux:input id="admin-new-pw" name="password" type="password" required viewable />
                                    <flux:description>{{ __('The admin will be forced to change this on their next login.') }}</flux:description>
                                    @error('password') <flux:error>{{ $message }}</flux:error> @enderror
                                </flux:field>
                                <flux:button type="submit" variant="primary" size="sm">{{ __('Reset Password') }}</flux:button>
                            </form>
                        </div>
                    @else
                        <flux:text class="text-zinc-500">{{ __('No admin account found for this school.') }}</flux:text>
                    @endif
                </div>
            </div>

            {{-- Branding preview --}}
            <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                    <flux:heading size="lg">{{ __('Branding') }}</flux:heading>
                </div>
                <div class="space-y-3 p-4">
                    @foreach ([
                        __('Primary') => $primaryColor,
                        __('Secondary') => $secondaryColor,
                        __('Accent') => $accentColor,
                    ] as $label => $hex)
                        <div class="flex items-center gap-3">
                            <span
                                class="inline-block size-10 rounded-lg border border-zinc-200 dark:border-zinc-700"
                                style="background-color: {{ $hex }}"
                                aria-hidden="true"
                            ></span>
                            <div>
                                <div class="text-sm font-medium">{{ $label }}</div>
                                <div class="font-mono text-xs text-zinc-500">{{ $hex }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- AI credits --}}
            <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                    <flux:heading size="lg">{{ __('AI Credits') }}</flux:heading>
                    <flux:button variant="subtle" size="sm" href="{{ route('super-admin.credits.index', ['search' => $school->name]) }}" wire:navigate>
                        {{ __('Adjust') }}
                    </flux:button>
                </div>
                <dl class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    <div class="flex items-center justify-between px-4 py-2.5">
                        <dt class="text-sm text-zinc-500">{{ __('Free credits') }}</dt>
                        <dd class="text-sm font-medium">{{ number_format($school->ai_free_credits) }} / 15</dd>
                    </div>
                    <div class="flex items-center justify-between px-4 py-2.5">
                        <dt class="text-sm text-zinc-500">{{ __('Purchased credits') }}</dt>
                        <dd class="text-sm font-medium">{{ number_format($school->ai_purchased_credits) }}</dd>
                    </div>
                    <div class="flex items-center justify-between px-4 py-2.5">
                        <dt class="text-sm text-zinc-500">{{ __('Lifetime purchased') }}</dt>
                        <dd class="text-sm font-medium">{{ number_format($school->ai_credits_total_purchased) }}</dd>
                    </div>
                    <div class="flex items-center justify-between px-4 py-2.5">
                        <dt class="text-sm text-zinc-500">{{ __('Next free reset') }}</dt>
                        <dd class="text-sm font-medium">
                            {{ $school->ai_free_credits_reset_at ? \Illuminate\Support\Carbon::parse($school->ai_free_credits_reset_at)->format('M j, Y') : '—' }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        {{-- Custom domain / DNS --}}
        <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                <flux:heading size="lg">{{ __('Custom Domain & DNS') }}</flux:heading>
            </div>
            <div class="space-y-4 p-4">
                @if ($school->custom_domain)
                    <div class="flex items-center gap-2">
                        <flux:icon.globe-alt class="size-5 text-zinc-500" />
                        <span class="font-mono text-sm font-medium">{{ $school->custom_domain }}</span>
                        <a href="https://{{ $school->custom_domain }}" target="_blank" rel="noopener noreferrer" class="ml-1 text-xs text-zinc-500 hover:underline">
                            {{ __('Open ↗') }}
                        </a>
                    </div>

                    <flux:callout variant="secondary" icon="information-circle">
                        <flux:heading size="sm">{{ __('DNS setup required') }}</flux:heading>
                        <flux:text size="sm" class="mt-1">
                            {{ __('Have the school add this A record at their domain registrar so the portal resolves on their domain.') }}
                        </flux:text>
                    </flux:callout>

                    <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                        <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                            <thead class="bg-zinc-50 text-left text-xs uppercase tracking-wide text-zinc-500 dark:bg-zinc-800">
                                <tr>
                                    <th scope="col" class="px-3 py-2">{{ __('Type') }}</th>
                                    <th scope="col" class="px-3 py-2">{{ __('Name') }}</th>
                                    <th scope="col" class="px-3 py-2">{{ __('Value') }}</th>
                                    <th scope="col" class="px-3 py-2">{{ __('TTL') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 font-mono dark:divide-zinc-800">
                                <tr>
                                    <td class="px-3 py-2">A</td>
                                    <td class="px-3 py-2">@</td>
                                    <td class="px-3 py-2">{{ $serverIp }}</td>
                                    <td class="px-3 py-2">3600</td>
                                </tr>
                                <tr>
                                    <td class="px-3 py-2">A</td>
                                    <td class="px-3 py-2">www</td>
                                    <td class="px-3 py-2">{{ $serverIp }}</td>
                                    <td class="px-3 py-2">3600</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <flux:text size="sm" class="text-zinc-500">
                        {{ __('After DNS propagates (up to 24 hours), SSL is auto-provisioned and the portal becomes reachable at') }}
                        <span class="font-mono">https://{{ $school->custom_domain }}/portal</span>.
                    </flux:text>
                @else
                    <flux:text class="text-zinc-500">
                        {{ __('No custom domain set. Add one in') }}
                        <a href="{{ route('super-admin.schools.edit', $school) }}" wire:navigate class="underline">{{ __('edit') }}</a>.
                    </flux:text>
                @endif
            </div>
        </div>
    </div>
</x-layouts::app>
