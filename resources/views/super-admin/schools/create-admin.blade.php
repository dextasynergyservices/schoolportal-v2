<x-layouts::app :title="__('Add School Admin')">
    <div class="space-y-6">
        <x-admin-header
            :title="__('Add School Admin')"
            :description="__('Create an additional admin account for :school.', ['school' => $school->name])"
        >
            <flux:button variant="subtle" size="sm" icon="arrow-left" href="{{ route('super-admin.schools.show', $school) }}" wire:navigate>
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

        <form method="POST" action="{{ route('super-admin.schools.store-admin', $school) }}" class="space-y-6">
            @csrf

            <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900 sm:p-6">
                <flux:heading size="lg" class="mb-4">{{ __('Admin Details') }}</flux:heading>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:field>
                        <flux:label for="name">{{ __('Full Name') }} <span class="text-red-500">*</span></flux:label>
                        <flux:input id="name" name="name" :value="old('name')" required maxlength="255" placeholder="e.g. Mrs. Adamu" />
                        @error('name') <flux:error>{{ $message }}</flux:error> @enderror
                    </flux:field>

                    <flux:field>
                        <flux:label for="username">{{ __('Username') }} <span class="text-red-500">*</span></flux:label>
                        <flux:input id="username" name="username" :value="old('username')" required maxlength="100" placeholder="e.g. adamu_admin" />
                        <flux:description>{{ __('Used to log in. Only letters, numbers, dots, hyphens, and underscores.') }}</flux:description>
                        @error('username') <flux:error>{{ $message }}</flux:error> @enderror
                    </flux:field>

                    <flux:field>
                        <flux:label for="email">{{ __('Email') }}</flux:label>
                        <flux:input id="email" name="email" type="email" :value="old('email')" maxlength="255" placeholder="e.g. admin@school.com" />
                        <flux:description>{{ __('Optional. Used for password resets and notifications.') }}</flux:description>
                        @error('email') <flux:error>{{ $message }}</flux:error> @enderror
                    </flux:field>

                    <flux:field>
                        <flux:label for="phone">{{ __('Phone') }}</flux:label>
                        <flux:input id="phone" name="phone" :value="old('phone')" maxlength="20" placeholder="e.g. 08012345678" />
                        @error('phone') <flux:error>{{ $message }}</flux:error> @enderror
                    </flux:field>

                    <flux:field class="sm:col-span-2">
                        <flux:label for="password">{{ __('Password') }} <span class="text-red-500">*</span></flux:label>
                        <flux:input id="password" name="password" type="password" required viewable />
                        <flux:description>{{ __('The admin will be required to change this on first login.') }}</flux:description>
                        @error('password') <flux:error>{{ $message }}</flux:error> @enderror
                    </flux:field>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <flux:button variant="ghost" href="{{ route('super-admin.schools.show', $school) }}" wire:navigate>
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button type="submit" variant="primary">
                    {{ __('Create Admin') }}
                </flux:button>
            </div>
        </form>
    </div>
</x-layouts::app>
