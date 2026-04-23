<x-layouts::app :title="__('Approvals')">
    <div class="space-y-6">
        <x-admin-header :title="__('Teacher Approvals')" :description="__(':count pending', ['count' => $pendingCount])" />

        @if (session('success'))
            <flux:callout variant="success" icon="check-circle">{{ session('success') }}</flux:callout>
        @endif

        <div class="flex gap-2">
            <flux:button variant="{{ $status === 'pending' ? 'filled' : 'subtle' }}" size="sm" href="{{ route('admin.approvals.index', ['status' => 'pending']) }}" wire:navigate>
                {{ __('Pending') }}
            </flux:button>
            <flux:button variant="{{ $status === 'approved' ? 'filled' : 'subtle' }}" size="sm" href="{{ route('admin.approvals.index', ['status' => 'approved']) }}" wire:navigate>
                {{ __('Approved') }}
            </flux:button>
            <flux:button variant="{{ $status === 'rejected' ? 'filled' : 'subtle' }}" size="sm" href="{{ route('admin.approvals.index', ['status' => 'rejected']) }}" wire:navigate>
                {{ __('Rejected') }}
            </flux:button>
            <flux:button variant="{{ $status === 'all' ? 'filled' : 'subtle' }}" size="sm" href="{{ route('admin.approvals.index', ['status' => 'all']) }}" wire:navigate>
                {{ __('All') }}
            </flux:button>
        </div>

        {{-- Type filter --}}
        <div class="flex flex-wrap items-end gap-3">
            <flux:select name="type" onchange="window.location.href = this.value ? '{{ route('admin.approvals.index') }}?type=' + this.value + '&status={{ $status }}' : '{{ route('admin.approvals.index') }}?status={{ $status }}'">
                <option value="">{{ __('All Types') }}</option>
                <option value="result" @selected(request('type') === 'result')>{{ __('Results') }}</option>
                <option value="assignment" @selected(request('type') === 'assignment')>{{ __('Assignments') }}</option>
                <option value="notice" @selected(request('type') === 'notice')>{{ __('Notices') }}</option>
                <option value="quiz" @selected(request('type') === 'quiz')>{{ __('Quizzes') }}</option>
                <option value="game" @selected(request('type') === 'game')>{{ __('Games') }}</option>
            </flux:select>
        </div>

        <div class="space-y-4">
            @forelse ($actions as $action)
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                @if ($action->entity_type === 'result')
                                    <flux:badge color="blue" size="sm">{{ __('Result') }}</flux:badge>
                                @elseif ($action->entity_type === 'assignment')
                                    <flux:badge color="purple" size="sm">{{ __('Assignment') }}</flux:badge>
                                @elseif ($action->entity_type === 'notice')
                                    <flux:badge color="amber" size="sm">{{ __('Notice') }}</flux:badge>
                                @elseif ($action->entity_type === 'quiz')
                                    <flux:badge color="indigo" size="sm">{{ __('Quiz') }}</flux:badge>
                                @elseif ($action->entity_type === 'game')
                                    <flux:badge color="cyan" size="sm">{{ __('Game') }}</flux:badge>
                                @else
                                    <flux:badge size="sm">{{ ucfirst($action->entity_type) }}</flux:badge>
                                @endif

                                @if ($action->status === 'pending')
                                    <flux:badge color="yellow" size="sm">{{ __('Pending') }}</flux:badge>
                                @elseif ($action->status === 'approved')
                                    <flux:badge color="green" size="sm">{{ __('Approved') }}</flux:badge>
                                @else
                                    <flux:badge color="red" size="sm">{{ __('Rejected') }}</flux:badge>
                                @endif
                            </div>
                            <p class="text-sm font-medium">
                                {{ ucfirst(str_replace('_', ' ', $action->action_type)) }}
                                {{ __('by') }} {{ $action->teacher?->name ?? __('Unknown') }}
                            </p>
                            <p class="text-xs text-zinc-500 mt-1">{{ $action->created_at->diffForHumans() }}</p>
                            @if ($action->rejection_reason)
                                <p class="text-xs text-red-600 mt-1">{{ __('Reason:') }} {{ $action->rejection_reason }}</p>
                            @endif
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            {{-- Preview link for quizzes and games --}}
                            @if ($action->entity_type === 'quiz')
                                <flux:button variant="subtle" size="xs" href="{{ route('admin.quizzes.show', $action->entity_id) }}" wire:navigate>
                                    <flux:icon name="eye" class="size-3.5 mr-1" /> {{ __('Preview') }}
                                </flux:button>
                            @elseif ($action->entity_type === 'game')
                                <flux:button variant="subtle" size="xs" href="{{ route('admin.games.show', $action->entity_id) }}" wire:navigate>
                                    <flux:icon name="eye" class="size-3.5 mr-1" /> {{ __('Preview') }}
                                </flux:button>
                            @elseif ($action->entity_type === 'notice')
                                @php $notice = $notices[$action->entity_id] ?? null; @endphp
                                @if ($notice)
                                    <div x-data="{ showPreview: false }">
                                        <flux:button variant="subtle" size="xs" @click="showPreview = true">
                                            <flux:icon name="eye" class="size-3.5 mr-1" /> {{ __('Preview') }}
                                        </flux:button>

                                        {{-- Notice Preview Modal --}}
                                        <div x-show="showPreview" x-cloak x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="showPreview = false" @keydown.escape.window="showPreview = false">
                                            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-xl max-w-3xl w-full mx-auto max-h-[85vh] flex flex-col" @click.stop>
                                                {{-- Modal Header --}}
                                                <div class="flex items-start justify-between gap-4 p-5 border-b border-zinc-200 dark:border-zinc-700">
                                                    <div>
                                                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $notice->title }}</h3>
                                                        <p class="text-xs text-zinc-500 mt-1">
                                                            {{ __('By') }} {{ $notice->creator?->name ?? __('Unknown') }} &middot; {{ $notice->created_at->format('M j, Y g:i A') }}
                                                        </p>
                                                    </div>
                                                    <button @click="showPreview = false" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 p-1">
                                                        <flux:icon name="x-mark" class="size-5" />
                                                    </button>
                                                </div>

                                                {{-- Modal Body --}}
                                                <div class="p-5 overflow-y-auto flex-1 space-y-4">
                                                    {{-- Notice Content --}}
                                                    <div class="prose prose-sm dark:prose-invert max-w-none text-zinc-700 dark:text-zinc-300">
                                                        {!! nl2br(e($notice->content)) !!}
                                                    </div>

                                                    {{-- Attachment --}}
                                                    @if ($notice->file_url)
                                                        <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg overflow-hidden">
                                                            @if ($notice->fileIsImage())
                                                                <img src="{{ $notice->file_url }}" alt="{{ $notice->file_name ?? __('Notice attachment') }}" class="w-full max-h-80 object-contain bg-zinc-50 dark:bg-zinc-900" />
                                                            @else
                                                                <div class="flex items-center gap-3 p-3 bg-zinc-50 dark:bg-zinc-700/50">
                                                                    <flux:icon.paper-clip class="size-5 text-zinc-500 shrink-0" />
                                                                    <span class="text-sm text-zinc-700 dark:text-zinc-300 truncate flex-1">{{ $notice->file_name ?? __('Attached file') }}</span>
                                                                    <a href="{{ $notice->file_url }}" target="_blank" rel="noopener noreferrer" class="text-xs font-medium text-blue-600 hover:underline whitespace-nowrap">{{ __('Open file') }}</a>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @endif

                                                    {{-- Targeting Info --}}
                                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
                                                        <div>
                                                            <span class="text-xs font-medium text-zinc-500 uppercase tracking-wide">{{ __('Levels') }}</span>
                                                            <p class="mt-0.5 text-zinc-700 dark:text-zinc-300">
                                                                @if (!empty($notice->target_levels))
                                                                    {{ \App\Models\SchoolLevel::whereIn('id', $notice->target_levels)->pluck('name')->join(', ') }}
                                                                @else
                                                                    {{ __('All levels') }}
                                                                @endif
                                                            </p>
                                                        </div>
                                                        <div>
                                                            <span class="text-xs font-medium text-zinc-500 uppercase tracking-wide">{{ __('Classes') }}</span>
                                                            <p class="mt-0.5 text-zinc-700 dark:text-zinc-300">
                                                                @if (!empty($notice->target_classes))
                                                                    {{ \App\Models\SchoolClass::whereIn('id', $notice->target_classes)->pluck('name')->join(', ') }}
                                                                @else
                                                                    {{ __('All classes') }}
                                                                @endif
                                                            </p>
                                                        </div>
                                                        <div>
                                                            <span class="text-xs font-medium text-zinc-500 uppercase tracking-wide">{{ __('Audience') }}</span>
                                                            <p class="mt-0.5 text-zinc-700 dark:text-zinc-300">
                                                                @if (!empty($notice->target_roles))
                                                                    {{ collect($notice->target_roles)->map(fn ($r) => ucfirst($r) . 's')->join(', ') }}
                                                                @else
                                                                    {{ __('Everyone') }}
                                                                @endif
                                                            </p>
                                                        </div>
                                                    </div>

                                                    @if ($notice->expires_at)
                                                        <p class="text-xs text-zinc-500">{{ __('Expires:') }} {{ $notice->expires_at->format('M j, Y') }}</p>
                                                    @endif
                                                </div>

                                                {{-- Modal Footer --}}
                                                <div class="flex justify-between items-center gap-3 p-5 border-t border-zinc-200 dark:border-zinc-700">
                                                    <flux:button variant="subtle" size="sm" href="{{ route('admin.notices.edit', $notice) }}" wire:navigate>
                                                        <flux:icon name="pencil-square" class="size-3.5 mr-1" /> {{ __('Edit') }}
                                                    </flux:button>

                                                    <div class="flex items-center gap-2">
                                                        @if ($action->status === 'pending')
                                                            <form method="POST" action="{{ route('admin.approvals.approve', $action) }}">
                                                                @csrf
                                                                <flux:button type="submit" variant="primary" size="sm">{{ __('Approve') }}</flux:button>
                                                            </form>

                                                            <div x-data="{ showReject: false, submitting: false }">
                                                                <flux:button @click="showReject = true" variant="danger" size="sm">{{ __('Reject') }}</flux:button>

                                                                {{-- Inline reject reason --}}
                                                                <div x-show="showReject" x-cloak x-transition class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50" @click.self="showReject = false" @keydown.escape.window="showReject = false">
                                                                    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-xl p-6 max-w-md w-full mx-4" @click.stop>
                                                                        <div class="flex items-center gap-3 mb-4">
                                                                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                                                                                <flux:icon name="x-circle" class="size-5 text-red-600" />
                                                                            </div>
                                                                            <div>
                                                                                <h3 class="text-base font-semibold text-zinc-900 dark:text-white">{{ __('Reject Notice') }}</h3>
                                                                                <p class="text-xs text-zinc-500">{{ $notice->title }}</p>
                                                                            </div>
                                                                        </div>
                                                                        <form method="POST" action="{{ route('admin.approvals.reject', $action) }}" @submit="submitting = true">
                                                                            @csrf
                                                                            <div class="mb-4">
                                                                                <label for="modal_rejection_reason_{{ $action->id }}" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('Reason for rejection') }}</label>
                                                                                <textarea
                                                                                    id="modal_rejection_reason_{{ $action->id }}"
                                                                                    name="rejection_reason"
                                                                                    rows="3"
                                                                                    required
                                                                                    maxlength="500"
                                                                                    placeholder="{{ __('Explain why this notice is being rejected...') }}"
                                                                                    class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-3 py-2 text-sm text-zinc-900 dark:text-white placeholder-zinc-400 focus:border-red-500 focus:ring-1 focus:ring-red-500"
                                                                                ></textarea>
                                                                            </div>
                                                                            <div class="flex justify-end gap-2">
                                                                                <flux:button type="button" variant="subtle" size="sm" @click="showReject = false">{{ __('Cancel') }}</flux:button>
                                                                                <flux:button type="submit" variant="danger" size="sm" x-bind:disabled="submitting">
                                                                                    <span x-show="!submitting">{{ __('Reject') }}</span>
                                                                                    <span x-show="submitting" x-cloak class="inline-flex items-center gap-1">
                                                                                        <flux:icon name="arrow-path" class="size-3 animate-spin" /> {{ __('Rejecting...') }}
                                                                                    </span>
                                                                                </flux:button>
                                                                            </div>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endif

                                                        <flux:button variant="subtle" size="sm" @click="showPreview = false">{{ __('Close') }}</flux:button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @elseif ($action->entity_type === 'result')
                                @php $result = $results[$action->entity_id] ?? null; @endphp
                                @if ($result)
                                    <div x-data="{ showPreview: false }">
                                        <flux:button variant="subtle" size="xs" @click="showPreview = true">
                                            <flux:icon name="eye" class="size-3.5 mr-1" /> {{ __('Preview') }}
                                        </flux:button>

                                        {{-- Result Preview Modal --}}
                                        <div x-show="showPreview" x-cloak x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="showPreview = false" @keydown.escape.window="showPreview = false">
                                            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-xl max-w-2xl w-full mx-auto max-h-[85vh] flex flex-col" @click.stop>
                                                {{-- Modal Header --}}
                                                <div class="flex items-start justify-between gap-4 p-5 border-b border-zinc-200 dark:border-zinc-700">
                                                    <div>
                                                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Result Preview') }}</h3>
                                                        <p class="text-xs text-zinc-500 mt-1">
                                                            {{ __('Uploaded by') }} {{ $action->teacher?->name ?? __('Unknown') }} &middot; {{ $action->created_at->format('M j, Y g:i A') }}
                                                        </p>
                                                    </div>
                                                    <button @click="showPreview = false" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 p-1">
                                                        <flux:icon name="x-mark" class="size-5" />
                                                    </button>
                                                </div>

                                                {{-- Modal Body --}}
                                                <div class="p-5 overflow-y-auto flex-1 space-y-4">
                                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                        <div class="rounded-lg bg-zinc-50 dark:bg-zinc-700/50 p-3">
                                                            <span class="text-xs font-medium text-zinc-500 uppercase tracking-wide">{{ __('Student') }}</span>
                                                            <p class="mt-1 text-sm font-medium text-zinc-900 dark:text-white">{{ $result->student?->name ?? '—' }}</p>
                                                            <p class="text-xs text-zinc-500">{{ $result->student?->username ?? '' }}</p>
                                                        </div>
                                                        <div class="rounded-lg bg-zinc-50 dark:bg-zinc-700/50 p-3">
                                                            <span class="text-xs font-medium text-zinc-500 uppercase tracking-wide">{{ __('Class') }}</span>
                                                            <p class="mt-1 text-sm font-medium text-zinc-900 dark:text-white">{{ $result->class?->name ?? '—' }}</p>
                                                        </div>
                                                        <div class="rounded-lg bg-zinc-50 dark:bg-zinc-700/50 p-3">
                                                            <span class="text-xs font-medium text-zinc-500 uppercase tracking-wide">{{ __('Session') }}</span>
                                                            <p class="mt-1 text-sm font-medium text-zinc-900 dark:text-white">{{ $result->session?->name ?? '—' }}</p>
                                                        </div>
                                                        <div class="rounded-lg bg-zinc-50 dark:bg-zinc-700/50 p-3">
                                                            <span class="text-xs font-medium text-zinc-500 uppercase tracking-wide">{{ __('Term') }}</span>
                                                            <p class="mt-1 text-sm font-medium text-zinc-900 dark:text-white">{{ $result->term?->name ?? '—' }}</p>
                                                        </div>
                                                    </div>

                                                    @if ($result->notes)
                                                        <div>
                                                            <span class="text-xs font-medium text-zinc-500 uppercase tracking-wide">{{ __('Notes') }}</span>
                                                            <p class="mt-1 text-sm text-zinc-700 dark:text-zinc-300">{{ $result->notes }}</p>
                                                        </div>
                                                    @endif

                                                    {{-- Result PDF --}}
                                                    @if ($result->file_url)
                                                        <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg overflow-hidden">
                                                            <div class="flex items-center gap-3 p-3 bg-zinc-50 dark:bg-zinc-700/50">
                                                                <flux:icon name="document" class="size-5 text-red-500 shrink-0" />
                                                                <span class="text-sm text-zinc-700 dark:text-zinc-300 truncate flex-1">{{ __('Result PDF') }}</span>
                                                                <a href="{{ $result->file_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-xs font-medium text-blue-600 hover:underline whitespace-nowrap">
                                                                    <flux:icon name="arrow-top-right-on-square" class="size-3" /> {{ __('Open PDF') }}
                                                                </a>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>

                                                {{-- Modal Footer --}}
                                                <div class="flex justify-between items-center gap-3 p-5 border-t border-zinc-200 dark:border-zinc-700">
                                                    <flux:button variant="subtle" size="sm" href="{{ route('admin.results.show', $result) }}" wire:navigate>
                                                        <flux:icon name="arrow-top-right-on-square" class="size-3.5 mr-1" /> {{ __('Full Details') }}
                                                    </flux:button>

                                                    <div class="flex items-center gap-2">
                                                        @if ($action->status === 'pending')
                                                            <form method="POST" action="{{ route('admin.approvals.approve', $action) }}">
                                                                @csrf
                                                                <flux:button type="submit" variant="primary" size="sm">{{ __('Approve') }}</flux:button>
                                                            </form>

                                                            @include('admin.approvals._reject-modal', ['action' => $action, 'entityLabel' => __('Result')])
                                                        @endif

                                                        <flux:button variant="subtle" size="sm" @click="showPreview = false">{{ __('Close') }}</flux:button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @elseif ($action->entity_type === 'assignment')
                                @php $assignment = $assignments[$action->entity_id] ?? null; @endphp
                                @if ($assignment)
                                    <div x-data="{ showPreview: false }">
                                        <flux:button variant="subtle" size="xs" @click="showPreview = true">
                                            <flux:icon name="eye" class="size-3.5 mr-1" /> {{ __('Preview') }}
                                        </flux:button>

                                        {{-- Assignment Preview Modal --}}
                                        <div x-show="showPreview" x-cloak x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="showPreview = false" @keydown.escape.window="showPreview = false">
                                            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-xl max-w-2xl w-full mx-auto max-h-[85vh] flex flex-col" @click.stop>
                                                {{-- Modal Header --}}
                                                <div class="flex items-start justify-between gap-4 p-5 border-b border-zinc-200 dark:border-zinc-700">
                                                    <div>
                                                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">
                                                            {{ $assignment->title ?: __('Assignment Preview') }}
                                                        </h3>
                                                        <p class="text-xs text-zinc-500 mt-1">
                                                            {{ __('Uploaded by') }} {{ $action->teacher?->name ?? __('Unknown') }} &middot; {{ $action->created_at->format('M j, Y g:i A') }}
                                                        </p>
                                                    </div>
                                                    <button @click="showPreview = false" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 p-1">
                                                        <flux:icon name="x-mark" class="size-5" />
                                                    </button>
                                                </div>

                                                {{-- Modal Body --}}
                                                <div class="p-5 overflow-y-auto flex-1 space-y-4">
                                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                        <div class="rounded-lg bg-zinc-50 dark:bg-zinc-700/50 p-3">
                                                            <span class="text-xs font-medium text-zinc-500 uppercase tracking-wide">{{ __('Class') }}</span>
                                                            <p class="mt-1 text-sm font-medium text-zinc-900 dark:text-white">{{ $assignment->class?->name ?? '—' }}</p>
                                                        </div>
                                                        <div class="rounded-lg bg-zinc-50 dark:bg-zinc-700/50 p-3">
                                                            <span class="text-xs font-medium text-zinc-500 uppercase tracking-wide">{{ __('Week') }}</span>
                                                            <p class="mt-1 text-sm font-medium text-zinc-900 dark:text-white">{{ __('Week :num', ['num' => $assignment->week_number]) }}</p>
                                                        </div>
                                                        <div class="rounded-lg bg-zinc-50 dark:bg-zinc-700/50 p-3">
                                                            <span class="text-xs font-medium text-zinc-500 uppercase tracking-wide">{{ __('Session') }}</span>
                                                            <p class="mt-1 text-sm font-medium text-zinc-900 dark:text-white">{{ $assignment->session?->name ?? '—' }}</p>
                                                        </div>
                                                        <div class="rounded-lg bg-zinc-50 dark:bg-zinc-700/50 p-3">
                                                            <span class="text-xs font-medium text-zinc-500 uppercase tracking-wide">{{ __('Term') }}</span>
                                                            <p class="mt-1 text-sm font-medium text-zinc-900 dark:text-white">{{ $assignment->term?->name ?? '—' }}</p>
                                                        </div>
                                                    </div>

                                                    @if ($assignment->due_date)
                                                        <div>
                                                            <span class="text-xs font-medium text-zinc-500 uppercase tracking-wide">{{ __('Due Date') }}</span>
                                                            <p class="mt-1 text-sm text-zinc-700 dark:text-zinc-300">{{ \Carbon\Carbon::parse($assignment->due_date)->format('M j, Y') }}</p>
                                                        </div>
                                                    @endif

                                                    @if ($assignment->description)
                                                        <div>
                                                            <span class="text-xs font-medium text-zinc-500 uppercase tracking-wide">{{ __('Description') }}</span>
                                                            <div class="mt-1 prose prose-sm dark:prose-invert max-w-none text-zinc-700 dark:text-zinc-300">
                                                                {!! nl2br(e($assignment->description)) !!}
                                                            </div>
                                                        </div>
                                                    @endif

                                                    {{-- Assignment File --}}
                                                    @if ($assignment->file_url)
                                                        <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg overflow-hidden">
                                                            <div class="flex items-center gap-3 p-3 bg-zinc-50 dark:bg-zinc-700/50">
                                                                <flux:icon.paper-clip class="size-5 text-zinc-500 shrink-0" />
                                                                <span class="text-sm text-zinc-700 dark:text-zinc-300 truncate flex-1">{{ __('Assignment File') }}</span>
                                                                <a href="{{ $assignment->file_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-xs font-medium text-blue-600 hover:underline whitespace-nowrap">
                                                                    <flux:icon name="arrow-top-right-on-square" class="size-3" /> {{ __('Open File') }}
                                                                </a>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>

                                                {{-- Modal Footer --}}
                                                <div class="flex justify-between items-center gap-3 p-5 border-t border-zinc-200 dark:border-zinc-700">
                                                    <flux:button variant="subtle" size="sm" href="{{ route('admin.assignments.edit', $assignment) }}" wire:navigate>
                                                        <flux:icon name="pencil-square" class="size-3.5 mr-1" /> {{ __('Edit') }}
                                                    </flux:button>

                                                    <div class="flex items-center gap-2">
                                                        @if ($action->status === 'pending')
                                                            <form method="POST" action="{{ route('admin.approvals.approve', $action) }}">
                                                                @csrf
                                                                <flux:button type="submit" variant="primary" size="sm">{{ __('Approve') }}</flux:button>
                                                            </form>

                                                            @include('admin.approvals._reject-modal', ['action' => $action, 'entityLabel' => __('Assignment')])
                                                        @endif

                                                        <flux:button variant="subtle" size="sm" @click="showPreview = false">{{ __('Close') }}</flux:button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endif

                            {{-- Generic approve/reject for types without preview modals --}}
                            @if ($action->status === 'pending' && ! in_array($action->entity_type, ['notice', 'result', 'assignment']))
                                <form method="POST" action="{{ route('admin.approvals.approve', $action) }}">
                                    @csrf
                                    <flux:button type="submit" variant="primary" size="xs">{{ __('Approve') }}</flux:button>
                                </form>

                                @include('admin.approvals._reject-modal', ['action' => $action, 'entityLabel' => ucfirst($action->entity_type)])
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-8 text-zinc-500">
                    {{ __('No :status approvals found.', ['status' => $status === 'all' ? '' : $status]) }}
                </div>
            @endforelse
        </div>

        {{ $actions->links() }}
    </div>
</x-layouts::app>
