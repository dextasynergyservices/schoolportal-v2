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

                {{-- Student Linking with Level/Class Filtering --}}
                <fieldset
                    x-data="{
                        levels: {{ Js::from($levels) }},
                        classes: {{ Js::from($classes) }},
                        allStudents: {{ Js::from($studentData) }},
                        selectedLevelIds: [],
                        selectedClassIds: [],
                        selectedStudentIds: {{ Js::from(old('student_ids', array_map('strval', $linkedStudentIds))) }},
                        search: '',

                        get filteredClasses() {
                            if (this.selectedLevelIds.length === 0) return this.classes;
                            return this.classes.filter(c => this.selectedLevelIds.includes(String(c.level_id)));
                        },

                        get filteredStudents() {
                            let students = this.allStudents;

                            if (this.selectedClassIds.length > 0) {
                                students = students.filter(s => this.selectedClassIds.includes(String(s.class_id)));
                            } else if (this.selectedLevelIds.length > 0) {
                                const classIdsForLevels = this.classes
                                    .filter(c => this.selectedLevelIds.includes(String(c.level_id)))
                                    .map(c => c.id);
                                students = students.filter(s => classIdsForLevels.includes(s.class_id));
                            }

                            if (this.search.trim()) {
                                const q = this.search.toLowerCase().trim();
                                students = students.filter(s =>
                                    s.name.toLowerCase().includes(q) ||
                                    s.username.toLowerCase().includes(q) ||
                                    (s.admission_number && s.admission_number.toLowerCase().includes(q))
                                );
                            }

                            return students;
                        },

                        toggleStudent(id) {
                            const sid = String(id);
                            const idx = this.selectedStudentIds.indexOf(sid);
                            if (idx >= 0) {
                                this.selectedStudentIds.splice(idx, 1);
                            } else {
                                this.selectedStudentIds.push(sid);
                            }
                        },

                        isSelected(id) {
                            return this.selectedStudentIds.includes(String(id));
                        },

                        getClassName(classId) {
                            const cls = this.classes.find(c => c.id === classId);
                            return cls ? cls.name : '';
                        },

                        onLevelChange() {
                            const validClassIds = this.filteredClasses.map(c => String(c.id));
                            this.selectedClassIds = this.selectedClassIds.filter(id => validClassIds.includes(id));
                        },

                        selectAllVisible() {
                            this.filteredStudents.forEach(s => {
                                const sid = String(s.id);
                                if (!this.selectedStudentIds.includes(sid)) {
                                    this.selectedStudentIds.push(sid);
                                }
                            });
                        },

                        deselectAllVisible() {
                            const visibleIds = this.filteredStudents.map(s => String(s.id));
                            this.selectedStudentIds = this.selectedStudentIds.filter(id => !visibleIds.includes(id));
                        }
                    }"
                >
                    <legend class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-3">{{ __('Linked Students') }} <span class="text-red-500">*</span></legend>

                    <div class="space-y-3 mb-3">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">{{ __('Filter by Level') }}</label>
                                <select multiple x-model="selectedLevelIds" @change="onLevelChange()"
                                        class="w-full rounded-lg border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-sm text-zinc-700 dark:text-zinc-300 px-3 py-2">
                                    <template x-for="level in levels" :key="level.id">
                                        <option :value="level.id" x-text="level.name"></option>
                                    </template>
                                </select>
                                <p class="text-xs text-zinc-400 mt-0.5">{{ __('Hold Ctrl/Cmd to select multiple') }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">{{ __('Filter by Class') }}</label>
                                <select multiple x-model="selectedClassIds"
                                        class="w-full rounded-lg border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-sm text-zinc-700 dark:text-zinc-300 px-3 py-2">
                                    <template x-for="cls in filteredClasses" :key="cls.id">
                                        <option :value="cls.id" x-text="cls.name"></option>
                                    </template>
                                </select>
                                <p class="text-xs text-zinc-400 mt-0.5">{{ __('Hold Ctrl/Cmd to select multiple') }}</p>
                            </div>
                        </div>

                        <div>
                            <input type="text" x-model.debounce.200ms="search"
                                   placeholder="{{ __('Search by name, username, or admission number...') }}"
                                   class="w-full rounded-lg border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-sm text-zinc-700 dark:text-zinc-300 px-3 py-2 placeholder-zinc-400" />
                        </div>

                        <div class="flex items-center justify-between text-xs text-zinc-500">
                            <span>
                                {{ __('Showing') }} <strong x-text="filteredStudents.length"></strong> {{ __('students') }}
                                &middot;
                                <strong x-text="selectedStudentIds.length"></strong> {{ __('selected') }}
                            </span>
                            <span class="flex gap-2">
                                <button type="button" @click="selectAllVisible()" class="text-blue-600 hover:underline">{{ __('Select all visible') }}</button>
                                <button type="button" @click="deselectAllVisible()" class="text-zinc-500 hover:underline">{{ __('Deselect visible') }}</button>
                            </span>
                        </div>
                    </div>

                    <div class="max-h-64 overflow-y-auto border border-zinc-200 dark:border-zinc-700 rounded-lg">
                        <template x-if="filteredStudents.length === 0">
                            <p class="p-4 text-sm text-zinc-500 text-center">{{ __('No students match the current filters.') }}</p>
                        </template>
                        <template x-for="student in filteredStudents" :key="student.id">
                            <label class="flex items-center gap-3 px-3 py-2 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 cursor-pointer border-b border-zinc-100 dark:border-zinc-700 last:border-0"
                                   :class="isSelected(student.id) ? 'bg-blue-50 dark:bg-blue-900/20' : ''">
                                <input type="checkbox"
                                       :checked="isSelected(student.id)"
                                       @change="toggleStudent(student.id)"
                                       class="rounded border-zinc-300 dark:border-zinc-600 text-blue-600 focus:ring-blue-500" />
                                <div class="flex-1 min-w-0">
                                    <span class="text-sm font-medium text-zinc-800 dark:text-zinc-200" x-text="student.name"></span>
                                    <span class="text-xs text-zinc-400 ml-1" x-text="'@' + student.username"></span>
                                    <span class="text-xs text-zinc-400 ml-1" x-show="student.admission_number" x-text="'#' + student.admission_number"></span>
                                </div>
                                <span class="text-xs text-zinc-400 shrink-0" x-text="getClassName(student.class_id)"></span>
                            </label>
                        </template>
                    </div>

                    <template x-for="id in selectedStudentIds" :key="'input-' + id">
                        <input type="hidden" name="student_ids[]" :value="id" />
                    </template>

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
