<x-layouts::app :title="__('Add Student')">
    <div class="space-y-6">
        <x-admin-header
            :title="__('Add Student')"
            :description="__('Create a new student account in any school.')"
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

        <form method="POST" action="{{ route('super-admin.students.store') }}" class="space-y-6" x-data="{
            schoolId: '{{ old('school_id', request('school_id')) }}',
            levelId: '{{ old('level_id') }}',
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
                        x-on:change="window.location.href = '{{ route('super-admin.students.create') }}?school_id=' + schoolId"
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
                    <flux:description id="school-help">{{ __('The student will be created under this school.') }}</flux:description>
                    @error('school_id') <flux:error>{{ $message }}</flux:error> @enderror
                </flux:field>
            </div>

            @if ($levels->isNotEmpty())
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
                            <flux:label for="password">{{ __('Password') }} <span class="text-red-500">*</span></flux:label>
                            <flux:input id="password" name="password" type="password" required />
                            @error('password') <flux:error>{{ $message }}</flux:error> @enderror
                        </flux:field>

                        <flux:field>
                            <label for="gender" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Gender') }} <span class="text-red-500">*</span></label>
                            <select
                                id="gender"
                                name="gender"
                                required
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

                {{-- Academic --}}
                <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900 sm:p-6">
                    <flux:heading size="lg" class="mb-4">{{ __('Academic') }}</flux:heading>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <flux:field>
                            <label for="level_id" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Level') }} <span class="text-red-500">*</span></label>
                            <select
                                id="level_id"
                                name="level_id"
                                required
                                x-model="levelId"
                                class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-[var(--color-primary)] focus:ring-[var(--color-primary)] dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
                            >
                                <option value="">{{ __('Select Level') }}</option>
                                @foreach ($levels as $level)
                                    <option value="{{ $level->id }}" @selected(old('level_id') == $level->id)>
                                        {{ $level->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('level_id') <flux:error>{{ $message }}</flux:error> @enderror
                        </flux:field>

                        <flux:field>
                            <label for="class_id" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Class') }} <span class="text-red-500">*</span></label>
                            <select
                                id="class_id"
                                name="class_id"
                                required
                                class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-[var(--color-primary)] focus:ring-[var(--color-primary)] dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
                            >
                                <option value="">{{ __('Select Class') }}</option>
                                @foreach ($classes as $class)
                                    <option
                                        value="{{ $class->id }}"
                                        x-show="!levelId || levelId == '{{ $class->level_id }}'"
                                        @selected(old('class_id') == $class->id)
                                    >
                                        {{ $class->name }} ({{ $class->level?->name }})
                                    </option>
                                @endforeach
                            </select>
                            @error('class_id') <flux:error>{{ $message }}</flux:error> @enderror
                        </flux:field>
                    </div>
                </div>

                {{-- Optional Details --}}
                <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900 sm:p-6">
                    <flux:heading size="lg" class="mb-4">{{ __('Optional Details') }}</flux:heading>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <flux:field>
                            <flux:label for="admission_number">{{ __('Admission Number') }}</flux:label>
                            <flux:input id="admission_number" name="admission_number" :value="old('admission_number')" />
                        </flux:field>

                        <flux:field>
                            <flux:label for="date_of_birth">{{ __('Date of Birth') }}</flux:label>
                            <flux:input id="date_of_birth" name="date_of_birth" type="date" :value="old('date_of_birth')" />
                        </flux:field>

                        <flux:field>
                            <flux:label for="blood_group">{{ __('Blood Group') }}</flux:label>
                            <flux:input id="blood_group" name="blood_group" :value="old('blood_group')" maxlength="5" />
                        </flux:field>

                        <flux:field class="sm:col-span-2">
                            <flux:label for="address">{{ __('Address') }}</flux:label>
                            <flux:textarea id="address" name="address" rows="2">{{ old('address') }}</flux:textarea>
                        </flux:field>

                        <flux:field class="sm:col-span-2">
                            <flux:label for="medical_notes">{{ __('Medical Notes') }}</flux:label>
                            <flux:textarea id="medical_notes" name="medical_notes" rows="2">{{ old('medical_notes') }}</flux:textarea>
                        </flux:field>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <flux:button type="submit" variant="primary">{{ __('Create Student') }}</flux:button>
                    <flux:button variant="ghost" href="{{ route('super-admin.students.index') }}" wire:navigate>{{ __('Cancel') }}</flux:button>
                </div>
            @elseif (!request('school_id'))
                <div class="rounded-lg border border-dashed border-zinc-300 p-8 text-center dark:border-zinc-700">
                    <flux:icon.academic-cap class="mx-auto size-8 text-zinc-400" />
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
