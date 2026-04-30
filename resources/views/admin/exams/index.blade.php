<x-layouts::app :title="__('CBT')">
    <div class="space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold text-zinc-900 dark:text-white">{{ __('CBT') }}</h1>
                <p class="mt-1 text-sm text-zinc-500">{{ __('Create and manage exams, assessments, and assignments.') }}</p>
            </div>
            <flux:dropdown>
                <flux:button variant="primary" icon-trailing="chevron-down">{{ __('Create') }}</flux:button>
                <flux:menu>
                    <flux:menu.item icon="document-text" href="{{ route('admin.exams.create') }}" wire:navigate>{{ __('Create Exam') }}</flux:menu.item>
                    <flux:menu.item icon="clipboard-document-check" href="{{ route('admin.exams.create', ['category' => 'assessment']) }}" wire:navigate>{{ __('Create Assessment') }}</flux:menu.item>
                    <flux:menu.item icon="document-arrow-up" href="{{ route('admin.exams.create', ['category' => 'assignment']) }}" wire:navigate>{{ __('Create Assignment') }}</flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        </div>

        @if (session('success'))
            <flux:callout variant="success" icon="check-circle">{{ session('success') }}</flux:callout>
        @endif
        @if (session('error'))
            <flux:callout variant="danger" icon="x-circle">{{ session('error') }}</flux:callout>
        @endif

        {{-- Category Tabs --}}
        @php
            $currentCategory = request('category');
            $tabs = [
                null => __('All'),
                'exam' => __('Exams'),
                'assessment' => __('Assessments'),
                'assignment' => __('Assignments'),
            ];
        @endphp
        <div class="flex gap-1 overflow-x-auto border-b border-zinc-200 dark:border-zinc-700" role="tablist" aria-label="{{ __('Category filter') }}">
            @foreach ($tabs as $tabValue => $tabLabel)
                @php
                    $isActive = $currentCategory === ($tabValue ?: null);
                    // Preserve other query params when switching tabs
                    $tabUrl = route('admin.exams.index', array_filter(array_merge(request()->except('category', 'page'), $tabValue ? ['category' => $tabValue] : [])));
                @endphp
                <a href="{{ $tabUrl }}"
                   role="tab"
                   aria-selected="{{ $isActive ? 'true' : 'false' }}"
                   @if ($isActive) aria-current="page" @endif
                   class="shrink-0 px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors {{ $isActive ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}"
                   wire:navigate>
                    {{ $tabLabel }}
                </a>
            @endforeach
        </div>

        {{-- Filters --}}
        <div class="flex flex-wrap gap-3">
            <form method="GET" class="flex flex-wrap items-end gap-3">
                @if ($currentCategory)
                    <input type="hidden" name="category" value="{{ $currentCategory }}">
                @endif
                <flux:select name="level_id" label="{{ __('Level') }}" class="w-40">
                    <option value="">{{ __('All Levels') }}</option>
                    @foreach ($levels as $level)
                        <option value="{{ $level->id }}" @selected(request('level_id') == $level->id)>{{ $level->name }}</option>
                    @endforeach
                </flux:select>
                <flux:select name="class_id" label="{{ __('Class') }}" class="w-40">
                    <option value="">{{ __('All Classes') }}</option>
                    @foreach ($classes as $class)
                        <option value="{{ $class->id }}" @selected(request('class_id') == $class->id)>{{ $class->name }}</option>
                    @endforeach
                </flux:select>
                <flux:select name="subject_id" label="{{ __('Subject') }}" class="w-40">
                    <option value="">{{ __('All Subjects') }}</option>
                    @foreach ($subjects as $subject)
                        <option value="{{ $subject->id }}" @selected(request('subject_id') == $subject->id)>{{ $subject->name }}</option>
                    @endforeach
                </flux:select>
                <flux:select name="status" label="{{ __('Status') }}" class="w-32">
                    <option value="">{{ __('All') }}</option>
                    <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                    <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                    <option value="approved" @selected(request('status') === 'approved')>Approved</option>
                    <option value="rejected" @selected(request('status') === 'rejected')>Rejected</option>
                </flux:select>
                <flux:button type="submit" variant="subtle" icon="funnel" size="sm">{{ __('Filter') }}</flux:button>
                @if (request()->hasAny(['level_id', 'class_id', 'subject_id', 'status']))
                    <flux:button href="{{ route('admin.exams.index', $currentCategory ? ['category' => $currentCategory] : []) }}" variant="ghost" size="sm">{{ __('Clear') }}</flux:button>
                @endif
            </form>

            {{-- Bulk Export --}}
            @if ($exams->isNotEmpty())
                <form method="GET" action="{{ route('admin.exams.export-bulk-results-csv') }}" class="flex items-end gap-2">
                    @if (request('level_id'))
                        <input type="hidden" name="level_id" value="{{ request('level_id') }}">
                    @endif
                    @if (request('class_id'))
                        <input type="hidden" name="class_id" value="{{ request('class_id') }}">
                    @endif
                    @if (request('subject_id'))
                        <input type="hidden" name="subject_id" value="{{ request('subject_id') }}">
                    @endif
                    <flux:button type="submit" variant="subtle" icon="arrow-down-tray" size="sm">{{ __('Export All Results (CSV)') }}</flux:button>
                </form>
            @endif
        </div>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Title') }}</flux:table.column>
                @unless ($currentCategory)
                    <flux:table.column>{{ __('Type') }}</flux:table.column>
                @endunless
                <flux:table.column>{{ __('Subject') }}</flux:table.column>
                <flux:table.column>{{ __('Class') }}</flux:table.column>
                <flux:table.column class="text-center">{{ __('Questions') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Created By') }}</flux:table.column>
                <flux:table.column class="w-40" />
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($exams as $exam)
                    @php
                        $examRoutePrefix = 'admin.exams';
                        $examTypeLabel = match ($exam->category) {
                            'assessment' => __('Assessment'),
                            'assignment' => __('Assignment'),
                            default => __('Exam'),
                        };
                        $typeColors = ['assessment' => 'sky', 'assignment' => 'amber', 'exam' => 'indigo'];
                    @endphp
                    <flux:table.row>
                        <flux:table.cell class="font-medium">
                            <a href="{{ route($examRoutePrefix . '.show', $exam) }}" class="hover:underline" wire:navigate>{{ $exam->title }}</a>
                            @if ($exam->is_published)
                                <flux:badge color="green" size="sm" class="ml-1">{{ __('Published') }}</flux:badge>
                            @endif
                        </flux:table.cell>
                        @unless ($currentCategory)
                            <flux:table.cell>
                                <flux:badge :color="$typeColors[$exam->category] ?? 'zinc'" size="sm">{{ $examTypeLabel }}</flux:badge>
                            </flux:table.cell>
                        @endunless
                        <flux:table.cell>{{ $exam->subject?->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $exam->class?->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell class="text-center">{{ $exam->questions_count }}</flux:table.cell>
                        <flux:table.cell>
                            @php
                                $statusColors = ['draft' => 'zinc', 'pending' => 'amber', 'approved' => 'green', 'rejected' => 'red'];
                            @endphp
                            <flux:badge :color="$statusColors[$exam->status] ?? 'zinc'" size="sm">{{ ucfirst($exam->status) }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="text-sm text-zinc-500">{{ $exam->creator?->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-1">
                                <flux:button variant="subtle" size="xs" icon="eye" href="{{ route($examRoutePrefix . '.show', $exam) }}" wire:navigate aria-label="{{ __('View') }}" />
                                @if ($exam->is_published)
                                    <flux:button variant="subtle" size="xs" icon="chart-bar" href="{{ route($examRoutePrefix . '.results', $exam) }}" wire:navigate aria-label="{{ __('Results') }}" />
                                @endif
                                <flux:button variant="subtle" size="xs" icon="pencil-square" href="{{ route($examRoutePrefix . '.edit', $exam) }}" wire:navigate aria-label="{{ __('Edit') }}" />
                                @if ($exam->status === 'approved' && ! $exam->is_published)
                                    <form method="POST" action="{{ route($examRoutePrefix . '.publish', $exam) }}" class="inline">
                                        @csrf
                                        <flux:button type="submit" variant="subtle" size="xs" icon="arrow-up-circle" aria-label="{{ __('Publish') }}" />
                                    </form>
                                @elseif ($exam->is_published)
                                    <form method="POST" action="{{ route($examRoutePrefix . '.unpublish', $exam) }}" class="inline">
                                        @csrf
                                        <flux:button type="submit" variant="subtle" size="xs" icon="arrow-down-circle" aria-label="{{ __('Unpublish') }}" />
                                    </form>
                                @endif
                                <x-confirm-delete
                                    :action="route($examRoutePrefix . '.destroy', $exam)"
                                    :title="__('Delete ' . $examTypeLabel)"
                                    :message="__('Are you sure? All questions will be permanently deleted.')"
                                    :ariaLabel="__('Delete :name', ['name' => $exam->title])"
                                />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell :colspan="$currentCategory ? 7 : 8" class="text-center py-8 text-zinc-500">
                            {{ __('No items found.') }}
                            <a href="{{ route('admin.exams.index') }}" class="text-indigo-600 hover:underline" wire:navigate>{{ __('View all') }}</a>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        <div class="mt-4">{{ $exams->links() }}</div>
    </div>
</x-layouts::app>
