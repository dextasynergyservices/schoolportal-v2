<x-layouts::app :title="__('Audit Logs')">

    @include('partials.dashboard-styles')

    <style>
        /* ── Banner ── */
        .audit-banner {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 45%, #0f172a 100%);
            border-radius: 20px;
            padding: 1.75rem 2rem;
            position: relative;
            overflow: hidden;
        }
        .audit-banner::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse 70% 80% at 80% 50%, rgba(99,102,241,0.18) 0%, transparent 65%),
                        radial-gradient(ellipse 50% 60% at 10% 80%, rgba(6,182,212,0.12) 0%, transparent 60%);
            pointer-events: none;
        }
        .audit-banner::after {
            content: '';
            position: absolute;
            top: -60px; right: -40px;
            width: 260px; height: 260px;
            border-radius: 50%;
            background: rgba(99,102,241,0.1);
            pointer-events: none;
        }
        /* ── Filters panel ── */
        .filters-panel {
            background: white;
            border: 1px solid #e4e4e7;
            border-radius: 16px;
            padding: 1.25rem 1.5rem;
        }
        :is(.dark .filters-panel) { background: #18181b; border-color: #3f3f46; }
        /* ── Table rows ── */
        .log-tbody tr.log-data-row { cursor: pointer; transition: background 0.12s; }
        .log-tbody tr.log-data-row:hover { background: rgba(99,102,241,0.035); }
        :is(.dark) .log-tbody tr.log-data-row:hover { background: rgba(99,102,241,0.07); }

        /* ── Log table ── */
        /* ── JSON diff block ── */
        .json-code {
            font-family: 'JetBrains Mono', 'Fira Code', ui-monospace, monospace;
            font-size: 0.76rem;
            line-height: 1.65;
            background: #0f172a;
            color: #94a3b8;
            border-radius: 10px;
            padding: 0.85rem 1rem;
            overflow-x: auto;
            white-space: pre-wrap;
            word-break: break-all;
        }
        :is(.dark) .json-code { background: #09090b; }
        /* ── Mini KPI cards ── */
        .mini-kpi {
            border-radius: 14px;
            padding: 1rem 1.25rem;
            border: 1px solid #e4e4e7;
            background: white;
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .mini-kpi:hover { box-shadow: 0 6px 20px rgba(0,0,0,0.07); transform: translateY(-1px); }
        :is(.dark .mini-kpi) { background: #27272a; border-color: #3f3f46; }
    </style>

    <div class="space-y-6">

        {{-- ── Banner ─────────────────────────────────────────────── --}}
        <div class="audit-banner dash-animate" role="banner">
            <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2.5 mb-1">
                        <div class="flex items-center justify-center w-9 h-9 rounded-xl bg-white/15 backdrop-blur-sm">
                            <flux:icon.clipboard-document-list class="w-5 h-5 text-white" />
                        </div>
                        <h1 class="text-xl sm:text-2xl font-bold text-white tracking-tight">{{ __('Platform Audit Log') }}</h1>
                    </div>
                    <p class="text-sm text-white/55 ml-11">
                        {{ __('Every action across all schools — searchable, filterable, exportable.') }}
                    </p>
                </div>

                {{-- Export button --}}
                <a href="{{ route('super-admin.audit-logs.export', request()->only(['school_id','category','action','user','from','to'])) }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white/10 border border-white/15 text-white/90 text-sm font-semibold hover:bg-white/18 hover:text-white transition-all whitespace-nowrap">
                    <flux:icon.arrow-down-tray class="w-4 h-4" />
                    {{ __('Export CSV') }}
                </a>
            </div>
        </div>

        {{-- ── Summary KPI strip ───────────────────────────────── --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 dash-animate dash-animate-delay-1">
            <div class="mini-kpi">
                <p class="text-xs font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider mb-1">{{ __('Total Entries') }}</p>
                <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ number_format($total) }}</p>
            </div>
            <div class="mini-kpi">
                <p class="text-xs font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider mb-1">{{ __('Filtered Results') }}</p>
                <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ number_format($logs->total()) }}</p>
            </div>
            <div class="mini-kpi">
                <p class="text-xs font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider mb-1">{{ __('Schools') }}</p>
                <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $schools->count() }}</p>
            </div>
            <div class="mini-kpi">
                <p class="text-xs font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider mb-1">{{ __('Page') }}</p>
                <p class="text-2xl font-bold text-zinc-900 dark:text-white">
                    {{ $logs->currentPage() }}<span class="text-base font-normal text-zinc-400">/{{ $logs->lastPage() }}</span>
                </p>
            </div>
        </div>

        {{-- ── Filters ─────────────────────────────────────────────── --}}
        <div class="filters-panel dash-animate dash-animate-delay-2">
            <form method="GET" action="{{ route('super-admin.audit-logs.index') }}">
                <div class="flex items-center gap-2 mb-4">
                    <flux:icon.funnel class="w-4 h-4 text-zinc-400" />
                    <p class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">{{ __('Filter Logs') }}</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">{{ __('School') }}</label>
                        <select name="school_id" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-sm text-zinc-900 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">{{ __('All schools') }}</option>
                            @foreach ($schools as $s)
                                <option value="{{ $s->id }}" @selected((string) request('school_id') === (string) $s->id)>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">{{ __('Category') }}</label>
                        <select name="category" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-sm text-zinc-900 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">{{ __('All categories') }}</option>
                            @foreach (['created' => 'Created', 'updated' => 'Updated', 'deleted' => 'Deleted', 'login' => 'Login / Logout', 'credit' => 'AI Credits', 'approved' => 'Approved', 'rejected' => 'Rejected', 'promoted' => 'Promotions', 'password' => 'Passwords'] as $val => $lbl)
                                <option value="{{ $val }}" @selected(request('category') === $val)>{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">{{ __('User') }}</label>
                        <input type="text" name="user" value="{{ request('user') }}" placeholder="{{ __('Search by name…') }}" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-sm text-zinc-900 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">{{ __('Action keyword') }}</label>
                        <input type="text" name="action" value="{{ request('action') }}" placeholder="{{ __('e.g. student.created') }}" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-sm text-zinc-900 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">{{ __('From') }}</label>
                        <input type="date" name="from" value="{{ request('from') }}" max="{{ now()->format('Y-m-d') }}" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-sm text-zinc-900 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">{{ __('To') }}</label>
                        <input type="date" name="to" value="{{ request('to') }}" max="{{ now()->format('Y-m-d') }}" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-sm text-zinc-900 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                    </div>
                </div>
                <div class="flex items-center gap-2 mt-4">
                    <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg transition-colors">{{ __('Apply') }}</button>
                    @if (request()->hasAny(['school_id', 'category', 'action', 'user', 'from', 'to']))
                        <a href="{{ route('super-admin.audit-logs.index') }}" wire:navigate class="px-4 py-2 bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-300 text-sm font-medium rounded-lg transition-colors">{{ __('Clear all') }}</a>
                    @endif
                </div>
            </form>
        </div>

        {{-- ── Results ─────────────────────────────────────────── --}}
        <div class="dash-animate dash-animate-delay-3">
            @if ($logs->isEmpty())
                <flux:callout variant="info" icon="information-circle">
                    {{ __('No audit log entries match your current filters. Try broadening your search.') }}
                </flux:callout>
            @else
                <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="px-5 py-3.5 border-b border-zinc-100 dark:border-zinc-800 flex items-center justify-between gap-4">
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __(':from–:to of :total', ['from' => number_format($logs->firstItem()), 'to' => number_format($logs->lastItem()), 'total' => number_format($logs->total())]) }}
                        </p>
                        <p class="text-xs text-zinc-400 hidden sm:block">{{ __('↓ Click a row to inspect change details') }}</p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[640px] text-sm">
                            <thead>
                                <tr class="border-b border-zinc-100 dark:border-zinc-800 bg-zinc-50/60 dark:bg-zinc-800/40">
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-400">{{ __('Date') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-400">{{ __('Action') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-400">{{ __('User') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-400 hidden md:table-cell">{{ __('School') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-400 hidden lg:table-cell">{{ __('Entity') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-400 hidden xl:table-cell">{{ __('IP') }}</th>
                                    <th class="px-4 py-3 w-10"></th>
                                </tr>
                            </thead>

                            {{-- One <tbody> per log entry — Alpine x-data is scoped to the tbody, shared between data row + detail row --}}
                            @foreach ($logs as $log)
                                @php
                                    $color     = \App\Http\Controllers\SuperAdmin\AuditLogController::actionColor($log->action);
                                    $hasDetail = $log->old_values || $log->new_values;
                                    $oldJson   = $log->old_values ? json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : null;
                                    $newJson   = $log->new_values ? json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : null;
                                @endphp

                                <tbody class="log-tbody" x-data="{{ $hasDetail ? '{ open: false }' : '{}' }}">

                                    {{-- Main data row --}}
                                    <tr class="log-data-row border-b border-zinc-50 dark:border-zinc-800/60"
                                        @if ($hasDetail) @click="open = !open" @endif>

                                        <td class="px-4 py-3.5">
                                            <time datetime="{{ $log->created_at->toIso8601String() }}" title="{{ $log->created_at->format('d M Y H:i:s') }}" class="block">
                                                <p class="text-xs font-medium text-zinc-700 dark:text-zinc-300 whitespace-nowrap">{{ $log->created_at->diffForHumans() }}</p>
                                                <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-0.5">{{ $log->created_at->format('d M Y') }}</p>
                                            </time>
                                        </td>

                                        <td class="px-4 py-3.5">
                                            <flux:badge :color="$color" size="sm" inset="top bottom">
                                                <span class="font-mono">{{ $log->action }}</span>
                                            </flux:badge>
                                        </td>

                                        <td class="px-4 py-3.5">
                                            @if ($log->user)
                                                <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200 leading-none">{{ $log->user->name }}</p>
                                                <p class="text-xs text-zinc-400 capitalize mt-1">{{ str_replace('_', ' ', $log->user->role ?? '') }}</p>
                                            @else
                                                <span class="text-xs text-zinc-400 italic">{{ __('System') }}</span>
                                            @endif
                                        </td>

                                        <td class="px-4 py-3.5 hidden md:table-cell">
                                            @if ($log->school)
                                                <a href="{{ route('super-admin.schools.show', $log->school) }}" wire:navigate @click.stop class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline leading-none">
                                                    {{ $log->school->name }}
                                                </a>
                                            @else
                                                <span class="text-xs text-zinc-300 dark:text-zinc-600">—</span>
                                            @endif
                                        </td>

                                        <td class="px-4 py-3.5 hidden lg:table-cell">
                                            @if ($log->entity_type)
                                                <span class="inline-flex items-center gap-1 text-xs font-mono px-2 py-0.5 rounded-md bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400">
                                                    {{ class_basename($log->entity_type) }}
                                                    @if ($log->entity_id) <span class="text-zinc-400">#{{ $log->entity_id }}</span> @endif
                                                </span>
                                            @else
                                                <span class="text-xs text-zinc-300 dark:text-zinc-600">—</span>
                                            @endif
                                        </td>

                                        <td class="px-4 py-3.5 hidden xl:table-cell">
                                            <span class="text-xs font-mono text-zinc-400 dark:text-zinc-500">{{ $log->ip_address ?? '—' }}</span>
                                        </td>

                                        <td class="px-3 py-3.5 text-right">
                                            @if ($hasDetail)
                                                <flux:icon.chevron-down class="w-4 h-4 text-zinc-400 transition-transform duration-200 inline-block" x-bind:class="open ? 'rotate-180 text-indigo-500' : ''" />
                                            @endif
                                        </td>
                                    </tr>

                                    {{-- Expandable detail row (shares x-data with parent tbody) --}}
                                    @if ($hasDetail)
                                        <tr x-show="open" x-collapse x-cloak class="bg-zinc-50 dark:bg-zinc-800/40">
                                            <td colspan="7" class="px-6 py-4">
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                    @if ($oldJson)
                                                        <div>
                                                            <p class="text-xs font-semibold uppercase tracking-wider text-red-500 dark:text-red-400 mb-2">{{ __('Before') }}</p>
                                                            <pre class="json-code">{{ $oldJson }}</pre>
                                                        </div>
                                                    @endif
                                                    @if ($newJson)
                                                        <div>
                                                            <p class="text-xs font-semibold uppercase tracking-wider text-green-500 dark:text-green-400 mb-2">{{ __('After') }}</p>
                                                            <pre class="json-code">{{ $newJson }}</pre>
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endif

                                </tbody>
                            @endforeach
                        </table>
                    </div>
                </div>
            @endif
        </div>

        {{-- ── Pagination ────────────────────────────────────────── --}}
        @if ($logs->hasPages())
            <div class="flex justify-center dash-animate dash-animate-delay-4">
                {{ $logs->links() }}
            </div>
        @endif

    </div>
    {{-- Use a different table structure below and replace the table above. --}}

</x-layouts::app>
