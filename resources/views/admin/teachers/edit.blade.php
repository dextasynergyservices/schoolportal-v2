<x-layouts::app :title="__('Edit Teacher')">
    <div class="space-y-6">
        <x-admin-header :title="__('Edit Teacher: :name', ['name' => $teacher->name])" />

        <div class="max-w-2xl rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <form method="POST" action="{{ route('admin.teachers.update', $teacher) }}" class="space-y-6"
                  x-data="{
                      levelId: '{{ old('level_id', $teacher->level_id ?? '') }}',
                      allClasses: {{ Js::from($classes->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'level_id' => $c->level_id, 'level_name' => $c->level?->name])) }},
                      selectedClassIds: {{ Js::from(old('class_ids', $assignedClassIds)) }},
                      get filteredClasses() {
                          if (!this.levelId) return this.allClasses;
                          return this.allClasses.filter(c => c.level_id == this.levelId);
                      },
                      toggleClass(id) {
                          const idx = this.selectedClassIds.indexOf(id);
                          if (idx > -1) { this.selectedClassIds.splice(idx, 1); }
                          else { this.selectedClassIds.push(id); }
                      },
                      isSelected(id) { return this.selectedClassIds.includes(id); }
                  }">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <flux:input name="name" :label="__('Full Name')" :value="old('name', $teacher->name)" required />
                    <flux:input name="username" :label="__('Username')" :value="old('username', $teacher->username)" required />
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <flux:input name="email" :label="__('Email (optional)')" :value="old('email', $teacher->email)" type="email" />
                    <x-password-input name="password" :label="__('New Password (leave blank to keep)')" />
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <flux:input name="phone" :label="__('Phone')" :value="old('phone', $teacher->phone)" />
                    <flux:select name="gender" :label="__('Gender')">
                        <option value="">{{ __('Select...') }}</option>
                        <option value="male" @selected(old('gender', $teacher->gender) === 'male')>{{ __('Male') }}</option>
                        <option value="female" @selected(old('gender', $teacher->gender) === 'female')>{{ __('Female') }}</option>
                    </flux:select>
                </div>

                <flux:select name="level_id" :label="__('School Level')" x-model="levelId">
                    <option value="">{{ __('All levels') }}</option>
                    @foreach ($levels as $level)
                        <option value="{{ $level->id }}">{{ $level->name }}</option>
                    @endforeach
                </flux:select>

                <flux:switch name="is_active" :label="__('Active')" :checked="old('is_active', $teacher->is_active)" value="1" />

                <fieldset x-show="filteredClasses.length > 0">
                    <legend class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">{{ __('Assigned Classes') }}</legend>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                        <template x-for="cls in filteredClasses" :key="cls.id">
                            <label class="flex items-center gap-2 text-sm cursor-pointer">
                                <input type="checkbox" name="class_ids[]" :value="cls.id"
                                       :checked="isSelected(cls.id)" @change="toggleClass(cls.id)"
                                       class="rounded border-zinc-300 text-[var(--color-primary)] focus:ring-[var(--color-primary)] dark:border-zinc-600 dark:bg-zinc-700" />
                                <span x-text="cls.name" class="text-zinc-700 dark:text-zinc-300"></span>
                                <span x-show="!levelId" x-text="'(' + (cls.level_name || '') + ')'" class="text-xs text-zinc-400"></span>
                            </label>
                        </template>
                    </div>
                    <p x-show="filteredClasses.length === 0 && levelId" class="text-sm text-zinc-500 mt-2">
                        {{ __('No available classes in this level.') }}
                    </p>
                </fieldset>

                <div class="flex gap-3">
                    <flux:button variant="primary" type="submit">{{ __('Update Teacher') }}</flux:button>
                    <flux:button variant="ghost" href="{{ route('admin.teachers.index') }}" wire:navigate>{{ __('Cancel') }}</flux:button>
                </div>
            </form>
        </div>
    </div>
</x-layouts::app>
