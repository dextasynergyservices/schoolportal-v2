{{--
    Progress Timeline — shows average score per term across sessions as an SVG sparkline.
    Props: $timelineData = array of sessions, each with terms and scores.
    Only rendered when there is at least 1 data point.

    $timelineData shape (from controller):
    [
        ['session' => '2024/2025', 'terms' => [
            ['label' => 'T1', 'score' => 65, 'current' => false],
            ['label' => 'T2', 'score' => 70, 'current' => false],
            ['label' => 'T3', 'score' => 74, 'current' => false],
        ]],
        ['session' => '2025/2026', 'terms' => [
            ['label' => 'T1', 'score' => 78, 'current' => true],
        ]],
    ]
--}}
@php
    // Flatten all points in chronological order for the SVG chart
    $allPoints = [];
    foreach ($timelineData as $sessionGroup) {
        foreach ($sessionGroup['terms'] as $term) {
            $allPoints[] = $term;
        }
    }

    $pointCount = count($allPoints);

    if ($pointCount === 0) {
        return;
    }

    // SVG dimensions
    $svgW = 300;
    $svgH = 80;
    $padX = 14;
    $padY = 12;
    $chartW = $svgW - $padX * 2;
    $chartH = $svgH - $padY * 2;

    // Score range — clamp 0-100, but use a tighter range for visual interest
    $scores = array_column($allPoints, 'score');
    $minScore = max(0, min($scores) - 10);
    $maxScore = min(100, max($scores) + 10);
    if ($maxScore - $minScore < 20) {
        $minScore = max(0, $minScore - 10);
        $maxScore = min(100, $maxScore + 10);
    }
    $scoreRange = $maxScore - $minScore ?: 1;

    // Calculate x,y for each point
    $coords = [];
    foreach ($allPoints as $i => $pt) {
        $x = $padX + ($pointCount > 1 ? ($i / ($pointCount - 1)) * $chartW : $chartW / 2);
        $y = $padY + $chartH - (($pt['score'] - $minScore) / $scoreRange) * $chartH;
        $coords[] = ['x' => round($x, 1), 'y' => round($y, 1), ...$pt];
    }

    // Build polyline points string
    $polyline = implode(' ', array_map(fn ($c) => "{$c['x']},{$c['y']}", $coords));

    // Area fill path (close below the line)
    $areaPath = "M {$coords[0]['x']},{$coords[0]['y']} ";
    foreach (array_slice($coords, 1) as $c) {
        $areaPath .= "L {$c['x']},{$c['y']} ";
    }
    $last = end($coords);
    $first = reset($coords);
    $areaPath .= "L {$last['x']},".($padY + $chartH)." L {$first['x']},".($padY + $chartH)." Z";

    // Latest score info
    $latestPoint = end($coords);
    $trend = null;
    if ($pointCount >= 2) {
        $prev = $coords[$pointCount - 2];
        $diff = $latestPoint['score'] - $prev['score'];
        $trend = $diff > 0 ? 'up' : ($diff < 0 ? 'down' : 'flat');
        $trendDiff = abs($diff);
    }
@endphp

