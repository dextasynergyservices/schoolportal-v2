<x-layouts::app :title="$announcement->title">
    <div class="space-y-6">
        <x-admin-header :title="$announcement->title" />

        @if (session('success'))
            <flux:callout variant="success" icon="check-circle">{{ session('success') }}</flux:callout>
        @endif

        {{-- Announcement details --}}
        <div class="max-w-3xl rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <div class="flex flex-wrap items-center gap-2 mb-4">
                @if ($announcement->priority === 'critical')
                    <flux:badge color="red" size="sm">{{ __('Critical') }}</flux:badge>
                @elseif ($announcement->priority === 'warning')
                    <flux:badge color="yellow" size="sm">{{ __('Warning') }}</flux:badge>
                @else
                    <flux:badge color="sky" size="sm">{{ __('Info') }}</flux:badge>
                @endif

                @if ($announcement->is_active)
                    <flux:badge color="green" size="sm">{{ __('Active') }}</flux:badge>
                @else
                    <flux:badge color="zinc" size="sm">{{ __('Inactive') }}</flux:badge>
                @endif

                <span class="text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('Created :date by :name', ['date' => $announcement->created_at->format('M j, Y g:i A'), 'name' => $announcement->creator?->name ?? '—']) }}
                </span>
            </div>

            <div class="prose dark:prose-invert max-w-none text-sm">
                {!! nl2br(e($announcement->content)) !!}
            </div>

            @if ($announcement->starts_at || $announcement->expires_at)
                <div class="mt-4 flex flex-wrap gap-4 text-sm text-zinc-500 dark:text-zinc-400">
                    @if ($announcement->starts_at)
                        <span>{{ __('Starts:') }} {{ $announcement->starts_at->format('M j, Y') }}</span>
                    @endif
                    @if ($announcement->expires_at)
                        <span>{{ __('Expires:') }} {{ $announcement->expires_at->format('M j, Y') }}</span>
                    @endif
                </div>
            @endif

            <div class="mt-6 flex flex-wrap gap-2">
                <flux:button variant="filled" size="sm" icon="pencil-square" :href="route('super-admin.announcements.edit', $announcement)" wire:navigate>
                    {{ __('Edit') }}
                </flux:button>
                @if ($announcement->is_active)
                    <form method="POST" action="{{ route('super-admin.announcements.deactivate', $announcement) }}" class="inline">
                        @csrf
                        <flux:button variant="subtle" size="sm" icon="pause" type="submit">{{ __('Deactivate') }}</flux:button>
                    </form>
                @else
                    <form method="POST" action="{{ route('super-admin.announcements.activate', $announcement) }}" class="inline">
                        @csrf
                        <flux:button variant="subtle" size="sm" icon="play" type="submit">{{ __('Activate') }}</flux:button>
                    </form>
                @endif
                <x-confirm-delete
                    :action="route('super-admin.announcements.destroy', $announcement)"
                    :title="__('Delete Announcement')"
                    :message="__('Are you sure? This will permanently remove this announcement from all schools.')"
                    :confirmLabel="__('Delete')"
                    buttonVariant="danger"
                    buttonSize="sm"
                    :buttonLabel="__('Delete')"
                    :ariaLabel="__('Delete announcement')"
                />
            </div>
        </div>

        {{-- Read status --}}
        @php
            $totalSchools = $readSchools->count() + $unreadSchools->count();
            $readPercent = $totalSchools > 0 ? round(($readSchools->count() / $totalSchools) * 100) : 0;
        @endphp

        <div class="max-w-3xl rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Read Status') }}</h3>
                <span class="text-sm font-medium text-zinc-600 dark:text-zinc-300">
                    {{ $readSchools->count() }} / {{ $totalSchools }} {{ __('schools') }}
                    <span class="text-zinc-400 dark:text-zinc-500">({{ $readPercent }}%)</span>
                </span>
            </div>
            <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-2.5 mb-6 overflow-hidden">
                <div class="h-full rounded-full transition-all duration-500 ease-out {{ $readPercent === 100 ? 'bg-green-500' : 'bg-indigo-500' }}"
                     style="width: {{ $readPercent }}%"></div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                {{-- Schools that have read --}}
                <div>
                    <h4 class="text-xs font-semibold uppercase tracking-wider text-green-600 dark:text-green-400 mb-3 flex items-center gap-1.5">
                        <flux:icon name="check-circle" class="size-4" />
                        {{ __('Read (:count)', ['count' => $readSchools->count()]) }}
                    </h4>
                    @if ($readSchools->isNotEmpty())
                        <ul class="space-y-2">
                            @foreach ($readSchools as $school)
                                @php
                                    $readRecord = $announcement->reads->firstWhere('school_id', $school->id);
                                @endphp
                                <li class="flex items-center justify-between rounded-lg bg-green-50 dark:bg-green-900/20 px-3 py-2">
                                    <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $school->name }}</span>
                                    <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $readRecord?->read_at?->diffForHumans() ?? '—' }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-sm text-zinc-500 italic">{{ __('No schools have read this yet.') }}</p>
                    @endif
                </div>

                {{-- Schools that haven't read --}}
                <div>
                    <h4 class="text-xs font-semibold uppercase tracking-wider text-amber-600 dark:text-amber-400 mb-3 flex items-center gap-1.5">
                        <flux:icon name="clock" class="size-4" />
                        {{ __('Unread (:count)', ['count' => $unreadSchools->count()]) }}
                    </h4>
                    @if ($unreadSchools->isNotEmpty())
                        <ul class="space-y-2">
                            @foreach ($unreadSchools as $school)
                                <li class="rounded-lg bg-amber-50 dark:bg-amber-900/20 px-3 py-2">
                                    <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $school->name }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="flex flex-col items-center py-6 text-center">
                            <flux:icon name="check-badge" class="size-8 text-green-500 mb-2" />
                            <p class="text-sm font-medium text-green-600 dark:text-green-400">{{ __('All schools have read this!') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-layouts::app>
