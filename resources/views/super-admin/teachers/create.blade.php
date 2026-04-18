<x-layouts::app :title="__('Add Teacher')">
    <div class="space-y-6">
        <x-admin-header
            :title="__('Add Teacher')"
            :description="__('Create a new teacher account in any school.')"
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

        <form method="POST" action="{{ route('super-admin.teachers.store') }}" class="space-y-6" x-data="{
            schoolId: '{{ old('school_id', request('school_id')) }}',
        }">
            @csrf

            {{-- School Selection --}}
            <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900 sm:p-6">
                <flux:heading size="lg" class="mb-4">{{ __('School') }}</flux:heading>

                <flux:field>
                    <label for="school_id" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Select School') }} <span class="text-red-500">*</span></label>
                    <select
                        id="school_id"
                        name="school_id"
                        required
                        x-model="schoolId"
                        x-on:change="window.location.href = '{{ route('super-admin.teachers.create') }}?school_id=' + schoolId"
                        class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-[var(--color-primary)] focus:ring-[var(--color-primary)] dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
                        aria-describedby="school-help"
                    >
                        <option value="">{{ __('— Choose a school —') }}</option>
                        @foreach ($schools as $school)
                            <option value="{{ $school->id }}" @selected(old('school_id', request('school_id')) == $school->id)>
                                {{ $school->name }}
                            </option>
                        @endforeach
                    </select>
                    <flux:description id="school-help">{{ __('The teacher will be created under this school.') }}</flux:description>
                    @error('school_id') <flux:error>{{ $message }}</flux:error> @enderror
                </flux:field>
            </div>

            @if ($levels->isNotEmpty() || $classes->isNotEmpty())
                {{-- Basic Info --}}
                <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900 sm:p-6">
                    <flux:heading size="lg" class="mb-4">{{ __('Basic Information') }}</flux:heading>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <flux:field>
                            <flux:label for="name">{{ __('Full Name') }} <span class="text-red-500">*</span></flux:label>
                            <flux:input id="name" name="name" :value="old('name')" required />
                            @error('name') <flux:error>{{ $message }}</flux:error> @enderror
                        </flux:field>

                        <flux:field>
                            <flux:label for="username">{{ __('Username') }} <span class="text-red-500">*</span></flux:label>
                            <flux:input id="username" name="username" :value="old('username')" required />
                            @error('username') <flux:error>{{ $message }}</flux:error> @enderror
                        </flux:field>

                        <flux:field>
                            <flux:label for="email">{{ __('Email') }}</flux:label>
                            <flux:input id="email" name="email" type="email" :value="old('email')" />
                            @error('email') <flux:error>{{ $message }}</flux:error> @enderror
                        </flux:field>

                        <flux:field>
                            <flux:label for="password">{{ __('Password') }} <span class="text-red-500">*</span></flux:label>
                            <flux:input id="password" name="password" type="password" required />
                            @error('password') <flux:error>{{ $message }}</flux:error> @enderror
                        </flux:field>

                        <flux:field>
                            <flux:label for="phone">{{ __('Phone') }}</flux:label>
                            <flux:input id="phone" name="phone" :value="old('phone')" />
                            @error('phone') <flux:error>{{ $message }}</flux:error> @enderror
                        </flux:field>

                        <flux:field>
                            <label for="gender" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Gender') }}</label>
                            <select
                                id="gender"
                                name="gender"
                                class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-[var(--color-primary)] focus:ring-[var(--color-primary)] dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
                            >
                                <option value="">{{ __('Select') }}</option>
                                <option value="male" @selected(old('gender') === 'male')>{{ __('Male') }}</option>
                                <option value="female" @selected(old('gender') === 'female')>{{ __('Female') }}</option>
                                <option value="other" @selected(old('gender') === 'other')>{{ __('Other') }}</option>
                            </select>
                            @error('gender') <flux:error>{{ $message }}</flux:error> @enderror
                        </flux:field>
                    </div>
                </div>

                {{-- Level & Class Assignment --}}
                <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900 sm:p-6">
                    <flux:heading size="lg" class="mb-4">{{ __('Level & Class Assignment') }}</flux:heading>

                    @if ($levels->isNotEmpty())
                        <flux:field class="mb-4">
                            <label for="level_id" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Level') }}</label>
                            <select
                                id="level_id"
                                name="level_id"
                                class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-[var(--color-primary)] focus:ring-[var(--color-primary)] dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
                            >
                                <option value="">{{ __('Select Level (optional)') }}</option>
                                @foreach ($levels as $level)
                                    <option value="{{ $level->id }}" @selected(old('level_id') == $level->id)>
                                        {{ $level->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('level_id') <flux:error>{{ $message }}</flux:error> @enderror
                        </flux:field>
                    @endif

                    @if ($classes->isNotEmpty())
                        <fieldset>
                            <legend class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Assign to Classes') }}</legend>
                            <flux:description>{{ __('Select one or more classes for this teacher. Only unassigned classes are shown.') }}</flux:description>
                            <div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach ($classes as $class)
                                    <label class="flex items-center gap-2 rounded-md border border-zinc-200 px-3 py-2 text-sm hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800">
                                        <input
                                            type="checkbox"
                                            name="class_ids[]"
                                            value="{{ $class->id }}"
                                            class="rounded border-zinc-300 text-[var(--color-primary)] focus:ring-[var(--color-primary)]"
                                            @checked(in_array($class->id, old('class_ids', [])))
                                        />
                                        <span>{{ $class->name }}</span>
                                        @if ($class->level)
                                            <flux:badge size="sm" class="ml-auto">{{ $class->level->name }}</flux:badge>
                                        @endif
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    @else
                        <flux:text class="text-zinc-500">{{ __('All classes in this school already have assigned teachers.') }}</flux:text>
                    @endif
                </div>

                <div class="flex items-center gap-3">
                    <flux:button type="submit" variant="primary">{{ __('Create Teacher') }}</flux:button>
                    <flux:button variant="ghost" href="{{ route('super-admin.teachers.index') }}" wire:navigate>{{ __('Cancel') }}</flux:button>
                </div>
            @elseif (!request('school_id'))
                <div class="rounded-lg border border-dashed border-zinc-300 p-8 text-center dark:border-zinc-700">
                    <flux:icon.user-group class="mx-auto size-8 text-zinc-400" />
                    <flux:text class="mt-2 text-zinc-500">{{ __('Select a school above to continue.') }}</flux:text>
                </div>
            @else
                <flux:callout variant="warning" icon="exclamation-triangle">
                    {{ __('This school has no active levels or classes. Please set up levels and classes first.') }}
                </flux:callout>
            @endif
        </form>
    </div>
</x-layouts::app>
