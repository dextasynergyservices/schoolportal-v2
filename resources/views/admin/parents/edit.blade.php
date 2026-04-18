<x-layouts::app :title="__('Edit Parent')">
    <div class="space-y-6">
        <x-admin-header :title="__('Edit Parent: :name', ['name' => $parent->name])" />

        <div class="max-w-2xl rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <form method="POST" action="{{ route('admin.parents.update', $parent) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <flux:input name="name" :label="__('Full Name')" :value="old('name', $parent->name)" required />
                    <flux:input name="username" :label="__('Username')" :value="old('username', $parent->username)" required />
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <x-password-input name="password" :label="__('New Password (leave blank to keep)')" />
                    <flux:input name="phone" :label="__('Phone')" :value="old('phone', $parent->phone)" />
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <flux:select name="gender" :label="__('Gender')">
                        <option value="">{{ __('Select...') }}</option>
                        <option value="male" @selected(old('gender', $parent->gender) === 'male')>{{ __('Male') }}</option>
                        <option value="female" @selected(old('gender', $parent->gender) === 'female')>{{ __('Female') }}</option>
                    </flux:select>
                    <flux:select name="relationship" :label="__('Relationship')">
                        <option value="">{{ __('Select...') }}</option>
                        <option value="father" @selected(old('relationship', $parent->parentProfile?->relationship) === 'father')>{{ __('Father') }}</option>
                        <option value="mother" @selected(old('relationship', $parent->parentProfile?->relationship) === 'mother')>{{ __('Mother') }}</option>
                        <option value="guardian" @selected(old('relationship', $parent->parentProfile?->relationship) === 'guardian')>{{ __('Guardian') }}</option>
                        <option value="other" @selected(old('relationship', $parent->parentProfile?->relationship) === 'other')>{{ __('Other') }}</option>
                    </flux:select>
                </div>

                <flux:input name="occupation" :label="__('Occupation')" :value="old('occupation', $parent->parentProfile?->occupation)" />

                <flux:textarea name="address" :label="__('Address')" rows="2">{{ old('address', $parent->parentProfile?->address) }}</flux:textarea>

                <flux:switch name="is_active" :label="__('Active')" :checked="old('is_active', $parent->is_active)" value="1" />

                <fieldset>
                    <legend class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">{{ __('Linked Students') }} <span class="text-red-500">*</span></legend>
                    @if ($students->count())
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 max-h-60 overflow-y-auto border border-zinc-200 dark:border-zinc-700 rounded-lg p-3">
                            @foreach ($students as $student)
                                <flux:checkbox
                                    name="student_ids[]"
                                    :value="$student->id"
                                    :label="$student->name . ($student->studentProfile?->class ? ' (' . $student->studentProfile->class->name . ')' : '')"
                                    :checked="in_array($student->id, old('student_ids', $linkedStudentIds))"
                                />
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-zinc-500">{{ __('No active students found.') }}</p>
                    @endif
                    @error('student_ids')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </fieldset>

                <div class="flex gap-3">
                    <flux:button variant="primary" type="submit">{{ __('Update Parent') }}</flux:button>
                    <flux:button variant="ghost" href="{{ route('admin.parents.index') }}" wire:navigate>{{ __('Cancel') }}</flux:button>
                </div>
            </form>
        </div>
    </div>
</x-layouts::app>