<div class="dash-panel dash-animate dash-animate-delay-2" style="padding: 0;" aria-label="{{ __('Academic Progress Timeline') }}">
    <div class="dash-panel-header">
        <h2 class="text-sm font-semibold text-zinc-900 dark:text-white flex items-center gap-2">
            <flux:icon.chart-bar class="w-4 h-4 text-teal-500" />
            {{ __('My Academic Progress') }}
        </h2>
        @if ($pointCount >= 2)
            <div class="flex items-center gap-1 text-xs">
                @if ($trend === 'up')
                    <span class="flex items-center gap-0.5 text-emerald-600 dark:text-emerald-400 font-semibold">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.577 4.878a.75.75 0 0 1 .919-.53l4.78 1.281a.75.75 0 0 1 .531.919l-1.281 4.78a.75.75 0 0 1-1.449-.387l.81-3.022a19.407 19.407 0 0 0-5.594 5.203.75.75 0 0 1-1.139.093L7 10.06l-4.72 4.72a.75.75 0 0 1-1.06-1.061l5.25-5.25a.75.75 0 0 1 1.06 0l3.074 3.073a20.923 20.923 0 0 1 5.545-4.931l-3.042-.815a.75.75 0 0 1-.53-.918Z" clip-rule="evenodd" /></svg>
                        +{{ $trendDiff }}%
                    </span>
                @elseif ($trend === 'down')
                    <span class="flex items-center gap-0.5 text-red-500 dark:text-red-400 font-semibold">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M1.22 5.222a.75.75 0 0 1 1.06 0L7 9.942l3.768-3.769a.75.75 0 0 1 1.113.058 20.908 20.908 0 0 1 5.545 4.931l3.042-.815a.75.75 0 1 1 .388 1.449l-4.78 1.28a.75.75 0 0 1-.919-.53l-1.28-4.781a.75.75 0 0 1 1.449-.387l.81 3.021a19.407 19.407 0 0 0-5.594-5.203l-3.07 3.069a.75.75 0 0 1-1.13-.089L2.28 6.282a.75.75 0 0 1-.53-.919.75.75 0 0 1 0 .0Z" clip-rule="evenodd" /></svg>
                        -{{ $trendDiff }}%
                    </span>
                @else
                    <span class="text-zinc-400">{{ __('Steady') }}</span>
                @endif
            </div>
        @endif
    </div>

    <div class="px-4 pb-4 pt-2">
        {{-- SVG Sparkline Chart --}}
        <div class="relative">
            <svg viewBox="0 0 {{ $svgW }} {{ $svgH }}" class="w-full h-20" role="img" aria-label="{{ __('Score trend chart') }}" preserveAspectRatio="none">
                <defs>
                    <linearGradient id="timeline-grad-{{ $student->id }}" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#14b8a6" stop-opacity="0.25"/>
                        <stop offset="100%" stop-color="#14b8a6" stop-opacity="0.02"/>
                    </linearGradient>
                </defs>

                {{-- Area fill --}}
                <path d="{{ $areaPath }}" fill="url(#timeline-grad-{{ $student->id }})" />

                {{-- Line --}}
                <polyline
                    points="{{ $polyline }}"
                    fill="none"
                    stroke="#14b8a6"
                    stroke-width="2"
                    stroke-linejoin="round"
                    stroke-linecap="round"
                />

                {{-- Data points --}}
                @foreach ($coords as $pt)
                    @if ($pt['current'])
                        {{-- "You are here" — filled larger dot with pulse ring --}}
                        <circle cx="{{ $pt['x'] }}" cy="{{ $pt['y'] }}" r="5" fill="#14b8a6" />
                        <circle cx="{{ $pt['x'] }}" cy="{{ $pt['y'] }}" r="8" fill="none" stroke="#14b8a6" stroke-width="1.5" opacity="0.4" />
                    @else
                        <circle cx="{{ $pt['x'] }}" cy="{{ $pt['y'] }}" r="3.5" fill="white" stroke="#14b8a6" stroke-width="2" />
                    @endif
                @endforeach
            </svg>
        </div>

        {{-- Session × Term legend row --}}
        <div class="mt-3 flex flex-wrap gap-x-4 gap-y-3">
            @foreach ($timelineData as $sessionGroup)
                <div class="min-w-0">
                    <p class="text-[10px] uppercase tracking-widest text-zinc-400 dark:text-zinc-500 mb-1.5">{{ $sessionGroup['session'] }}</p>
                    <div class="flex items-center gap-1.5">
                        @foreach ($sessionGroup['terms'] as $term)
                            <div class="flex flex-col items-center gap-0.5">
                                <span class="inline-flex items-center justify-center px-2 py-0.5 rounded text-[11px] font-bold
                                    {{ $term['current']
                                        ? 'bg-teal-500 text-white ring-2 ring-teal-300 dark:ring-teal-700'
                                        : ($term['score'] >= 70
                                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                                            : ($term['score'] >= 50
                                                ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'
                                                : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400')) }}">
                                    {{ $term['score'] }}%
                                </span>
                                <span class="text-[10px] text-zinc-400 dark:text-zinc-500">{{ $term['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Arrow separator between sessions --}}
                @if (! $loop->last)
                    <div class="flex items-center self-end mb-3">
                        <flux:icon.arrow-right class="w-3.5 h-3.5 text-zinc-300 dark:text-zinc-600" />
                    </div>
                @endif
            @endforeach
        </div>

        {{-- "You are here" callout --}}
        @php
            $currentPoint = collect($allPoints)->firstWhere('current', true);
        @endphp
        @if ($currentPoint)
            <div class="mt-3 flex items-center gap-2 text-xs text-teal-700 dark:text-teal-400 bg-teal-50 dark:bg-teal-900/20 rounded-lg px-3 py-2">
                <span class="w-2 h-2 rounded-full bg-teal-500 shrink-0 ring-2 ring-teal-300 dark:ring-teal-700"></span>
                <span>{{ __('You are here') }} &mdash; <strong>{{ $currentPoint['label_full'] ?? $currentPoint['label'] }}</strong>: <strong>{{ $currentPoint['score'] }}%</strong></span>
            </div>
        @endif
    </div>
</div>
