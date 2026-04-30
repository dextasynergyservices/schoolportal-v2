<x-layouts::app :title="__('All Quizzes')">
    <div class="space-y-6">
        <x-admin-header :title="__('All Quizzes')" />

        @if (session('success'))
            <flux:callout variant="success" icon="check-circle">{{ session('success') }}</flux:callout>
        @endif
        @if (session('error'))
            <flux:callout variant="danger" icon="x-circle">{{ session('error') }}</flux:callout>
        @endif

        <form method="GET" action="{{ route('admin.quizzes.index') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <flux:select name="class_id" aria-label="{{ __('Filter by class') }}">
                    <option value="">{{ __('All Classes') }}</option>
                    @foreach ($classes as $class)
                        <option value="{{ $class->id }}" @selected(request('class_id') == $class->id)>{{ $class->name }}</option>
                    @endforeach
                </flux:select>
            </div>
            <div>
                <flux:select name="status" aria-label="{{ __('Filter by status') }}">
                    <option value="">{{ __('All Statuses') }}</option>
                    <option value="pending" @selected(request('status') === 'pending')>{{ __('Pending') }}</option>
                    <option value="approved" @selected(request('status') === 'approved')>{{ __('Approved') }}</option>
                    <option value="rejected" @selected(request('status') === 'rejected')>{{ __('Rejected') }}</option>
                </flux:select>
            </div>
            <flux:button type="submit" variant="filled" size="sm">{{ __('Filter') }}</flux:button>
            @if (request()->hasAny(['class_id', 'status']))
                <flux:button variant="subtle" size="sm" href="{{ route('admin.quizzes.index') }}" wire:navigate>{{ __('Clear') }}</flux:button>
            @endif
        </form>

        <div class="grid gap-4">
            @forelse ($quizzes as $quiz)
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 sm:p-5">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <h3 class="text-base font-semibold text-zinc-900 dark:text-white truncate">{{ $quiz->title }}</h3>
                            <p class="mt-1 text-sm text-zinc-500">
                                {{ $quiz->class?->name }} &middot; {{ $quiz->questions_count }} {{ __('questions') }}
                                @if ($quiz->time_limit_minutes)
                                    &middot; {{ $quiz->time_limit_minutes }} {{ __('min') }}
                                @endif
                            </p>
                            <p class="mt-1 text-xs text-zinc-400">
                                {{ __('By') }} {{ $quiz->creator?->name }} &middot; {{ $quiz->created_at->format('M j, Y') }}
                            </p>
                        </div>
                        <div class="shrink-0">
                            @if ($quiz->status === 'approved' && $quiz->is_published)
                                <flux:badge color="green" size="sm">{{ __('Published') }}</flux:badge>
                            @elseif ($quiz->status === 'approved')
                                <flux:badge color="blue" size="sm">{{ __('Approved') }}</flux:badge>
                            @elseif ($quiz->status === 'pending')
                                <flux:badge color="yellow" size="sm">{{ __('Pending') }}</flux:badge>
                            @elseif ($quiz->status === 'rejected')
                                <flux:badge color="red" size="sm">{{ __('Rejected') }}</flux:badge>
                            @endif
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <flux:button variant="subtle" size="sm" href="{{ route('admin.quizzes.show', $quiz) }}" wire:navigate>{{ __('Preview') }}</flux:button>

                        @if ($quiz->status === 'pending' && $quiz->latestTeacherAction && $quiz->latestTeacherAction->status === 'pending')
                            <form method="POST" action="{{ route('admin.approvals.approve', $quiz->latestTeacherAction) }}" class="inline" x-data="{ submitting: false }" @submit="submitting = true">
                                @csrf
                                <flux:button type="submit" variant="primary" size="sm" x-bind:disabled="submitting">
                                    <span x-show="!submitting">{{ __('Approve') }}</span>
                                    <span x-show="submitting" x-cloak class="inline-flex items-center gap-1">
                                        <flux:icon name="arrow-path" class="size-3 animate-spin" /> {{ __('Approving...') }}
                                    </span>
                                </flux:button>
                            </form>

                            <div x-data="{ showRejectModal: false, rejecting: false }">
                                <flux:button @click="showRejectModal = true" variant="danger" size="sm">{{ __('Reject') }}</flux:button>

                                <div x-show="showRejectModal" x-cloak x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="showRejectModal = false" @keydown.escape.window="showRejectModal = false">
                                    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-xl p-6 max-w-md w-full mx-4" @click.stop>
                                        <div class="flex items-center gap-3 mb-4">
                                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                                                <flux:icon name="x-circle" class="size-5 text-red-600" />
                                            </div>
                                            <div>
                                                <h3 class="text-base font-semibold text-zinc-900 dark:text-white">{{ __('Reject Quiz') }}</h3>
                                                <p class="text-xs text-zinc-500">{{ $quiz->title }}</p>
                                            </div>
                                        </div>
                                        <form method="POST" action="{{ route('admin.approvals.reject', $quiz->latestTeacherAction) }}" @submit="rejecting = true">
                                            @csrf
                                            <div class="mb-4">
                                                <flux:label for="rejection_reason_quiz_{{ $quiz->id }}">{{ __('Reason for rejection') }}</flux:label>
                                                <flux:textarea
                                                    id="rejection_reason_quiz_{{ $quiz->id }}"
                                                    name="rejection_reason"
                                                    rows="3"
                                                    required
                                                    maxlength="500"
                                                    placeholder="{{ __('Explain why this quiz is being rejected...') }}"
                                                />
                                            </div>
                                            <div class="flex justify-end gap-2">
                                                <flux:button type="button" variant="subtle" size="sm" @click="showRejectModal = false">{{ __('Cancel') }}</flux:button>
                                                <flux:button type="submit" variant="danger" size="sm" x-bind:disabled="rejecting">
                                                    <span x-show="!rejecting">{{ __('Reject') }}</span>
                                                    <span x-show="rejecting" x-cloak class="inline-flex items-center gap-1">
                                                        <flux:icon name="arrow-path" class="size-3 animate-spin" /> {{ __('Rejecting...') }}
                                                    </span>
                                                </flux:button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if ($quiz->status === 'approved' && ! $quiz->is_published)
                            <form method="POST" action="{{ route('admin.quizzes.publish', $quiz) }}" class="inline" x-data="{ submitting: false }" @submit="submitting = true">
                                @csrf
                                <flux:button type="submit" variant="subtle" size="sm" class="!text-green-600" x-bind:disabled="submitting">
                                    <span x-show="!submitting">{{ __('Publish') }}</span>
                                    <span x-show="submitting" x-cloak class="inline-flex items-center gap-1">
                                        <flux:icon name="arrow-path" class="size-3 animate-spin" /> {{ __('Publishing...') }}
                                    </span>
                                </flux:button>
                            </form>
                        @endif
                        @if ($quiz->is_published)
                            <form method="POST" action="{{ route('admin.quizzes.unpublish', $quiz) }}" class="inline" x-data="{ submitting: false }" @submit="submitting = true">
                                @csrf
                                <flux:button type="submit" variant="subtle" size="sm" x-bind:disabled="submitting">
                                    <span x-show="!submitting">{{ __('Unpublish') }}</span>
                                    <span x-show="submitting" x-cloak class="inline-flex items-center gap-1">
                                        <flux:icon name="arrow-path" class="size-3 animate-spin" /> {{ __('Unpublishing...') }}
                                    </span>
                                </flux:button>
                            </form>
                            <flux:button variant="subtle" size="sm" href="{{ route('admin.quizzes.results', $quiz) }}" wire:navigate>{{ __('Results') }}</flux:button>
                        @endif
                        @if (! $quiz->is_published)
                            <div x-data="{ showConfirm: false, deleting: false }">
                                <flux:button type="button" variant="subtle" size="sm" class="!text-red-600 hover:!text-red-700" @click="showConfirm = true">
                                    {{ __('Delete') }}
                                </flux:button>
                                <div x-show="showConfirm" x-cloak x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="showConfirm = false">
                                    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-xl p-6 max-w-sm mx-4" @click.stop>
                                        <div class="flex items-center gap-3 mb-3">
                                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                                                <flux:icon name="exclamation-triangle" class="size-5 text-red-600" />
                                            </div>
                                            <h3 class="text-base font-semibold text-zinc-900 dark:text-white">{{ __('Delete Quiz') }}</h3>
                                        </div>
                                        <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-4">{{ __('Are you sure you want to delete this quiz? This action cannot be undone.') }}</p>
                                        <div class="flex justify-end gap-2">
                                            <flux:button variant="subtle" size="sm" @click="showConfirm = false">{{ __('Cancel') }}</flux:button>
                                            <form method="POST" action="{{ route('admin.quizzes.destroy', $quiz) }}" @submit="deleting = true">
                                                @csrf
                                                @method('DELETE')
                                                <flux:button type="submit" variant="danger" size="sm" x-bind:disabled="deleting">
                                                    <span x-show="!deleting">{{ __('Delete') }}</span>
                                                    <span x-show="deleting" x-cloak class="inline-flex items-center gap-1">
                                                        <flux:icon name="arrow-path" class="size-3 animate-spin" /> {{ __('Deleting...') }}
                                                    </span>
                                                </flux:button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-8 text-center">
                    <flux:icon name="academic-cap" class="mx-auto h-12 w-12 text-zinc-400" />
                    <h3 class="mt-2 text-sm font-semibold text-zinc-900 dark:text-white">{{ __('No quizzes yet') }}</h3>
                    <p class="mt-1 text-sm text-zinc-500">{{ __('Teachers can create quizzes from their dashboard.') }}</p>
                </div>
            @endforelse
        </div>

        {{ $quizzes->links() }}
    </div>
</x-layouts::app>
