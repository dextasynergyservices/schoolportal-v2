<x-layouts::app :title="__('Credit History')">

    @include('partials.dashboard-styles')

    <style>
        /* ── History banner ── */
        .history-banner {
            background: linear-gradient(135deg, #064e3b 0%, #065f46 45%, #047857 100%);
            border-radius: 20px;
            padding: 1.75rem 2rem;
            position: relative;
            overflow: hidden;
        }
        .history-banner::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse 60% 80% at 90% 30%, rgba(110,231,183,0.2) 0%, transparent 60%),
                        radial-gradient(ellipse 40% 50% at 5% 80%, rgba(52,211,153,0.1) 0%, transparent 55%);
            pointer-events: none;
        }
        .history-banner::after {
            content: '';
            position: absolute;
            top: -70px; right: -40px;
            width: 240px; height: 240px;
            border-radius: 50%;
            background: rgba(16,185,129,0.12);
            pointer-events: none;
        }

        /* ── KPI strip ── */
        .kpi-strip {
            background: white;
            border: 1px solid #e4e4e7;
            border-radius: 16px;
            padding: 1.1rem 1.4rem;
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .kpi-strip:hover { box-shadow: 0 6px 22px rgba(0,0,0,0.07); transform: translateY(-1px); }
        :is(.dark .kpi-strip) { background: #27272a; border-color: #3f3f46; }

        /* ── Tabs ── */
        .tab-nav {
            display: flex;
            background: #f4f4f5;
            border-radius: 14px;
            padding: 4px;
            width: fit-content;
        }
        :is(.dark .tab-nav) { background: #27272a; }
        .tab-item {
            padding: 0.55rem 1.2rem;
            font-size: 0.875rem;
            font-weight: 500;
            border-radius: 10px;
            cursor: pointer;
            transition: background 0.15s, color 0.15s, box-shadow 0.15s;
            color: #71717a;
        }
        :is(.dark .tab-item) { color: #a1a1aa; }
        .tab-item.active {
            background: white;
            color: #18181b;
            box-shadow: 0 1px 6px rgba(0,0,0,0.1);
        }
        :is(.dark .tab-item.active) { background: #3f3f46; color: white; }

        /* ── Filters panel ── */
        .filters-panel {
            background: white;
            border: 1px solid #e4e4e7;
            border-radius: 18px;
            padding: 1.1rem 1.4rem;
        }
        :is(.dark .filters-panel) { background: #27272a; border-color: #3f3f46; }

        /* ── Table container ── */
        .history-table-wrap {
            overflow: hidden;
            border-radius: 18px;
            border: 1px solid #e4e4e7;
            background: white;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
            transition: box-shadow 0.2s;
        }
        .history-table-wrap:hover { box-shadow: 0 6px 24px rgba(0,0,0,0.06); }
        :is(.dark .history-table-wrap) { background: #27272a; border-color: #3f3f46; }
    </style>

    <div x-data="{ tab: '{{ $activeTab }}' }" class="space-y-6">

        {{-- ── Banner ─────────────────────────────────────────────── --}}
        <div class="history-banner dash-animate" role="banner">
            <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2.5 mb-1">
                        <div class="flex items-center justify-center w-9 h-9 rounded-xl bg-white/15 backdrop-blur-sm">
                            <flux:icon.clock class="w-5 h-5 text-white" />
                        </div>
                        <h1 class="text-xl sm:text-2xl font-bold text-white tracking-tight">
                            {{ __('Credit History') }}
                        </h1>
                    </div>
                    <p class="text-sm text-white/55 ml-11">
                        {{ __('Full audit of AI credit usage and purchase transactions across all schools.') }}
                    </p>
                </div>
            </div>

            {{-- Sub-nav --}}
            <div class="relative z-10 mt-5">
                @include('partials.credits-subnav')
            </div>
        </div>

        {{-- ── KPI strip ───────────────────────────────────────────── --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 dash-animate dash-animate-delay-1">
            <div class="kpi-strip">
                <p class="text-xs font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider mb-1">{{ __('Credits Purchased') }}</p>
                <p class="text-2xl font-extrabold text-zinc-900 dark:text-white tracking-tight">{{ number_format($totalPurchasedCredits) }}</p>
                <p class="text-xs text-zinc-400 mt-1">{{ __('completed transactions') }}</p>
            </div>
            <div class="kpi-strip">
                <p class="text-xs font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider mb-1">{{ __('Usage Log Entries') }}</p>
                <p class="text-2xl font-extrabold text-zinc-900 dark:text-white tracking-tight">{{ number_format($usageLogs->total()) }}</p>
                <p class="text-xs text-zinc-400 mt-1">{{ __('AI generations') }}</p>
            </div>
            <div class="kpi-strip col-span-2 sm:col-span-1">
                <p class="text-xs font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider mb-1">{{ __('Total Revenue') }}</p>
                <p class="text-2xl font-extrabold text-zinc-900 dark:text-white tracking-tight">
                    ₦{{ number_format((float) $totalRevenue) }}
                </p>
                <p class="text-xs text-zinc-400 mt-1">{{ __('from completed purchases') }}</p>
            </div>
        </div>

        {{-- ── Tab toggle ────────────────────────────────────────── --}}
        <div class="dash-animate dash-animate-delay-2 flex items-center gap-4 flex-wrap">
            <div class="tab-nav">
                <button type="button"
                        class="tab-item"
                        :class="{ 'active': tab === 'usage' }"
                        @click="tab = 'usage'">
                    <span class="flex items-center gap-1.5">
                        <flux:icon.bolt class="w-4 h-4" />
                        {{ __('Usage Log') }}
                        <span class="text-xs opacity-70">({{ number_format($usageLogs->total()) }})</span>
                    </span>
                </button>
                <button type="button"
                        class="tab-item"
                        :class="{ 'active': tab === 'purchases' }"
                        @click="tab = 'purchases'">
                    <span class="flex items-center gap-1.5">
                        <flux:icon.credit-card class="w-4 h-4" />
                        {{ __('Purchases') }}
                        <span class="text-xs opacity-70">({{ number_format($purchases->total()) }})</span>
                    </span>
                </button>
            </div>
        </div>

        {{-- ── Shared filter form ────────────────────────────────── --}}
        <div class="filters-panel dash-animate dash-animate-delay-2">
            <form method="GET" action="{{ route('super-admin.credits.history') }}">
                <input type="hidden" name="tab" :value="tab" />

                <div class="flex items-center gap-2 mb-4">
                    <flux:icon.funnel class="w-4 h-4 text-zinc-400" />
                    <p class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">{{ __('Filter Records') }}</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">{{ __('School') }}</label>
                        <select name="school_id" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-sm text-zinc-900 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            <option value="">{{ __('All schools') }}</option>
                            @foreach ($schools as $s)
                                <option value="{{ $s->id }}" @selected((string) request('school_id') === (string) $s->id)>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Usage type (usage tab) / Status (purchase tab) --}}
                    <div x-show="tab === 'usage'">
                        <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">{{ __('Type') }}</label>
                        <select name="usage_type" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-sm text-zinc-900 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            <option value="">{{ __('All types') }}</option>
                            <option value="quiz" @selected(request('usage_type') === 'quiz')>{{ __('Quiz') }}</option>
                            <option value="game" @selected(request('usage_type') === 'game')>{{ __('Game') }}</option>
                            <option value="exam" @selected(request('usage_type') === 'exam')>{{ __('Exam') }}</option>
                            <option value="assessment" @selected(request('usage_type') === 'assessment')>{{ __('Assessment') }}</option>
                            <option value="assignment" @selected(request('usage_type') === 'assignment')>{{ __('Assignment') }}</option>
                        </select>
                    </div>
                    <div x-show="tab === 'purchases'" x-cloak>
                        <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">{{ __('Status') }}</label>
                        <select name="status" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-sm text-zinc-900 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            <option value="">{{ __('All statuses') }}</option>
                            <option value="completed" @selected(request('status') === 'completed')>{{ __('Completed') }}</option>
                            <option value="pending" @selected(request('status') === 'pending')>{{ __('Pending') }}</option>
                            <option value="failed" @selected(request('status') === 'failed')>{{ __('Failed') }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">{{ __('From') }}</label>
                        <input type="date" name="from" value="{{ request('from') }}" max="{{ now()->format('Y-m-d') }}"
                               class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-sm text-zinc-900 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">{{ __('To') }}</label>
                        <input type="date" name="to" value="{{ request('to') }}" max="{{ now()->format('Y-m-d') }}"
                               class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-sm text-zinc-900 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500" />
                    </div>

                    <div class="flex items-end gap-2">
                        <button type="submit" class="flex-1 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg transition-colors">{{ __('Apply') }}</button>
                        @if (request()->hasAny(['school_id', 'usage_type', 'status', 'from', 'to']))
                            <a href="{{ route('super-admin.credits.history') }}" wire:navigate class="px-3 py-2 bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-600 dark:text-zinc-400 text-sm rounded-lg transition-colors">✕</a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        {{-- ══════════════════════════════════════════════════════════ --}}
        {{-- Usage Log Tab                                             --}}
        {{-- ══════════════════════════════════════════════════════════ --}}
        <div x-show="tab === 'usage'" class="dash-animate dash-animate-delay-3">
            @if ($usageLogs->isEmpty())
                <flux:callout variant="info" icon="information-circle">
                    {{ __('No usage log entries found for the selected filters.') }}
                </flux:callout>
            @else
                <div class="history-table-wrap">
                    <div class="px-5 py-3.5 border-b border-zinc-100 dark:border-zinc-800 flex items-center justify-between gap-4">
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __(':from–:to of :total', ['from' => number_format($usageLogs->firstItem()), 'to' => number_format($usageLogs->lastItem()), 'total' => number_format($usageLogs->total())]) }}
                        </p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[560px] text-sm">
                            <thead>
                                <tr class="border-b border-zinc-100 dark:border-zinc-800 bg-zinc-50/60 dark:bg-zinc-800/40">
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-400">{{ __('Date') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-400">{{ __('School') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-400">{{ __('User') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-400 hidden md:table-cell">{{ __('Level') }}</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-zinc-400">{{ __('Type') }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-400">{{ __('Credits') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($usageLogs as $entry)
                                    <tr class="border-b border-zinc-50 dark:border-zinc-800/60 last:border-0 hover:bg-emerald-50/30 dark:hover:bg-emerald-950/10 transition-colors">
                                        <td class="px-4 py-3.5">
                                            <p class="text-xs font-medium text-zinc-700 dark:text-zinc-300 whitespace-nowrap">{{ $entry->created_at?->format('d M Y') }}</p>
                                            <p class="text-xs text-zinc-400 mt-0.5 tabular-nums">{{ $entry->created_at?->format('H:i') }}</p>
                                        </td>
                                        <td class="px-4 py-3.5">
                                            <p class="font-medium text-zinc-800 dark:text-zinc-200">{{ $entry->school?->name ?? '—' }}</p>
                                        </td>
                                        <td class="px-4 py-3.5">
                                            <p class="text-sm text-zinc-700 dark:text-zinc-300">{{ $entry->user?->name ?? '—' }}</p>
                                        </td>
                                        <td class="px-4 py-3.5 hidden md:table-cell">
                                            <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $entry->level?->name ?? '—' }}</span>
                                        </td>
                                        <td class="px-4 py-3.5 text-center">
                                            @if ($entry->usage_type === 'quiz')
                                                <flux:badge color="indigo" size="sm">{{ __('Quiz') }}</flux:badge>
                                            @elseif ($entry->usage_type === 'game')
                                                <flux:badge color="fuchsia" size="sm">{{ __('Game') }}</flux:badge>
                                            @elseif ($entry->usage_type === 'exam')
                                                <flux:badge color="rose" size="sm">{{ __('Exam') }}</flux:badge>
                                            @elseif ($entry->usage_type === 'assessment')
                                                <flux:badge color="amber" size="sm">{{ __('Assessment') }}</flux:badge>
                                            @else
                                                <flux:badge color="teal" size="sm">{{ __('Assignment') }}</flux:badge>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3.5 text-right">
                                            <span class="text-sm font-bold text-zinc-800 dark:text-zinc-200 tabular-nums">{{ $entry->credits_used }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                @if ($usageLogs->hasPages())
                    <div class="flex justify-center mt-4">
                        {{ $usageLogs->appends(['tab' => 'usage'])->links() }}
                    </div>
                @endif
            @endif
        </div>

        {{-- ══════════════════════════════════════════════════════════ --}}
        {{-- Purchase History Tab                                      --}}
        {{-- ══════════════════════════════════════════════════════════ --}}
        <div x-show="tab === 'purchases'" x-cloak class="dash-animate dash-animate-delay-3">
            @if ($purchases->isEmpty())
                <flux:callout variant="info" icon="information-circle">
                    {{ __('No purchase records found for the selected filters.') }}
                </flux:callout>
            @else
                <div class="history-table-wrap">
                    <div class="px-5 py-3.5 border-b border-zinc-100 dark:border-zinc-800 flex items-center justify-between gap-4">
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __(':from–:to of :total', ['from' => number_format($purchases->firstItem()), 'to' => number_format($purchases->lastItem()), 'total' => number_format($purchases->total())]) }}
                        </p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[640px] text-sm">
                            <thead>
                                <tr class="border-b border-zinc-100 dark:border-zinc-800 bg-zinc-50/60 dark:bg-zinc-800/40">
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-400">{{ __('Date') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-400">{{ __('School') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-400 hidden md:table-cell">{{ __('Purchased by') }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-400">{{ __('Credits') }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-400">{{ __('Amount') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-400 hidden lg:table-cell">{{ __('Method') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-400 hidden xl:table-cell">{{ __('Reference') }}</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-zinc-400">{{ __('Status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($purchases as $purchase)
                                    <tr class="border-b border-zinc-50 dark:border-zinc-800/60 last:border-0 hover:bg-emerald-50/30 dark:hover:bg-emerald-950/10 transition-colors">
                                        <td class="px-4 py-3.5">
                                            <p class="text-xs font-medium text-zinc-700 dark:text-zinc-300 whitespace-nowrap">{{ $purchase->created_at?->format('d M Y') }}</p>
                                            <p class="text-xs text-zinc-400 mt-0.5 tabular-nums">{{ $purchase->created_at?->format('H:i') }}</p>
                                        </td>
                                        <td class="px-4 py-3.5">
                                            <p class="font-medium text-zinc-800 dark:text-zinc-200">{{ $purchase->school?->name ?? '—' }}</p>
                                        </td>
                                        <td class="px-4 py-3.5 hidden md:table-cell">
                                            <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $purchase->purchaser?->name ?? '—' }}</p>
                                        </td>
                                        <td class="px-4 py-3.5 text-right">
                                            <span class="text-sm font-bold text-zinc-800 dark:text-zinc-200 tabular-nums">{{ number_format($purchase->credits) }}</span>
                                        </td>
                                        <td class="px-4 py-3.5 text-right">
                                            <span class="text-sm font-semibold text-emerald-700 dark:text-emerald-400 tabular-nums">₦{{ number_format((float) $purchase->amount_naira) }}</span>
                                        </td>
                                        <td class="px-4 py-3.5 hidden lg:table-cell">
                                            <span class="text-xs text-zinc-500 dark:text-zinc-400 capitalize">{{ $purchase->payment_method ?? '—' }}</span>
                                        </td>
                                        <td class="px-4 py-3.5 hidden xl:table-cell">
                                            <span class="text-xs font-mono text-zinc-400 dark:text-zinc-500">{{ $purchase->reference ?? '—' }}</span>
                                        </td>
                                        <td class="px-4 py-3.5 text-center">
                                            @if ($purchase->status === 'completed')
                                                <flux:badge color="green" size="sm">{{ __('Completed') }}</flux:badge>
                                            @elseif ($purchase->status === 'pending')
                                                <flux:badge color="amber" size="sm">{{ __('Pending') }}</flux:badge>
                                            @else
                                                <flux:badge color="red" size="sm">{{ __('Failed') }}</flux:badge>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                @if ($purchases->hasPages())
                    <div class="flex justify-center mt-4">
                        {{ $purchases->appends(['tab' => 'purchases'])->links() }}
                    </div>
                @endif
            @endif
        </div>

    </div>

</x-layouts::app>
