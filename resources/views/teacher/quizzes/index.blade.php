<x-layouts::app :title="__('My Quizzes')">
    <div class="space-y-6">
        <x-admin-header
            :title="__('My Quizzes')"
            :action="route('teacher.quizzes.create')"
            :actionLabel="__('Create Quiz')"
            actionIcon="plus"
        />

        @if (session('success'))
            <flux:callout variant="success" icon="check-circle">{{ session('success') }}</flux:callout>
        @endif

        @if (session('error'))
            <flux:callout variant="danger" icon="x-circle">{{ session('error') }}</flux:callout>
        @endif

        <form method="GET" action="{{ route('teacher.quizzes.index') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <flux:select name="class_id">
                    <option value="">{{ __('All Classes') }}</option>
                    @foreach ($classes as $class)
                        <option value="{{ $class->id }}" @selected(request('class_id') == $class->id)>{{ $class->name }}</option>
                    @endforeach
                </flux:select>
            </div>
            <div>
                <flux:select name="status">
                    <option value="">{{ __('All Statuses') }}</option>
                    <option value="draft" @selected(request('status') === 'draft')>{{ __('Draft') }}</option>
                    <option value="pending" @selected(request('status') === 'pending')>{{ __('Pending') }}</option>
                    <option value="approved" @selected(request('status') === 'approved')>{{ __('Approved') }}</option>
                    <option value="rejected" @selected(request('status') === 'rejected')>{{ __('Rejected') }}</option>
                </flux:select>
            </div>
            <flux:button type="submit" variant="filled" size="sm">{{ __('Filter') }}</flux:button>
            @if (request()->hasAny(['class_id', 'status']))
                <flux:button variant="subtle" size="sm" href="{{ route('teacher.quizzes.index') }}" wire:navigate>{{ __('Clear') }}</flux:button>
            @endif
        </form>

        <div class="grid gap-4">
            @forelse ($quizzes as $quiz)
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 sm:p-5">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <h3 class="text-base font-semibold text-zinc-900 dark:text-white truncate">{{ $quiz->title }}</h3>
                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $quiz->class?->name ?? '—' }}
                                &middot; {{ $quiz->total_questions }} {{ __('questions') }}
                                @if ($quiz->time_limit_minutes)
                                    &middot; {{ $quiz->time_limit_minutes }} {{ __('min') }}
                                @endif
                                &middot; {{ $quiz->passing_score }}% {{ __('to pass') }}
                            </p>
                            <p class="mt-1 text-xs text-zinc-400 dark:text-zinc-500">
                                {{ $quiz->session?->name }} / {{ $quiz->term?->name }}
                                &middot; {{ $quiz->created_at->format('M j, Y') }}
                            </p>
                        </div>
                        <div class="shrink-0">
                            @if ($quiz->status === 'approved' && $quiz->is_published)
                                <flux:badge color="green" size="sm">{{ __('Published') }}</flux:badge>
                            @elseif ($quiz->status === 'approved')
                                <flux:badge color="blue" size="sm">{{ __('Approved') }}</flux:badge>
                            @elseif ($quiz->status === 'pending')
                                <flux:badge color="yellow" size="sm">{{ __('Pending Approval') }}</flux:badge>
                            @elseif ($quiz->status === 'rejected')
                                <flux:badge color="red" size="sm">{{ __('Rejected') }}</flux:badge>
                            @else
                                <flux:badge color="zinc" size="sm">{{ __('Draft') }}</flux:badge>
                            @endif
                        </div>
                    </div>

                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <flux:button variant="subtle" size="sm" href="{{ route('teacher.quizzes.show', $quiz) }}" wire:navigate>
                            {{ __('Preview') }}
                        </flux:button>

                        @if (in_array($quiz->status, ['draft', 'pending', 'rejected']))
                            <flux:button variant="subtle" size="sm" href="{{ route('teacher.quizzes.edit', $quiz) }}" wire:navigate>
                                {{ __('Edit') }}
                            </flux:button>
                        @endif

                        @if ($quiz->status === 'approved' || ($quiz->status === 'approved' && $quiz->is_published))
                            <flux:button variant="subtle" size="sm" href="{{ route('teacher.quizzes.results', $quiz) }}" wire:navigate>
                                {{ __('Results') }}
                            </flux:button>
                        @endif

                        @if (! $quiz->is_published && ! in_array($quiz->status, ['approved']))
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
                                            <form method="POST" action="{{ route('teacher.quizzes.destroy', $quiz) }}" @submit="deleting = true">
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
                    <p class="mt-1 text-sm text-zinc-500">{{ __('Create your first quiz to get started.') }}</p>
                    <div class="mt-4">
                        <flux:button variant="primary" size="sm" href="{{ route('teacher.quizzes.create') }}" wire:navigate>
                            {{ __('Create Quiz') }}
                        </flux:button>
                    </div>
                </div>
            @endforelse
        </div>

        {{ $quizzes->links() }}
    </div>
</x-layouts::app>
