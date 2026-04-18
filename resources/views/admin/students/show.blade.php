<x-layouts::app :title="$student->name">
    <div class="space-y-6">
        <x-admin-header :title="$student->name">
            <div class="flex gap-2">
                @if ($student->is_active)
                    <flux:modal.trigger name="deactivate-student">
                        <flux:button variant="danger" size="sm" icon="pause-circle">{{ __('Deactivate') }}</flux:button>
                    </flux:modal.trigger>
                @else
                    <form method="POST" action="{{ route('admin.students.activate', $student) }}">
                        @csrf
                        <flux:button type="submit" variant="filled" size="sm" icon="play-circle">{{ __('Activate') }}</flux:button>
                    </form>
                @endif
                <flux:button variant="filled" size="sm" icon="pencil-square" href="{{ route('admin.students.edit', $student) }}" wire:navigate>
                    {{ __('Edit') }}
                </flux:button>
            </div>
        </x-admin-header>

        @if (! $student->is_active && $student->deactivation_reason)
            <flux:callout variant="danger" icon="exclamation-triangle">
                <flux:callout.heading>{{ __('Account Deactivated') }}</flux:callout.heading>
                <flux:callout.text>{{ $student->deactivation_reason }}</flux:callout.text>
                @if ($student->deactivated_at)
                    <flux:callout.text class="text-xs mt-1">{{ __('Deactivated on :date', ['date' => $student->deactivated_at->format('M j, Y g:i A')]) }}</flux:callout.text>
                @endif
            </flux:callout>
        @endif

        {{-- Deactivate modal --}}
        @if ($student->is_active)
            <flux:modal name="deactivate-student" class="max-w-md">
                <form method="POST" action="{{ route('admin.students.deactivate', $student) }}" class="space-y-4">
                    @csrf
                    <div>
                        <flux:heading size="lg">{{ __('Deactivate :name', ['name' => $student->name]) }}</flux:heading>
                        <flux:text class="mt-1">{{ __('This student will not be able to log in until you reactivate their account.') }}</flux:text>
                    </div>
                    <flux:textarea
                        name="deactivation_reason"
                        :label="__('Reason for deactivation')"
                        :placeholder="__('e.g. Student transferred to another school...')"
                        required
                        rows="3"
                    />
                    <div class="flex justify-end gap-2">
                        <flux:modal.close>
                            <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                        </flux:modal.close>
                        <flux:button type="submit" variant="danger">{{ __('Deactivate') }}</flux:button>
                    </div>
                </form>
            </flux:modal>
        @endif

        <div class="grid gap-6 lg:grid-cols-2">
            {{-- Profile Card --}}
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                <div class="flex items-center gap-4 mb-6">
                    <flux:avatar size="lg" :src="$student->avatar_url" :name="$student->name" />
                    <div>
                        <flux:heading size="lg">{{ $student->name }}</flux:heading>
                        <flux:text class="text-zinc-500">{{ '@' . $student->username }}</flux:text>
                        <div class="mt-1">
                            @if ($student->is_active)
                                <flux:badge color="green" size="sm">{{ __('Active') }}</flux:badge>
                            @else
                                <flux:badge color="red" size="sm">{{ __('Inactive') }}</flux:badge>
                            @endif
                        </div>
                    </div>
                </div>

                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-zinc-500">{{ __('Gender') }}</dt>
                        <dd class="font-medium">{{ $student->gender ? ucfirst($student->gender) : '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-zinc-500">{{ __('Level') }}</dt>
                        <dd class="font-medium">{{ $student->level?->name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-zinc-500">{{ __('Class') }}</dt>
                        <dd class="font-medium">{{ $student->studentProfile?->class?->name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-zinc-500">{{ __('Class Teacher') }}</dt>
                        <dd class="font-medium">{{ $student->studentProfile?->class?->teacher?->name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-zinc-500">{{ __('Admission No.') }}</dt>
                        <dd class="font-medium">{{ $student->studentProfile?->admission_number ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-zinc-500">{{ __('Date of Birth') }}</dt>
                        <dd class="font-medium">{{ $student->studentProfile?->date_of_birth?->format('M j, Y') ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-zinc-500">{{ __('Blood Group') }}</dt>
                        <dd class="font-medium">{{ $student->studentProfile?->blood_group ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-zinc-500">{{ __('Last Login') }}</dt>
                        <dd class="font-medium">{{ $student->last_login_at?->diffForHumans() ?? __('Never') }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Additional Info --}}
            <div class="space-y-6">
                @if ($student->studentProfile?->address)
                    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                        <flux:heading size="sm" class="mb-2">{{ __('Address') }}</flux:heading>
                        <flux:text>{{ $student->studentProfile->address }}</flux:text>
                    </div>
                @endif

                @if ($student->studentProfile?->medical_notes)
                    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                        <flux:heading size="sm" class="mb-2">{{ __('Medical Notes') }}</flux:heading>
                        <flux:text>{{ $student->studentProfile->medical_notes }}</flux:text>
                    </div>
                @endif

                {{-- Linked Parents --}}
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                    <flux:heading size="sm" class="mb-3">{{ __('Linked Parents') }}</flux:heading>
                    @php
                        $parentLinks = \App\Models\ParentStudent::where('student_id', $student->id)->with('parent:id,name,phone')->get();
                    @endphp
                    @forelse ($parentLinks as $link)
                        <div class="flex items-center justify-between py-2">
                            <div>
                                <flux:text class="font-medium">{{ $link->parent->name }}</flux:text>
                                @if ($link->parent->phone)
                                    <flux:text class="text-xs text-zinc-500">{{ $link->parent->phone }}</flux:text>
                                @endif
                            </div>
                        </div>
                    @empty
                        <flux:text class="text-zinc-500">{{ __('No parents linked yet.') }}</flux:text>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-layouts::app>
