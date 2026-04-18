<x-layouts::app :title="__('Add Parent')">
    <div class="space-y-6">
        <x-admin-header
            :title="__('Add Parent')"
            :description="__('Create a new parent account in any school and link to students.')"
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

        <form method="POST" action="{{ route('super-admin.parents.store') }}" class="space-y-6" x-data="{
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
                        x-on:change="window.location.href = '{{ route('super-admin.parents.create') }}?school_id=' + schoolId"
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
                    <flux:description id="school-help">{{ __('The parent will be created under this school.') }}</flux:description>
                    @error('school_id') <flux:error>{{ $message }}</flux:error> @enderror
                </flux:field>
            </div>

            @if ($students->isNotEmpty() || (request('school_id') && $students->isEmpty()))
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

                        <flux:field>
                            <label for="relationship" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Relationship') }}</label>
                            <select
                                id="relationship"
                                name="relationship"
                                class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-[var(--color-primary)] focus:ring-[var(--color-primary)] dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
                            >
                                <option value="">{{ __('Select') }}</option>
                                <option value="father" @selected(old('relationship') === 'father')>{{ __('Father') }}</option>
                                <option value="mother" @selected(old('relationship') === 'mother')>{{ __('Mother') }}</option>
                                <option value="guardian" @selected(old('relationship') === 'guardian')>{{ __('Guardian') }}</option>
                                <option value="other" @selected(old('relationship') === 'other')>{{ __('Other') }}</option>
                            </select>
                            @error('relationship') <flux:error>{{ $message }}</flux:error> @enderror
                        </flux:field>

                        <flux:field>
                            <flux:label for="occupation">{{ __('Occupation') }}</flux:label>
                            <flux:input id="occupation" name="occupation" :value="old('occupation')" />
                        </flux:field>

                        <flux:field class="sm:col-span-2">
                            <flux:label for="address">{{ __('Address') }}</flux:label>
                            <flux:textarea id="address" name="address" rows="2">{{ old('address') }}</flux:textarea>
                        </flux:field>
                    </div>
                </div>

                {{-- Link Students --}}
                <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900 sm:p-6">
                    <flux:heading size="lg" class="mb-1">{{ __('Link to Students') }}</flux:heading>
                    <flux:description class="mb-4">{{ __('Select one or more students this parent is linked to.') }} <span class="text-red-500">*</span></flux:description>

                    @if ($students->isNotEmpty())
                        <div class="max-h-64 space-y-2 overflow-y-auto rounded-md border border-zinc-200 p-3 dark:border-zinc-700" role="group" aria-label="{{ __('Students') }}">
                            @foreach ($students as $student)
                                <label class="flex items-center gap-3 rounded-md px-2 py-1.5 hover:bg-zinc-50 dark:hover:bg-zinc-800">
                                    <input
                                        type="checkbox"
                                        name="student_ids[]"
                                        value="{{ $student->id }}"
                                        class="rounded border-zinc-300 text-[var(--color-primary)] focus:ring-[var(--color-primary)]"
                                        @checked(in_array($student->id, old('student_ids', [])))
                                    />
                                    <div class="min-w-0 flex-1">
                                        <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $student->name }}</span>
                                        @if ($student->studentProfile?->class)
                                            <flux:badge size="sm" class="ml-2">{{ $student->studentProfile->class->name }}</flux:badge>
                                        @endif
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <flux:callout variant="warning" icon="exclamation-triangle">
                            {{ __('No active students found in this school. Please add students first.') }}
                        </flux:callout>
                    @endif
                    @error('student_ids') <flux:error>{{ $message }}</flux:error> @enderror
                </div>

                @if ($students->isNotEmpty())
                    <div class="flex items-center gap-3">
                        <flux:button type="submit" variant="primary">{{ __('Create Parent') }}</flux:button>
                        <flux:button variant="ghost" href="{{ route('super-admin.parents.index') }}" wire:navigate>{{ __('Cancel') }}</flux:button>
                    </div>
                @endif
            @elseif (!request('school_id'))
                <div class="rounded-lg border border-dashed border-zinc-300 p-8 text-center dark:border-zinc-700">
                    <flux:icon.users class="mx-auto size-8 text-zinc-400" />
                    <flux:text class="mt-2 text-zinc-500">{{ __('Select a school above to continue.') }}</flux:text>
                </div>
            @endif
        </form>
    </div>
</x-layouts::app>
