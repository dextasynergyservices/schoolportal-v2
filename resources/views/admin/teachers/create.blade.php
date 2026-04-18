<x-layouts::app :title="__('Add Teacher')">
    <div class="space-y-6">
        <x-admin-header :title="__('Add Teacher')" />

        <div class="max-w-2xl rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <form method="POST" action="{{ route('admin.teachers.store') }}" class="space-y-6">
                @csrf

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <flux:input name="name" :label="__('Full Name')" :value="old('name')" required />
                    <flux:input name="username" :label="__('Username')" :value="old('username')" required />
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <flux:input name="email" :label="__('Email (optional)')" :value="old('email')" type="email" />
                    <x-password-input name="password" :label="__('Password')" required />
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <flux:input name="phone" :label="__('Phone (optional)')" :value="old('phone')" />
                    <flux:select name="gender" :label="__('Gender')">
                        <option value="">{{ __('Select...') }}</option>
                        <option value="male" @selected(old('gender') === 'male')>{{ __('Male') }}</option>
                        <option value="female" @selected(old('gender') === 'female')>{{ __('Female') }}</option>
                    </flux:select>
                </div>

                <flux:select name="level_id" :label="__('School Level (optional)')">
                    <option value="">{{ __('All levels') }}</option>
                    @foreach ($levels as $level)
                        <option value="{{ $level->id }}" @selected(old('level_id') == $level->id)>{{ $level->name }}</option>
                    @endforeach
                </flux:select>

                @if ($classes->count())
                    <fieldset>
                        <legend class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">{{ __('Assign to Classes (optional)') }}</legend>
                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                            @foreach ($classes as $class)
                                <flux:checkbox name="class_ids[]" :value="$class->id" :label="$class->name" :checked="in_array($class->id, old('class_ids', []))" />
                            @endforeach
                        </div>
                    </fieldset>
                @endif

                <div class="flex gap-3">
                    <flux:button variant="primary" type="submit">{{ __('Add Teacher') }}</flux:button>
                    <flux:button variant="ghost" href="{{ route('admin.teachers.index') }}" wire:navigate>{{ __('Cancel') }}</flux:button>
                </div>
            </form>
        </div>
    </div>
</x-layouts::app>
