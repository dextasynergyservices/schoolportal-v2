<x-layouts::app :title="__('Platform Analytics')">
    @include('partials.dashboard-styles')

    <style>
        /* ── Banner ── */
        .analytics-banner {
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 40%, #4338ca 75%, #6366f1 100%);
            border-radius: 20px;
            padding: 1.75rem 2rem;
            position: relative;
            z-index: 10; /* ensures dropdown floats above sibling KPI cards */
            /* overflow: hidden removed — would clip the date-picker dropdown */
        }
        /* Decorative blobs are clipped via a pseudo-element wrapper instead */
        .analytics-banner::before {
            content: '';
            position: absolute;
            top: -60px; right: -60px;
            width: 280px; height: 280px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
            pointer-events: none;
            overflow: hidden;
        }
        .analytics-banner::after {
            content: '';
            position: absolute;
            bottom: -80px; left: 5%;
            width: 200px; height: 200px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(99,102,241,0.3) 0%, transparent 70%);
            pointer-events: none;
            overflow: hidden;
        }
        /* ── Range tabs ── */
        .range-tabs {
            display: flex;
            align-items: center;
            gap: 2px;
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 10px;
            padding: 4px;
        }
        .range-tab {
            padding: 0.35rem 0.9rem;
            font-size: 0.8rem;
            font-weight: 600;
            border-radius: 7px;
            transition: all 0.2s ease;
            color: rgba(255,255,255,0.65);
            cursor: pointer;
            border: none;
            background: transparent;
            letter-spacing: 0.02em;
        }
        .range-tab:hover { background: rgba(255,255,255,0.12); color: #fff; }
        .range-tab.active {
            background: rgba(255,255,255,0.95);
            color: #4338ca;
            box-shadow: 0 1px 4px rgba(0,0,0,0.2);
        }
        /* ── Stat cards ── */
        .kpi-card {
            background: white;
            border: 1px solid #e4e4e7;
            border-radius: 14px;
            padding: 1.25rem 1.4rem;
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .kpi-card:hover { box-shadow: 0 6px 20px rgba(0,0,0,0.07); transform: translateY(-1px); }
        :is(.dark .kpi-card) { background: #27272a; border-color: #3f3f46; }
        .kpi-icon {
            width: 44px; height: 44px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .kpi-value {
            font-size: 1.75rem;
            font-weight: 700;
            line-height: 1.1;
            letter-spacing: -0.03em;
        }
        /* ── Chart panels ── */
        .chart-panel {
            background: white;
            border: 1px solid #e4e4e7;
            border-radius: 16px;
            overflow: hidden;
            transition: box-shadow 0.2s;
        }
        .chart-panel:hover { box-shadow: 0 8px 24px rgba(0,0,0,0.06); }
        :is(.dark .chart-panel) { background: #27272a; border-color: #3f3f46; }
        .chart-panel-header {
            padding: 1.1rem 1.4rem 0.9rem;
            border-bottom: 1px solid #f4f4f5;
            display: flex; align-items: flex-start; justify-content: space-between;
        }
        :is(.dark .chart-panel-header) { border-bottom-color: #3f3f46; }
        .chart-canvas-wrap { padding: 1rem 1.25rem 0.75rem; height: 240px; position: relative; }
        /* ── Date picker panel ── */
        .date-picker-panel {
            background: white;
            border: 1px solid #e4e4e7;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
            padding: 1rem;
            min-width: 280px;
            position: absolute;
            right: 0; top: calc(100% + 8px);
            z-index: 50;
        }
        :is(.dark .date-picker-panel) { background: #27272a; border-color: #3f3f46; box-shadow: 0 8px 30px rgba(0,0,0,0.4); }
    </style>

    <div class="space-y-6">

        {{-- ── Banner ─────────────────────────────────────────────────── --}}
        <div class="analytics-banner dash-animate" role="banner">
            <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">

                {{-- Title --}}
                <div>
                    <div class="flex items-center gap-2.5 mb-1">
                        <div class="flex items-center justify-center w-9 h-9 rounded-xl bg-white/15 backdrop-blur-sm">
                            <flux:icon.chart-bar class="w-5 h-5 text-white" />
                        </div>
                        <h1 class="text-xl sm:text-2xl font-bold text-white tracking-tight">{{ __('Platform Analytics') }}</h1>
                    </div>
                    <p class="text-sm text-white/60 ml-11">
                        @if($mode === 'custom')
                            {{ $start->format('M j, Y') }} — {{ $end->format('M j, Y') }}
                        @else
                            {{ __('Last :n months · all schools', ['n' => $months]) }}
                        @endif
                    </p>
                </div>

                {{-- Controls: range tabs + date picker + export --}}
                <div class="flex flex-wrap items-center gap-2" x-data="{
                    open: false,
                    from: '{{ $mode === 'custom' ? $start->format('Y-m-d') : now()->subMonths(11)->startOfMonth()->format('Y-m-d') }}',
                    to:   '{{ $mode === 'custom' ? $end->format('Y-m-d')   : now()->format('Y-m-d') }}',
                    apply() {
                        if (!this.from || !this.to) return;
                        var url = '{{ route('super-admin.analytics') }}?range=custom&from=' + this.from + '&to=' + this.to;
                    @if ($geoLocation !== '') url += '&geo_location={{ urlencode($geoLocation) }}'; @endif
                    window.location = url;
                    }
                }">

                    {{-- Preset tabs --}}
                    <div class="range-tabs">
                        @foreach(['3m' => '3M', '6m' => '6M', '12m' => '12M'] as $val => $label)
                            <a href="{{ route('super-admin.analytics', array_filter(['range' => $val, 'geo_location' => $geoLocation ?: null])) }}"
                               wire:navigate
                               class="range-tab {{ $range === $val && $mode !== 'custom' ? 'active' : '' }}">
                                {{ $label }}
                            </a>
                        @endforeach
                    </div>

                    {{-- Custom date range button --}}
                    <div class="relative">
                        <button type="button"
                                @click="open = !open"
                                class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-semibold transition-all
                                       {{ $mode === 'custom' ? 'bg-white text-indigo-700' : 'bg-white/10 border border-white/15 text-white/80 hover:bg-white/15 hover:text-white' }}">
                            <flux:icon.calendar-days class="w-4 h-4" />
                            {{ $mode === 'custom' ? __('Custom') : __('Custom range') }}
                        </button>

                        {{-- Picker dropdown --}}
                        <div x-show="open"
                             x-cloak
                             @click.outside="open = false"
                             class="date-picker-panel">
                            <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 mb-3 uppercase tracking-wide">
                                {{ __('Select date range') }}
                            </p>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs text-zinc-500 dark:text-zinc-400 mb-1">{{ __('From') }}</label>
                                    <input type="date" x-model="from"
                                           max="{{ now()->format('Y-m-d') }}"
                                           class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800
                                                  text-sm text-zinc-900 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                                </div>
                                <div>
                                    <label class="block text-xs text-zinc-500 dark:text-zinc-400 mb-1">{{ __('To') }}</label>
                                    <input type="date" x-model="to"
                                           max="{{ now()->format('Y-m-d') }}"
                                           class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800
                                                  text-sm text-zinc-900 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                                </div>
                                <button type="button"
                                        @click="apply()"
                                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
                                    {{ __('Apply') }}
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Export CSV --}}
                    <a href="{{ route('super-admin.analytics.export', array_filter(['range' => $range, 'from' => $mode === 'custom' ? $start->format('Y-m-d') : null, 'to' => $mode === 'custom' ? $end->format('Y-m-d') : null, 'geo_location' => $geoLocation ?: null])) }}"
                       class="flex items-center gap-1.5 px-3 py-2 rounded-lg bg-white/10 border border-white/15 text-white/80 hover:bg-white/15 hover:text-white text-sm font-semibold transition-all">
                        <flux:icon.arrow-down-tray class="w-4 h-4" />
                        {{ __('Export CSV') }}
                    </a>

                </div>
            </div>
        </div>
        {{-- ── Geo Filter ─────────────────────────────────────────────── --}}
        <form method="GET" action="{{ route('super-admin.analytics') }}" class="flex flex-wrap items-center gap-3 dash-animate">
            <input type="hidden" name="range" value="{{ $range }}" />
            @if ($mode === 'custom')
                <input type="hidden" name="from" value="{{ $start->format('Y-m-d') }}" />
                <input type="hidden" name="to" value="{{ $end->format('Y-m-d') }}" />
            @endif

            <div class="flex items-center gap-2 text-sm font-medium text-zinc-600 dark:text-zinc-400 shrink-0">
                <flux:icon.funnel class="w-4 h-4" />
                {{ __('Filter schools:') }}
            </div>

            <flux:input
                name="geo_location"
                :value="$geoLocation"
                placeholder="{{ __('City, state or country...') }}"
                class="min-w-48"
                icon="map-pin"
                aria-label="{{ __('Filter by city, state or country') }}"
            />

            <flux:button type="submit" size="sm" variant="filled">{{ __('Apply') }}</flux:button>

            @if ($geoLocation !== '')
                <flux:button
                    size="sm"
                    variant="subtle"
                    href="{{ route('super-admin.analytics', array_filter(['range' => $range, 'from' => $mode === 'custom' ? $start->format('Y-m-d') : null, 'to' => $mode === 'custom' ? $end->format('Y-m-d') : null])) }}"
                    wire:navigate
                >{{ __('Clear filter') }}</flux:button>
                <span class="inline-flex items-center gap-1.5 text-sm font-semibold text-indigo-600 dark:text-indigo-400">
                    <flux:icon.building-office-2 class="w-4 h-4" />
                    {{ __(':n school(s) matched', ['n' => $filteredSchoolCount]) }}
                </span>
            @endif
        </form>
        {{-- ── KPI Cards ───────────────────────────────────────────────── --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4">

            <div class="kpi-card dash-animate dash-animate-delay-1">
                <div class="flex items-start justify-between gap-3 mb-3">
                    <div class="kpi-icon bg-indigo-500/10 dark:bg-indigo-500/15">
                        <flux:icon.building-office-2 class="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                    </div>
                    <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-300">
                        {{ $totalSchools }} {{ __('total') }}
                    </span>
                </div>
                <p class="kpi-value text-zinc-900 dark:text-white">{{ number_format($periodSchools) }}</p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 font-medium">{{ __('New Schools') }}</p>
            </div>

            <div class="kpi-card dash-animate dash-animate-delay-2">
                <div class="flex items-start justify-between gap-3 mb-3">
                    <div class="kpi-icon bg-emerald-500/10 dark:bg-emerald-500/15">
                        <flux:icon.academic-cap class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                    </div>
                    <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-300">
                        {{ $totalStudents }} {{ __('total') }}
                    </span>
                </div>
                <p class="kpi-value text-zinc-900 dark:text-white">{{ number_format($periodStudents) }}</p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 font-medium">{{ __('New Students') }}</p>
            </div>

            <div class="kpi-card dash-animate dash-animate-delay-3">
                <div class="flex items-start justify-between gap-3 mb-3">
                    <div class="kpi-icon bg-amber-500/10 dark:bg-amber-500/15">
                        <flux:icon.banknotes class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                    </div>
                    <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-300">
                        {{ __('AI credits') }}
                    </span>
                </div>
                <p class="kpi-value text-zinc-900 dark:text-white">₦{{ number_format($periodRevenue) }}</p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 font-medium">{{ __('Revenue') }}</p>
            </div>

            <div class="kpi-card dash-animate dash-animate-delay-4">
                <div class="flex items-start justify-between gap-3 mb-3">
                    <div class="kpi-icon bg-violet-500/10 dark:bg-violet-500/15">
                        <flux:icon.sparkles class="w-5 h-5 text-violet-600 dark:text-violet-400" />
                    </div>
                    <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-violet-50 dark:bg-violet-900/30 text-violet-600 dark:text-violet-300">
                        {{ __('all schools') }}
                    </span>
                </div>
                <p class="kpi-value text-zinc-900 dark:text-white">{{ number_format($periodCredits) }}</p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 font-medium">{{ __('AI Credits Used') }}</p>
            </div>

        </div>

        {{-- ── Charts ──────────────────────────────────────────────────── --}}
        <div class="grid gap-4 lg:grid-cols-2">

            {{-- School Signups --}}
            <div class="chart-panel dash-animate dash-animate-delay-2">
                <div class="chart-panel-header">
                    <div>
                        <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('School Signups') }}</h2>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">{{ __('New schools registered per month') }}</p>
                    </div>
                    <span class="inline-flex items-center gap-1.5 text-xs text-zinc-400 dark:text-zinc-500 font-medium">
                        <span class="w-2.5 h-2.5 rounded-sm inline-block bg-indigo-600"></span>
                        {{ __('Schools') }}
                    </span>
                </div>
                <div class="chart-canvas-wrap">
                    <canvas id="chart-schools" aria-label="{{ __('School Signups Chart') }}" role="img"></canvas>
                </div>
            </div>

            {{-- Student Registrations --}}
            <div class="chart-panel dash-animate dash-animate-delay-3">
                <div class="chart-panel-header">
                    <div>
                        <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Student Registrations') }}</h2>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">{{ __('New student accounts per month') }}</p>
                    </div>
                    <span class="inline-flex items-center gap-1.5 text-xs text-zinc-400 dark:text-zinc-500 font-medium">
                        <span class="w-2.5 h-2.5 rounded-full inline-block bg-emerald-500"></span>
                        {{ __('Students') }}
                    </span>
                </div>
                <div class="chart-canvas-wrap">
                    <canvas id="chart-students" aria-label="{{ __('Student Registrations Chart') }}" role="img"></canvas>
                </div>
            </div>

            {{-- Revenue --}}
            <div class="chart-panel dash-animate dash-animate-delay-3">
                <div class="chart-panel-header">
                    <div>
                        <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Revenue Trend') }}</h2>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">{{ __('AI credit purchase revenue (₦) per month') }}</p>
                    </div>
                    <span class="inline-flex items-center gap-1.5 text-xs text-zinc-400 dark:text-zinc-500 font-medium">
                        <span class="w-2.5 h-2.5 rounded-sm inline-block bg-amber-400"></span>
                        ₦ NGN
                    </span>
                </div>
                <div class="chart-canvas-wrap">
                    <canvas id="chart-revenue" aria-label="{{ __('Revenue Chart') }}" role="img"></canvas>
                </div>
            </div>

            {{-- AI Credit Usage --}}
            <div class="chart-panel dash-animate dash-animate-delay-4">
                <div class="chart-panel-header">
                    <div>
                        <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('AI Credit Usage') }}</h2>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">{{ __('Total credits consumed per month') }}</p>
                    </div>
                    <span class="inline-flex items-center gap-1.5 text-xs text-zinc-400 dark:text-zinc-500 font-medium">
                        <span class="w-2.5 h-2.5 rounded-full inline-block bg-violet-500"></span>
                        {{ __('Credits') }}
                    </span>
                </div>
                <div class="chart-canvas-wrap">
                    <canvas id="chart-credits" aria-label="{{ __('AI Credit Usage Chart') }}" role="img"></canvas>
                </div>
            </div>

        </div>

        {{-- ── Geographic Breakdown ───────────────────────────────────── --}}
        <div class="grid gap-4 lg:grid-cols-2 dash-animate dash-animate-delay-4">

            {{-- By State --}}
            <div class="chart-panel">
                <div class="chart-panel-header flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                            <flux:icon.map-pin class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" />
                        </div>
                        <span class="text-sm font-semibold text-zinc-800 dark:text-white">{{ __('Schools by State') }}</span>
                    </div>
                    <span class="text-xs text-zinc-400">{{ __('All time') }}</span>
                </div>
                <div class="p-4 space-y-2">
                    @forelse ($geoData['by_state'] as $row)
                        @php $max = $geoData['by_state'][0]['count'] ?? 1; @endphp
                        <div class="flex items-center gap-3">
                            <span class="w-28 shrink-0 text-xs text-zinc-600 dark:text-zinc-300 truncate" title="{{ $row['state'] }}">{{ $row['state'] }}</span>
                            <div class="flex-1 bg-zinc-100 dark:bg-zinc-700 rounded-full h-2 overflow-hidden">
                                <div class="h-2 rounded-full bg-emerald-500 transition-all duration-500"
                                     style="width: {{ round(($row['count'] / $max) * 100) }}%"></div>
                            </div>
                            <span class="w-6 shrink-0 text-right text-xs font-semibold text-zinc-700 dark:text-zinc-200">{{ $row['count'] }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-400 py-4 text-center">{{ __('No geographic data yet') }}</p>
                    @endforelse
                </div>
            </div>

            {{-- Top Cities --}}
            <div class="chart-panel">
                <div class="chart-panel-header flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-sky-100 dark:bg-sky-900/30 flex items-center justify-center">
                            <flux:icon.building-office-2 class="w-3.5 h-3.5 text-sky-600 dark:text-sky-400" />
                        </div>
                        <span class="text-sm font-semibold text-zinc-800 dark:text-white">{{ __('Top Cities') }}</span>
                    </div>
                    <span class="text-xs text-zinc-400">{{ __('All time · top 20') }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="border-b border-zinc-100 dark:border-zinc-700">
                                <th class="px-4 py-2 text-left font-medium text-zinc-500">#</th>
                                <th class="px-4 py-2 text-left font-medium text-zinc-500">{{ __('City') }}</th>
                                <th class="px-4 py-2 text-left font-medium text-zinc-500">{{ __('State') }}</th>
                                <th class="px-4 py-2 text-right font-medium text-zinc-500">{{ __('Schools') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-50 dark:divide-zinc-700/50">
                            @forelse ($geoData['by_city'] as $i => $row)
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition-colors">
                                    <td class="px-4 py-2 text-zinc-400">{{ $i + 1 }}</td>
                                    <td class="px-4 py-2 font-medium text-zinc-700 dark:text-zinc-200">{{ $row['city'] }}</td>
                                    <td class="px-4 py-2 text-zinc-500">{{ $row['state'] }}</td>
                                    <td class="px-4 py-2 text-right font-semibold text-zinc-700 dark:text-zinc-200">{{ $row['count'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-6 text-center text-zinc-400">{{ __('No city data yet') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        {{-- ── Cohort Analysis ─────────────────────────────────────────── --}}
        @if (count($cohortData) > 0)
        <div class="chart-panel dash-animate dash-animate-delay-5">
            <div class="chart-panel-header flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center">
                        <flux:icon.arrow-trending-up class="w-3.5 h-3.5 text-violet-600 dark:text-violet-400" />
                    </div>
                    <div>
                        <span class="text-sm font-semibold text-zinc-800 dark:text-white">{{ __('Cohort Retention') }}</span>
                        <p class="text-xs text-zinc-400">{{ __('% of schools that signed up in month X still active in month X+3') }}</p>
                    </div>
                </div>
            </div>
            <div class="p-4 overflow-x-auto">
                <table class="w-full text-xs min-w-[480px]">
                    <thead>
                        <tr class="border-b border-zinc-100 dark:border-zinc-700">
                            <th class="px-3 py-2 text-left font-medium text-zinc-500">{{ __('Cohort') }}</th>
                            <th class="px-3 py-2 text-right font-medium text-zinc-500">{{ __('Joined') }}</th>
                            <th class="px-3 py-2 text-right font-medium text-zinc-500">{{ __('Active @ +3m') }}</th>
                            <th class="px-3 py-2 text-left font-medium text-zinc-500 w-40">{{ __('Retention') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-50 dark:divide-zinc-700/50">
                        @foreach ($cohortData as $row)
                            @php
                                $pct = $row['retention'];
                                $barColor = $pct >= 75 ? 'bg-emerald-500' : ($pct >= 50 ? 'bg-yellow-400' : 'bg-red-400');
                                $textColor = $pct >= 75 ? 'text-emerald-600 dark:text-emerald-400' : ($pct >= 50 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-500 dark:text-red-400');
                            @endphp
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition-colors">
                                <td class="px-3 py-2 font-medium text-zinc-700 dark:text-zinc-200">{{ $row['cohort'] }}</td>
                                <td class="px-3 py-2 text-right text-zinc-600 dark:text-zinc-300">{{ $row['joined'] }}</td>
                                <td class="px-3 py-2 text-right text-zinc-600 dark:text-zinc-300">{{ $row['active_3m'] }}</td>
                                <td class="px-3 py-2">
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 bg-zinc-100 dark:bg-zinc-700 rounded-full h-2 overflow-hidden">
                                            <div class="h-2 rounded-full {{ $barColor }} transition-all duration-500"
                                                 style="width: {{ min($pct, 100) }}%"></div>
                                        </div>
                                        <span class="w-12 text-right font-semibold {{ $textColor }}">{{ $pct }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- ── Back ────────────────────────────────────────────────────── --}}
        <div class="flex justify-start">
            <a href="{{ route('super-admin.dashboard') }}" wire:navigate
               class="inline-flex items-center gap-1.5 text-sm text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200 transition-colors">
                <flux:icon.arrow-left class="w-4 h-4" />
                {{ __('Back to Dashboard') }}
            </a>
        </div>

    </div>

    {{-- Inline script FIRST so window._initAnalyticsCharts is defined before the CDN onload fires --}}
    <script>
    (function () {
        var labels       = @json($monthLabels->values());
        var schoolsData  = @json($schoolsData);
        var studentsData = @json($studentsData);
        var revenueData  = @json($revenueData);
        var creditsData  = @json($creditsData);

        var isDark = document.documentElement.classList.contains('dark');
        var gridColor  = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
        var labelColor = isDark ? '#a1a1aa' : '#71717a';
        var tooltipBg  = isDark ? '#27272a' : '#18181b';

        function baseOptions(yTickFn, tooltipFn) {
            return {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 600, easing: 'easeOutQuart' },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: tooltipBg,
                        titleColor: '#fafafa',
                        bodyColor: '#d4d4d8',
                        borderColor: '#3f3f46',
                        borderWidth: 1,
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: { label: function(ctx){ return tooltipFn(ctx.parsed.y); } }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        border: { display: false },
                        ticks: { color: labelColor, font: { size: 11 }, maxRotation: 0 }
                    },
                    y: {
                        grid: { color: gridColor },
                        border: { display: false },
                        ticks: { color: labelColor, font: { size: 11 }, maxTicksLimit: 5,
                                 callback: yTickFn || function(v){ return v; } },
                        beginAtZero: true
                    }
                }
            };
        }

        function mkBar(id, data, color, yTickFn, tooltipFn) {
            var ctx = document.getElementById(id);
            if (!ctx) return;
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{ data: data, backgroundColor: color + 'bb',
                                 hoverBackgroundColor: color, borderRadius: 6,
                                 borderSkipped: false, barPercentage: 0.65, categoryPercentage: 0.85 }]
                },
                options: baseOptions(yTickFn, tooltipFn)
            });
        }

        function mkLine(id, data, color, yTickFn, tooltipFn) {
            var ctx = document.getElementById(id);
            if (!ctx) return;
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{ data: data, fill: true,
                                 backgroundColor: color + '22', borderColor: color,
                                 borderWidth: 2.5, pointRadius: 4, pointHoverRadius: 6,
                                 pointBackgroundColor: color, tension: 0.4 }]
                },
                options: baseOptions(yTickFn, tooltipFn)
            });
        }

        function init() {
            mkBar('chart-schools',  schoolsData,  '#4338ca', null,
                  function(v){ return '  ' + v + ' school' + (v !== 1 ? 's' : ''); });
            mkLine('chart-students', studentsData, '#10b981', null,
                  function(v){ return '  ' + v + ' student' + (v !== 1 ? 's' : ''); });
            mkBar('chart-revenue',  revenueData,  '#f59e0b',
                  function(v){ return v >= 1000 ? '\u20a6' + (v/1000).toFixed(0) + 'k' : '\u20a6' + v; },
                  function(v){ return '  \u20a6' + Number(v).toLocaleString(); });
            mkLine('chart-credits', creditsData,  '#8b5cf6', null,
                  function(v){ return '  ' + v + ' credit' + (v !== 1 ? 's' : ''); });
        }

        function runCharts() {
            ['chart-schools','chart-students','chart-revenue','chart-credits'].forEach(function (id) {
                var ex = Chart.getChart(id); if (ex) ex.destroy();
            });
            init();
        }

        // Called by the Chart.js onload attribute below (handles full load + first SPA navigation)
        window._initAnalyticsCharts = runCharts;

        // For subsequent SPA navigations where Chart.js is already cached (onload won't fire again).
        // Guard flag prevents duplicate listeners if the user visits this page multiple times.
        if (!window._analyticsNavListenerRegistered) {
            window._analyticsNavListenerRegistered = true;
            document.addEventListener('livewire:navigated', function () {
                if (document.getElementById('chart-schools') && typeof Chart !== 'undefined') {
                    runCharts();
                }
            });
        }
    })();
    </script>
    {{-- Chart.js from CDN — onload calls _initAnalyticsCharts once the library is ready --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.1/dist/chart.umd.min.js" onload="window._initAnalyticsCharts && window._initAnalyticsCharts()"></script>

</x-layouts::app>
