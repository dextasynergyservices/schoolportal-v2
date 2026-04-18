<x-layouts::app :title="__('Edit :name', ['name' => $school->name])">
    <div class="space-y-6">
        <x-admin-header
            :title="__('Edit School')"
            :description="$school->name"
        >
            <flux:button
                variant="ghost"
                icon="arrow-left"
                href="{{ route('super-admin.schools.show', $school) }}"
                wire:navigate
            >
                {{ __('Back') }}
            </flux:button>
        </x-admin-header>

        @if ($errors->any())
            <flux:callout variant="danger" icon="exclamation-triangle">
                <ul class="list-disc space-y-1 pl-5 text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </flux:callout>
        @endif

        @php
            $branding = $school->settings['branding'] ?? [];
            $primary = $branding['primary_color'] ?? '#4F46E5';
            $secondary = $branding['secondary_color'] ?? '#F59E0B';
            $accent = $branding['accent_color'] ?? '#10B981';
        @endphp

        <form
            method="POST"
            action="{{ route('super-admin.schools.update', $school) }}"
            class="space-y-6"
        >
            @csrf
            @method('PUT')

            {{-- School Information --}}
            <section class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900 sm:p-6">
                <flux:heading size="lg">{{ __('School Information') }}</flux:heading>
                <flux:text class="mt-1 text-zinc-500">{{ __('Basic details and contact information.') }}</flux:text>

                <div class="mt-4 space-y-4">
                    <flux:field>
                        <flux:label>{{ __('School Name') }} *</flux:label>
                        <flux:input name="name" :value="old('name', $school->name)" required />
                    </flux:field>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <flux:field>
                            <flux:label>{{ __('Email') }} *</flux:label>
                            <flux:input type="email" name="email" :value="old('email', $school->email)" required />
                        </flux:field>
                        <flux:field>
                            <flux:label>{{ __('Phone') }}</flux:label>
                            <flux:input name="phone" :value="old('phone', $school->phone)" />
                        </flux:field>
                    </div>

                    <flux:field>
                        <flux:label>{{ __('Custom Domain') }}</flux:label>
                        <flux:input
                            name="custom_domain"
                            :value="old('custom_domain', $school->custom_domain)"
                            placeholder="pearschool.com"
                        />
                        <flux:description>
                            {{ __('The school accesses the portal on this domain. DNS setup instructions are on the school details page.') }}
                        </flux:description>
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Motto') }}</flux:label>
                        <flux:input name="motto" :value="old('motto', $school->motto)" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Address') }}</flux:label>
                        <flux:textarea name="address" rows="2">{{ old('address', $school->address) }}</flux:textarea>
                    </flux:field>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <flux:field>
                            <flux:label>{{ __('City') }}</flux:label>
                            <flux:input name="city" :value="old('city', $school->city)" />
                        </flux:field>
                        <flux:field>
                            <flux:label>{{ __('State') }}</flux:label>
                            <flux:input name="state" :value="old('state', $school->state)" />
                        </flux:field>
                        <flux:field>
                            <flux:label>{{ __('Country') }}</flux:label>
                            <flux:input name="country" :value="old('country', $school->country)" />
                        </flux:field>
                    </div>

                    <flux:field>
                        <flux:label>{{ __('Website') }}</flux:label>
                        <flux:input
                            type="url"
                            name="website"
                            :value="old('website', $school->website)"
                            placeholder="https://..."
                        />
                    </flux:field>
                </div>
            </section>

            {{-- Branding --}}
            <section
                x-data="{
                    primary: '{{ old('primary_color', $primary) }}',
                    secondary: '{{ old('secondary_color', $secondary) }}',
                    accent: '{{ old('accent_color', $accent) }}',
                }"
                class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900 sm:p-6"
            >
                <flux:heading size="lg">{{ __('Branding') }}</flux:heading>
                <flux:text class="mt-1 text-zinc-500">
                    {{ __('These colors appear throughout the school portal.') }}
                </flux:text>

                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                    @foreach ([
                        ['label' => __('Primary'), 'name' => 'primary_color', 'model' => 'primary'],
                        ['label' => __('Secondary'), 'name' => 'secondary_color', 'model' => 'secondary'],
                        ['label' => __('Accent'), 'name' => 'accent_color', 'model' => 'accent'],
                    ] as $color)
                        <div class="space-y-2">
                            <label for="{{ $color['name'] }}" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                {{ $color['label'] }}
                            </label>
                            <div class="flex items-center gap-3">
                                <input
                                    type="color"
                                    id="{{ $color['name'] }}"
                                    name="{{ $color['name'] }}"
                                    x-model="{{ $color['model'] }}"
                                    class="h-11 w-14 shrink-0 cursor-pointer rounded border border-zinc-300 bg-transparent p-1 dark:border-zinc-600"
                                    aria-describedby="{{ $color['name'] }}-hex"
                                />
                                <input
                                    type="text"
                                    x-model="{{ $color['model'] }}"
                                    id="{{ $color['name'] }}-hex"
                                    class="block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 font-mono text-sm text-zinc-900 shadow-sm focus:border-[var(--color-primary)] focus:ring-[var(--color-primary)] dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
                                    pattern="^#[0-9A-Fa-f]{6}$"
                                    aria-label="{{ __(':label hex code', ['label' => $color['label']]) }}"
                                />
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Live preview --}}
                <div class="mt-6 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <flux:text size="xs" class="mb-3 uppercase tracking-wide text-zinc-500">
                        {{ __('Preview') }}
                    </flux:text>
                    <div class="flex flex-wrap items-center gap-3">
                        <span
                            class="inline-flex h-10 items-center rounded-md px-4 text-sm font-medium text-white"
                            :style="`background-color: ${primary}`"
                        >
                            {{ __('Primary Button') }}
                        </span>
                        <span
                            class="inline-flex h-10 items-center rounded-md px-4 text-sm font-medium text-white"
                            :style="`background-color: ${secondary}`"
                        >
                            {{ __('Secondary') }}
                        </span>
                        <span
                            class="inline-flex h-10 items-center rounded-md px-4 text-sm font-medium text-white"
                            :style="`background-color: ${accent}`"
                        >
                            {{ __('Accent') }}
                        </span>
                    </div>
                </div>
            </section>

            {{-- Actions --}}
            <div class="flex items-center justify-end gap-3">
                <flux:button
                    variant="ghost"
                    href="{{ route('super-admin.schools.show', $school) }}"
                    wire:navigate
                >
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button type="submit" variant="primary" icon="check">
                    {{ __('Save Changes') }}
                </flux:button>
            </div>
        </form>

        {{-- Reset School Admin Password --}}
        @if ($primaryAdmin)
            <section class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900 sm:p-6" x-data="{ showResetForm: false }">
                <flux:heading size="lg">{{ __('School Admin Password') }}</flux:heading>
                <flux:text class="mt-1 text-zinc-500">
                    {{ __('Reset the password for :name (:email). They will be forced to change it on next login.', ['name' => $primaryAdmin->name, 'email' => $primaryAdmin->email ?? $primaryAdmin->username]) }}
                </flux:text>

                <div class="mt-4">
                    <flux:button variant="danger" size="sm" icon="key" x-on:click="showResetForm = !showResetForm">
                        {{ __('Reset Password') }}
                    </flux:button>

                    <form
                        method="POST"
                        action="{{ route('super-admin.schools.reset-admin-password', $school) }}"
                        x-show="showResetForm"
                        x-cloak
                        x-transition
                        class="mt-4 max-w-md space-y-3"
                    >
                        @csrf
                        <input type="hidden" name="admin_id" value="{{ $primaryAdmin->id }}">

                        <flux:field>
                            <flux:label for="reset-pw">{{ __('New Password') }}</flux:label>
                            <flux:input id="reset-pw" name="password" type="password" required viewable />
                            <flux:description>{{ __('Minimum 8 characters. The admin will be forced to change this on their next login.') }}</flux:description>
                            @error('password') <flux:error>{{ $message }}</flux:error> @enderror
                        </flux:field>

                        <flux:button type="submit" variant="primary" size="sm">{{ __('Reset Password') }}</flux:button>
                    </form>
                </div>
            </section>
        @endif
    </div>
</x-layouts::app>
