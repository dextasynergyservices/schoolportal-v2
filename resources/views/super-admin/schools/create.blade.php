<x-layouts::app :title="__('New School')">
    <div class="space-y-6">
        <x-admin-header
            :title="__('New School')"
            :description="__('Onboard a new school in five quick steps.')"
        />

        @if ($errors->any())
            <flux:callout variant="danger" icon="exclamation-triangle">
                <ul class="list-disc space-y-1 pl-5 text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </flux:callout>
        @endif

        <div
            x-data="{
                step: 1,
                totalSteps: 5,
                next() { if (this.step < this.totalSteps) this.step++; },
                prev() { if (this.step > 1) this.step--; },
            }"
            class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900"
        >
            {{-- Progress indicator --}}
            <div class="border-b border-zinc-200 p-4 dark:border-zinc-700">
                <div class="flex items-center justify-between gap-2 text-xs font-medium text-zinc-500">
                    <span>{{ __('Step') }} <span x-text="step"></span> {{ __('of') }} <span x-text="totalSteps"></span></span>
                    <span x-text="[
                        '{{ __('School Information') }}',
                        '{{ __('School Levels') }}',
                        '{{ __('Classes') }}',
                        '{{ __('First Admin') }}',
                        '{{ __('First Session') }}',
                    ][step - 1]"></span>
                </div>
                <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                    <div
                        class="h-full rounded-full bg-[var(--color-primary)] transition-all duration-300"
                        :style="`width: ${(step / totalSteps) * 100}%`"
                        role="progressbar"
                        :aria-valuenow="step"
                        aria-valuemin="1"
                        aria-valuemax="5"
                    ></div>
                </div>
            </div>

            <form method="POST" action="{{ route('super-admin.schools.store') }}" class="p-4 sm:p-6">
                @csrf

                {{-- Step 1: School Information --}}
                <div x-show="step === 1" x-cloak class="space-y-4">
                    <flux:heading size="lg">{{ __('School Information') }}</flux:heading>

                    <flux:field>
                        <flux:label>{{ __('School Name') }} *</flux:label>
                        <flux:input name="name" :value="old('name')" required autofocus />
                    </flux:field>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <flux:field>
                            <flux:label>{{ __('Email') }} *</flux:label>
                            <flux:input type="email" name="email" :value="old('email')" required />
                        </flux:field>
                        <flux:field>
                            <flux:label>{{ __('Phone') }}</flux:label>
                            <flux:input name="phone" :value="old('phone')" />
                        </flux:field>
                    </div>

                    <flux:field>
                        <flux:label>{{ __('Custom Domain') }}</flux:label>
                        <flux:input name="custom_domain" :value="old('custom_domain')" placeholder="pearschool.com" />
                        <flux:description>{{ __("The school's own domain. DNS setup is shown after creation.") }}</flux:description>
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Motto') }}</flux:label>
                        <flux:input name="motto" :value="old('motto')" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Address') }}</flux:label>
                        <flux:textarea name="address" rows="2">{{ old('address') }}</flux:textarea>
                    </flux:field>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <flux:field>
                            <flux:label>{{ __('City') }}</flux:label>
                            <flux:input name="city" :value="old('city')" />
                        </flux:field>
                        <flux:field>
                            <flux:label>{{ __('State') }}</flux:label>
                            <flux:input name="state" :value="old('state')" />
                        </flux:field>
                        <flux:field>
                            <flux:label>{{ __('Country') }}</flux:label>
                            <flux:input name="country" :value="old('country', 'Nigeria')" />
                        </flux:field>
                    </div>

                    <flux:field>
                        <flux:label>{{ __('Website') }}</flux:label>
                        <flux:input type="url" name="website" :value="old('website')" placeholder="https://..." />
                    </flux:field>
                </div>

                {{-- Step 2: Levels --}}
                <div x-show="step === 2" x-cloak class="space-y-4">
                    <flux:heading size="lg">{{ __('School Levels') }}</flux:heading>
                    <flux:text class="text-zinc-500">{{ __('Select the levels this school will offer.') }}</flux:text>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        @foreach ($levelNames as $slug => $name)
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-zinc-200 p-4 hover:bg-zinc-50 has-[:checked]:border-[var(--color-primary)] has-[:checked]:bg-indigo-50 dark:border-zinc-700 dark:hover:bg-zinc-800 dark:has-[:checked]:bg-indigo-950/40">
                                <input
                                    type="checkbox"
                                    name="levels[]"
                                    value="{{ $slug }}"
                                    @checked(in_array($slug, (array) old('levels', ['nursery', 'primary'])))
                                    class="h-5 w-5 rounded border-zinc-300 text-[var(--color-primary)] focus:ring-[var(--color-primary)]"
                                />
                                <div>
                                    <div class="font-medium">{{ $name }}</div>
                                    <div class="text-xs text-zinc-500">
                                        {{ implode(', ', $levelPresets[$slug]) }}
                                    </div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Step 3: Classes --}}
                <div x-show="step === 3" x-cloak class="space-y-4">
                    <flux:heading size="lg">{{ __('Classes per Level') }}</flux:heading>
                    <flux:text class="text-zinc-500">
                        {{ __('Default classes are pre-filled. Leave as-is or customize (comma-separated).') }}
                    </flux:text>

                    @foreach ($levelNames as $slug => $name)
                        <flux:field>
                            <flux:label>{{ $name }}</flux:label>
                            <flux:input
                                name="classes_csv[{{ $slug }}]"
                                :value="old('classes_csv.'.$slug, implode(', ', $levelPresets[$slug]))"
                                placeholder="{{ __('Class names, comma-separated') }}"
                            />
                            <flux:description>
                                {{ __('Only applied if ":name" is selected in Step 2.', ['name' => $name]) }}
                            </flux:description>
                        </flux:field>
                    @endforeach

                    {{-- Convert the CSV rows into the array shape the controller expects --}}
                    @foreach ($levelNames as $slug => $name)
                        @php
                            $raw = old('classes_csv.'.$slug, implode(', ', $levelPresets[$slug]));
                            $items = array_values(array_filter(array_map('trim', explode(',', (string) $raw))));
                        @endphp
                        @foreach ($items as $i => $className)
                            <input type="hidden" name="classes[{{ $slug }}][{{ $i }}]" value="{{ $className }}" />
                        @endforeach
                    @endforeach
                </div>

                {{-- Step 4: First Admin --}}
                <div x-show="step === 4" x-cloak class="space-y-4">
                    <flux:heading size="lg">{{ __('First Admin Account') }}</flux:heading>
                    <flux:text class="text-zinc-500">{{ __('This admin will manage the school.') }}</flux:text>

                    <flux:field>
                        <flux:label>{{ __('Full Name') }} *</flux:label>
                        <flux:input name="admin_name" :value="old('admin_name')" required />
                    </flux:field>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <flux:field>
                            <flux:label>{{ __('Email') }} *</flux:label>
                            <flux:input type="email" name="admin_email" :value="old('admin_email')" required />
                        </flux:field>
                        <flux:field>
                            <flux:label>{{ __('Username') }} *</flux:label>
                            <flux:input name="admin_username" :value="old('admin_username')" required />
                        </flux:field>
                    </div>

                    <flux:field>
                        <flux:label>{{ __('Phone') }}</flux:label>
                        <flux:input name="admin_phone" :value="old('admin_phone')" />
                    </flux:field>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <x-password-input name="admin_password" :label="__('Password')" required />
                        <x-password-input name="admin_password_confirmation" :label="__('Confirm Password')" required :with-strength-meter="false" />
                    </div>
                </div>

                {{-- Step 5: First Session --}}
                <div x-show="step === 5" x-cloak class="space-y-4">
                    <flux:heading size="lg">{{ __('First Academic Session') }}</flux:heading>
                    <flux:text class="text-zinc-500">{{ __('Three terms will be created automatically.') }}</flux:text>

                    <flux:field>
                        <flux:label>{{ __('Session Name') }} *</flux:label>
                        <flux:input name="session_name" :value="old('session_name', date('Y').'/'.(date('Y')+1))" required />
                    </flux:field>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <flux:field>
                            <flux:label>{{ __('Start Date') }} *</flux:label>
                            <flux:input type="date" name="session_start_date" :value="old('session_start_date')" required />
                        </flux:field>
                        <flux:field>
                            <flux:label>{{ __('End Date') }} *</flux:label>
                            <flux:input type="date" name="session_end_date" :value="old('session_end_date')" required />
                        </flux:field>
                    </div>
                </div>

                {{-- Navigation --}}
                <div class="mt-6 flex items-center justify-between gap-3 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                    <flux:button
                        type="button"
                        variant="subtle"
                        x-show="step > 1"
                        x-cloak
                        @click="prev()"
                        icon="arrow-left"
                    >
                        {{ __('Back') }}
                    </flux:button>
                    <flux:button
                        type="button"
                        variant="ghost"
                        href="{{ route('super-admin.schools.index') }}"
                        wire:navigate
                        x-show="step === 1"
                        x-cloak
                    >
                        {{ __('Cancel') }}
                    </flux:button>

                    <div class="ml-auto">
                        <flux:button
                            type="button"
                            variant="filled"
                            x-show="step < totalSteps"
                            x-cloak
                            @click="next()"
                            icon:trailing="arrow-right"
                        >
                            {{ __('Next') }}
                        </flux:button>
                        <flux:button
                            type="submit"
                            variant="primary"
                            x-show="step === totalSteps"
                            x-cloak
                            icon="check"
                        >
                            {{ __('Create School') }}
                        </flux:button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-layouts::app>
