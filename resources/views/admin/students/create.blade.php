<x-layouts::app :title="__('Add Student')">
    <div class="space-y-6">
        <x-admin-header :title="__('Add Student')" />

        <div class="max-w-2xl rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <form method="POST" action="{{ route('admin.students.store') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf

                {{-- Avatar Upload --}}
                <div x-data="{ preview: null }">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">{{ __('Student Photo') }}</label>
                    <div class="flex items-center gap-4">
                        <div class="relative w-20 h-20 rounded-full overflow-hidden bg-zinc-100 dark:bg-zinc-700 flex items-center justify-center shrink-0 border-2 border-zinc-200 dark:border-zinc-600">
                            <template x-if="preview">
                                <img :src="preview" alt="Preview" class="w-full h-full object-cover" />
                            </template>
                            <template x-if="!preview">
                                <flux:icon.user class="w-8 h-8 text-zinc-400" />
                            </template>
                        </div>
                        <div class="flex-1">
                            <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp"
                                   class="block w-full text-sm text-zinc-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-zinc-100 file:text-zinc-700 hover:file:bg-zinc-200 dark:file:bg-zinc-700 dark:file:text-zinc-300 dark:hover:file:bg-zinc-600"
                                   @change="preview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null" />
                            <p class="mt-1 text-xs text-zinc-400">{{ __('JPG, PNG or WebP. Max 2MB.') }}</p>
                            @error('avatar') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <flux:input name="name" :label="__('Full Name')" :value="old('name')" required />
                    <flux:input name="username" :label="__('Username')" :value="old('username')" required placeholder="e.g. john.doe" />
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <x-password-input name="password" :label="__('Password')" required />
                    <flux:select name="gender" :label="__('Gender')" required>
                        <option value="">{{ __('Select...') }}</option>
                        <option value="male" @selected(old('gender') === 'male')>{{ __('Male') }}</option>
                        <option value="female" @selected(old('gender') === 'female')>{{ __('Female') }}</option>
                        <option value="other" @selected(old('gender') === 'other')>{{ __('Other') }}</option>
                    </flux:select>
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <flux:select name="level_id" :label="__('School Level')" required>
                        <option value="">{{ __('Select level...') }}</option>
                        @foreach ($levels as $level)
                            <option value="{{ $level->id }}" @selected(old('level_id') == $level->id)>{{ $level->name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:select name="class_id" :label="__('Class')" required>
                        <option value="">{{ __('Select class...') }}</option>
                        @foreach ($classes as $class)
                            <option value="{{ $class->id }}" @selected(old('class_id') == $class->id)>{{ $class->name }} ({{ $class->level?->name }})</option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:separator />

                <flux:heading size="sm">{{ __('Optional Details') }}</flux:heading>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <flux:input name="admission_number" :label="__('Admission Number')" :value="old('admission_number')" />
                    <flux:input name="date_of_birth" :label="__('Date of Birth')" :value="old('date_of_birth')" type="date" />
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <flux:input name="blood_group" :label="__('Blood Group')" :value="old('blood_group')" placeholder="e.g. A+" />
                    <div></div>
                </div>

                <flux:textarea name="address" :label="__('Address')" :value="old('address')" rows="2" />
                <flux:textarea name="medical_notes" :label="__('Medical Notes')" :value="old('medical_notes')" rows="2" />

                <div class="flex gap-3">
                    <flux:button variant="primary" type="submit">{{ __('Add Student') }}</flux:button>
                    <flux:button variant="ghost" href="{{ route('admin.students.index') }}" wire:navigate>{{ __('Cancel') }}</flux:button>
                </div>
            </form>
        </div>
    </div>
</x-layouts::app>
