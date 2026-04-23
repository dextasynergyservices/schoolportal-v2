<x-layouts::app :title="$school->name">
    @php
        $branding = $school->settings['branding'] ?? [];
        $primaryColor = $branding['primary_color'] ?? '#4F46E5';
        $secondaryColor = $branding['secondary_color'] ?? '#F59E0B';
        $accentColor = $branding['accent_color'] ?? '#10B981';
        $serverIp = env('SERVER_IP', '');
        $platformDomain = parse_url(config('app.url'), PHP_URL_HOST) ?: 'your-platform-domain.com';
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
        @if (session('error'))
            <flux:callout variant="danger" icon="exclamation-triangle">{{ session('error') }}</flux:callout>
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

            {{-- School admins --}}
            <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                    <flux:heading size="lg">{{ __('School Admins') }} ({{ $schoolAdmins->count() }})</flux:heading>
                    <flux:button variant="primary" size="sm" icon="plus" href="{{ route('super-admin.schools.create-admin', $school) }}" wire:navigate>
                        {{ __('Add Admin') }}
                    </flux:button>
                </div>
                <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($schoolAdmins as $admin)
                        <div class="flex items-center justify-between gap-3 p-4">
                            <div class="flex items-center gap-3 min-w-0">
                                <flux:avatar :name="$admin->name" size="md" />
                                <div class="min-w-0">
                                    <div class="truncate font-medium">{{ $admin->name }}</div>
                                    <flux:text size="sm" class="text-zinc-500">
                                        {{ $admin->email ?? '—' }} · {{ $admin->username }}
                                    </flux:text>
                                    @if ($admin->last_login_at)
                                        <flux:text size="xs" class="text-zinc-500">
                                            {{ __('Last login: :ago', ['ago' => $admin->last_login_at->diffForHumans()]) }}
                                        </flux:text>
                                    @else
                                        <flux:text size="xs" class="text-zinc-500">{{ __('Never logged in') }}</flux:text>
                                    @endif
                                </div>
                            </div>
                            @if ($schoolAdmins->count() > 1)
                                <x-confirm-delete
                                    :action="route('super-admin.schools.destroy-admin', [$school, $admin])"
                                    :title="__('Remove Admin')"
                                    :message="__('Remove :name as admin? They will no longer be able to manage this school.', ['name' => $admin->name])"
                                    :confirmLabel="__('Remove')"
                                    buttonVariant="danger"
                                    buttonSize="xs"
                                    :buttonLabel="__('Remove')"
                                    :ariaLabel="__('Remove :name', ['name' => $admin->name])"
                                />
                            @endif
                        </div>
                    @empty
                        <div class="p-4">
                            <flux:text class="text-zinc-500">{{ __('No admin accounts found for this school.') }}</flux:text>
                        </div>
                    @endforelse
                </div>

                {{-- Reset admin password (for primary admin) --}}
                @if ($primaryAdmin)
                    <div class="border-t border-zinc-200 p-4 dark:border-zinc-700" x-data="{ showForm: false, selectedAdmin: '' }">
                        <flux:button variant="subtle" size="sm" icon="key" x-on:click="showForm = !showForm" aria-controls="reset-pw-form" x-bind:aria-expanded="showForm">
                            {{ __('Reset Admin Password') }}
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
                            <flux:field>
                                <flux:label for="admin-select">{{ __('Select Admin') }}</flux:label>
                                <select
                                    id="admin-select"
                                    name="admin_id"
                                    required
                                    class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-[var(--color-primary)] focus:ring-[var(--color-primary)] dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
                                >
                                    @foreach ($schoolAdmins as $admin)
                                        <option value="{{ $admin->id }}">{{ $admin->name }} ({{ $admin->username }})</option>
                                    @endforeach
                                </select>
                            </flux:field>
                            <flux:field>
                                <flux:label for="admin-new-pw">{{ __('New Password') }}</flux:label>
                                <flux:input id="admin-new-pw" name="password" type="password" required viewable />
                                <flux:description>{{ __('The admin will be forced to change this on their next login.') }}</flux:description>
                                @error('password') <flux:error>{{ $message }}</flux:error> @enderror
                            </flux:field>
                            <flux:button type="submit" variant="primary" size="sm">{{ __('Reset Password') }}</flux:button>
                        </form>
                    </div>
                @endif
            </div>

            {{-- Branding --}}
            <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 lg:col-span-2">
                <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                    <flux:heading size="lg">{{ __('Branding') }}</flux:heading>
                </div>
                <div class="grid grid-cols-1 gap-6 p-4 sm:grid-cols-2">
                    {{-- Logo --}}
                    <div class="space-y-4">
                        <flux:text size="sm" class="font-semibold">{{ __('School Logo') }}</flux:text>
                        <div class="flex items-start gap-4" x-data="{ preview: null }">
                            <div class="shrink-0">
                                {{-- Client-side preview (shown when file selected) --}}
                                <img
                                    x-show="preview"
                                    x-cloak
                                    :src="preview"
                                    alt="{{ __('Logo preview') }}"
                                    class="size-24 rounded-lg border border-zinc-200 object-contain bg-white p-1 dark:border-zinc-700 dark:bg-zinc-800"
                                />
                                {{-- Server-rendered logo or placeholder --}}
                                <div x-show="!preview">
                                    @if ($school->logo_url)
                                        <img
                                            src="{{ $school->logo_url }}"
                                            alt="{{ $school->name }} logo"
                                            class="size-24 rounded-lg border border-zinc-200 object-contain bg-white p-1 dark:border-zinc-700 dark:bg-zinc-800"
                                        />
                                    @else
                                        <div class="flex size-24 items-center justify-center rounded-lg border-2 border-dashed border-zinc-300 bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800">
                                            <flux:icon.photo class="size-8 text-zinc-400" />
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="space-y-2">
                                <form method="POST" action="{{ route('super-admin.schools.upload-logo', $school) }}" enctype="multipart/form-data" class="space-y-2">
                                    @csrf
                                    <input
                                        type="file"
                                        name="logo"
                                        id="logo-upload-{{ $school->id }}"
                                        accept="image/jpeg,image/png,image/webp,image/svg+xml"
                                        class="block w-full text-sm text-zinc-500 file:mr-2 file:rounded-md file:border-0 file:bg-zinc-100 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-zinc-700 hover:file:bg-zinc-200 dark:file:bg-zinc-700 dark:file:text-zinc-300"
                                        required
                                        @change="
                                            const file = $event.target.files[0];
                                            if (file) {
                                                const reader = new FileReader();
                                                reader.onload = (e) => { preview = e.target.result; };
                                                reader.readAsDataURL(file);
                                            } else {
                                                preview = null;
                                            }
                                        "
                                    />
                                    @error('logo') <flux:error>{{ $message }}</flux:error> @enderror
                                    <flux:text size="xs" class="text-zinc-500">{{ __('JPG, PNG, WebP or SVG. Max 2 MB.') }}</flux:text>
                                    <flux:button type="submit" variant="primary" size="xs" icon="arrow-up-tray">
                                        {{ $school->logo_url ? __('Replace Logo') : __('Upload Logo') }}
                                    </flux:button>
                                </form>
                                @if ($school->logo_url)
                                    <form method="POST" action="{{ route('super-admin.schools.remove-logo', $school) }}">
                                        @csrf
                                        @method('DELETE')
                                        <flux:button type="submit" variant="danger" size="xs" icon="trash">
                                            {{ __('Remove Logo') }}
                                        </flux:button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Colors --}}
                    <div class="space-y-4">
                        <flux:text size="sm" class="font-semibold">{{ __('Brand Colors') }}</flux:text>
                        <div class="space-y-3">
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
                            <flux:button variant="subtle" size="xs" icon="pencil-square" href="{{ route('super-admin.schools.edit', $school) }}" wire:navigate>
                                {{ __('Edit Colors') }}
                            </flux:button>
                        </div>
                    </div>
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
                    {{-- Domain + verification status --}}
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="flex items-center gap-2">
                            <flux:icon.globe-alt class="size-5 text-zinc-500" />
                            <span class="font-mono text-sm font-medium">{{ $school->custom_domain }}</span>
                            <a href="https://{{ $school->custom_domain }}" target="_blank" rel="noopener noreferrer" class="ml-1 text-xs text-zinc-500 hover:underline">
                                {{ __('Open ↗') }}
                            </a>
                        </div>

                        @if ($school->domain_verified_at)
                            <span class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                <flux:icon.check-circle class="size-3.5" />
                                {{ __('Verified') }}
                            </span>
                            <span class="text-xs text-zinc-500">{{ $school->domain_verified_at->diffForHumans() }}</span>
                        @else
                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">
                                <flux:icon.clock class="size-3.5" />
                                {{ __('Unverified') }}
                            </span>
                        @endif
                    </div>

                    {{-- Verify button --}}
                    <form method="POST" action="{{ route('super-admin.schools.verify-domain', $school) }}">
                        @csrf
                        <flux:button type="submit" variant="primary" size="sm" icon="arrow-path">
                            {{ $school->domain_verified_at ? __('Re-check Domain') : __('Verify Domain') }}
                        </flux:button>
                    </form>

                    {{-- Flash messages for verification results --}}
                    @if (session('warning'))
                        <flux:callout variant="warning" icon="exclamation-triangle">
                            <flux:text size="sm">{{ session('warning') }}</flux:text>
                        </flux:callout>
                    @endif

                    @unless ($school->domain_verified_at)
                        {{-- DNS instructions (shown only when not verified) --}}
                        <flux:callout variant="secondary" icon="information-circle">
                            <flux:heading size="sm">{{ __('DNS setup required') }}</flux:heading>
                            <flux:text size="sm" class="mt-1">
                                {{ __('Have the school add ONE of the following DNS record options at their domain registrar. For subdomains like portal.pearschool.com, the school adds records for "portal" (not "@").') }}
                            </flux:text>
                        </flux:callout>

                        @php
                            $isSubdomain = substr_count($school->custom_domain, '.') > 1;
                            $dnsName = $isSubdomain ? explode('.', $school->custom_domain, 2)[0] : '@';
                        @endphp

                        {{-- Option A: CNAME record (recommended) --}}
                        <div class="space-y-2">
                            <flux:text size="sm" class="font-semibold">{{ __('Option A — CNAME Record (Recommended)') }}</flux:text>
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
                                            <td class="px-3 py-2">CNAME</td>
                                            <td class="px-3 py-2">{{ $dnsName }}</td>
                                            <td class="px-3 py-2">{{ $platformDomain }}</td>
                                            <td class="px-3 py-2">3600</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            @unless ($isSubdomain)
                                <flux:text size="xs" class="text-zinc-500">
                                    {{ __('Note: Some registrars do not support CNAME on the root (@). In that case, use Option B, or consider using a subdomain like portal.pearschool.com.') }}
                                </flux:text>
                            @endunless
                        </div>

                        {{-- Option B: A record --}}
                        <div class="space-y-2">
                            <flux:text size="sm" class="font-semibold">{{ __('Option B — A Record (if CNAME is not supported)') }}</flux:text>
                            @if ($serverIp)
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
                                                <td class="px-3 py-2">{{ $dnsName }}</td>
                                                <td class="px-3 py-2">{{ $serverIp }}</td>
                                                <td class="px-3 py-2">3600</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <flux:callout variant="warning" icon="exclamation-triangle">
                                    <flux:text size="sm">{{ __('Server IP not configured. Add SERVER_IP to your .env file to display A record instructions.') }}</flux:text>
                                </flux:callout>
                            @endif
                        </div>

                        <flux:text size="sm" class="text-zinc-500">
                            {{ __('After DNS propagates (5 minutes to 48 hours), SSL is auto-provisioned via cPanel AutoSSL. Then click "Verify Domain" above to confirm everything is working.') }}
                        </flux:text>
                    @endunless

                    @if ($school->domain_verified_at)
                        <flux:callout variant="success" icon="check-circle">
                            <flux:text size="sm">
                                {{ __('This domain is verified and serving the portal at') }}
                                <a href="https://{{ $school->custom_domain }}/portal/login" target="_blank" class="font-mono underline">https://{{ $school->custom_domain }}/portal/login</a>.
                            </flux:text>
                        </flux:callout>
                    @endif
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
