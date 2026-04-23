<x-layouts::app :title="__('My Profile')">
    <div class="space-y-6">
        <x-admin-header
            :title="__('My Profile')"
            :description="__('Your personal and academic information')"
        />

        <div class="grid gap-6 lg:grid-cols-3">
            {{-- Personal Information --}}
            <div class="lg:col-span-2 space-y-6">
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800">
                    <div class="border-b border-zinc-200 dark:border-zinc-700 px-5 py-3">
                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Personal Information') }}</h3>
                    </div>
                    <div class="p-5">
                        <div class="flex items-start gap-4 mb-6">
                            <div class="flex items-center justify-center w-16 h-16 rounded-full bg-indigo-100 dark:bg-indigo-900/30 shrink-0">
                                @if ($student->avatar_url)
                                    <img src="{{ $student->avatar_url }}" alt="{{ $student->name }}" class="w-16 h-16 rounded-full object-cover">
                                @else
                                    <span class="text-xl font-bold text-indigo-600 dark:text-indigo-400">{{ Str::of($student->name)->explode(' ')->map(fn($n) => Str::substr($n, 0, 1))->take(2)->implode('') }}</span>
                                @endif
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $student->name }}</h2>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ '@' . $student->username }}</p>
                            </div>
                        </div>

                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                            <div>
                                <dt class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">{{ __('Username') }}</dt>
                                <dd class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $student->username }}</dd>
                            </div>

                            <div>
                                <dt class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">{{ __('Gender') }}</dt>
                                <dd class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $student->gender ? ucfirst($student->gender) : '—' }}</dd>
                            </div>

                            @if ($profile?->admission_number)
                                <div>
                                    <dt class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">{{ __('Admission Number') }}</dt>
                                    <dd class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $profile->admission_number }}</dd>
                                </div>
                            @endif

                            @if ($profile?->date_of_birth)
                                <div>
                                    <dt class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">{{ __('Date of Birth') }}</dt>
                                    <dd class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $profile->date_of_birth->format('j F Y') }}</dd>
                                </div>
                            @endif

                            @if ($profile?->blood_group)
                                <div>
                                    <dt class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">{{ __('Blood Group') }}</dt>
                                    <dd class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $profile->blood_group }}</dd>
                                </div>
                            @endif

                            @if ($profile?->address)
                                <div class="sm:col-span-2">
                                    <dt class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">{{ __('Address') }}</dt>
                                    <dd class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $profile->address }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                </div>

                {{-- Academic Information --}}
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800">
                    <div class="border-b border-zinc-200 dark:border-zinc-700 px-5 py-3">
                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Academic Information') }}</h3>
                    </div>
                    <div class="p-5">
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                            <div>
                                <dt class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">{{ __('Class') }}</dt>
                                <dd class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $class?->name ?? '—' }}</dd>
                            </div>

                            <div>
                                <dt class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">{{ __('Level') }}</dt>
                                <dd class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $class?->level?->name ?? '—' }}</dd>
                            </div>

                            <div>
                                <dt class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">{{ __('Class Teacher') }}</dt>
                                <dd class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $class?->teacher?->name ?? '—' }}</dd>
                            </div>

                            @if ($enrolledSession)
                                <div>
                                    <dt class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">{{ __('Enrolled Session') }}</dt>
                                    <dd class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $enrolledSession->name }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                </div>
            </div>

            {{-- Sidebar: Quick Stats + Actions --}}
            <div class="space-y-6">
                {{-- Quick Stats --}}
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800">
                    <div class="border-b border-zinc-200 dark:border-zinc-700 px-5 py-3">
                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Quick Stats') }}</h3>
                    </div>
                    <div class="p-5 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Results') }}</span>
                            <span class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $resultsCount }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Quizzes Taken') }}</span>
                            <span class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $quizzesTaken }}</span>
                        </div>
                    </div>
                </div>

                {{-- Account Actions --}}
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800">
                    <div class="border-b border-zinc-200 dark:border-zinc-700 px-5 py-3">
                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Account Settings') }}</h3>
                    </div>
                    <div class="p-5 space-y-2">
                        <flux:button variant="subtle" class="w-full justify-start" href="{{ route('security.edit') }}" wire:navigate icon="key">
                            {{ __('Change Password') }}
                        </flux:button>
                        <flux:button variant="subtle" class="w-full justify-start" href="{{ route('appearance.edit') }}" wire:navigate icon="swatch">
                            {{ __('Appearance') }}
                        </flux:button>
                    </div>
                </div>

                {{-- Info Note --}}
                <div class="rounded-lg border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-4">
                    <div class="flex gap-3">
                        <flux:icon.information-circle class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" />
                        <p class="text-xs text-amber-700 dark:text-amber-300">
                            {{ __('To update your personal details (name, class, etc.), please contact your school administrator.') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts::app>
