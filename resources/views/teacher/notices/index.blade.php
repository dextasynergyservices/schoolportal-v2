<x-layouts::app :title="__('My Notices')">
    <div class="space-y-6">
        <x-admin-header
            :title="__('My Notices')"
            :action="route('teacher.notices.create')"
            :actionLabel="__('Post Notice')"
            actionIcon="megaphone"
        />

        @if (session('success'))
            <flux:callout variant="success" icon="check-circle">{{ session('success') }}</flux:callout>
        @endif

        @if (session('error'))
            <flux:callout variant="danger" icon="exclamation-triangle">{{ session('error') }}</flux:callout>
        @endif

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Title') }}</flux:table.column>
                <flux:table.column class="hidden sm:table-cell">{{ __('Published') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column class="hidden md:table-cell">{{ __('Expires') }}</flux:table.column>
                <flux:table.column class="hidden sm:table-cell">{{ __('Created') }}</flux:table.column>
                <flux:table.column class="w-28">{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($notices as $notice)
                    <flux:table.row>
                        <flux:table.cell class="font-medium">
                            <div>
                                <span class="flex items-center gap-1.5">
                                    {{ Str::limit($notice->title, 50) }}
                                    @if ($notice->file_url)
                                        <flux:icon.paper-clip class="w-4 h-4 text-zinc-400 shrink-0" />
                                    @endif
                                </span>
                                @if ($notice->status === 'rejected' && isset($rejectionReasons[$notice->id]))
                                    <p class="text-xs text-red-600 mt-1">
                                        <span class="font-medium">{{ __('Reason:') }}</span> {{ $rejectionReasons[$notice->id] }}
                                    </p>
                                @endif
                            </div>
                        </flux:table.cell>
                        <flux:table.cell class="hidden sm:table-cell">
                            @if ($notice->is_published)
                                <flux:badge color="green" size="sm">{{ __('Yes') }}</flux:badge>
                            @else
                                <flux:badge color="zinc" size="sm">{{ __('No') }}</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            @if ($notice->status === 'approved')
                                <flux:badge color="green" size="sm">{{ __('Approved') }}</flux:badge>
                            @elseif ($notice->status === 'pending')
                                <flux:badge color="yellow" size="sm">{{ __('Pending') }}</flux:badge>
                            @else
                                <flux:badge color="red" size="sm">{{ __('Rejected') }}</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="hidden md:table-cell text-zinc-500">
                            {{ $notice->expires_at?->format('M j, Y') ?? '—' }}
                        </flux:table.cell>
                        <flux:table.cell class="hidden sm:table-cell text-zinc-500">{{ $notice->created_at->format('M j, Y') }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-1">
                                <flux:button variant="subtle" size="xs" icon="pencil-square" href="{{ route('teacher.notices.edit', $notice) }}" wire:navigate :aria-label="__('Edit notice')" />

                                @if ($notice->is_published)
                                    <form method="POST" action="{{ route('teacher.notices.unpublish', $notice) }}">
                                        @csrf
                                        <flux:button type="submit" variant="subtle" size="xs" icon="eye-slash" :aria-label="__('Unpublish notice')" />
                                    </form>
                                @elseif ($notice->status === 'approved')
                                    <form method="POST" action="{{ route('teacher.notices.publish', $notice) }}">
                                        @csrf
                                        <flux:button type="submit" variant="subtle" size="xs" icon="eye" :aria-label="__('Publish notice')" />
                                    </form>
                                @endif

                                <x-confirm-delete
                                    :action="route('teacher.notices.destroy', $notice)"
                                    :title="__('Delete Notice')"
                                    :message="__('Are you sure you want to delete this notice? This action cannot be undone.')"
                                    :ariaLabel="__('Delete notice')"
                                />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="text-center py-8">
                            <flux:icon.megaphone class="w-8 h-8 mx-auto text-zinc-300 dark:text-zinc-600" />
                            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ __('No notices posted yet. Share an announcement with your students.') }}</p>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        {{ $notices->links() }}
    </div>
</x-layouts::app>
