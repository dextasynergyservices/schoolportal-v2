@php
    $reportType = $report->report_type ?? 'full_term';
    $isSession = $reportType === 'session';
    $isMidterm = $reportType === 'midterm';
    $showTermBreakdown = $isSession && ($config?->show_term_breakdown_in_session ?? true);

    // Determine report title
    $reportTitle = match ($reportType) {
        'midterm' => 'Mid-Term Progress Report',
        'session' => 'Session Report Card',
        default => 'Student Report Card',
    };

    // Determine period label
    $periodLabel = $isSession
        ? ($report->session->name ?? '')
        : ($report->session->name ?? '') . ' — ' . ($report->term->name ?? '');

    // For session reports, collect unique term names from the snapshot
    $sessionTermNames = [];
    if ($isSession && is_array($report->subject_scores_snapshot)) {
        foreach ($report->subject_scores_snapshot as $snap) {
            foreach ($snap['term_scores'] ?? [] as $ts) {
                $sessionTermNames[$ts['term_id']] = $ts['term_name'];
            }
        }
        ksort($sessionTermNames);
    }
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $reportTitle }} — {{ $report->student->name ?? 'Student' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 11px; color: #1a1a2e; line-height: 1.4; }
        .page { padding: 20px 30px; position: relative; }

        /* School Header */
        .school-header { text-align: center; border-bottom: 3px double #4338ca; padding-bottom: 12px; margin-bottom: 15px; }
        .school-header h1 { font-size: 18px; text-transform: uppercase; color: #1e1b4b; letter-spacing: 1px; }
        .school-header p { font-size: 10px; color: #64748b; margin-top: 2px; }
        .school-header .report-title { font-size: 13px; font-weight: bold; color: #4338ca; margin-top: 6px; text-transform: uppercase; letter-spacing: 0.5px; }
        .school-logo { width: 60px; height: 60px; border-radius: 50%; margin: 0 auto 8px; display: block; }

        /* Student Info */
        .student-info { display: table; width: 100%; margin-bottom: 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; }
        .student-info .row { display: table-row; }
        .student-info .cell { display: table-cell; padding: 5px 10px; width: 25%; }
        .student-info .label { font-size: 9px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; }
        .student-info .value { font-size: 11px; font-weight: bold; color: #1e293b; }

        /* Summary Bar */
        .summary-bar { display: table; width: 100%; margin-bottom: 12px; }
        .summary-item { display: table-cell; text-align: center; padding: 8px; background: #eef2ff; border: 1px solid #c7d2fe; }
        .summary-item:first-child { border-radius: 4px 0 0 4px; }
        .summary-item:last-child { border-radius: 0 4px 4px 0; }
        .summary-item .big { font-size: 18px; font-weight: bold; color: #4338ca; }
        .summary-item .small { font-size: 8px; color: #6366f1; text-transform: uppercase; }

        /* Score Table */
        table.scores { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        table.scores th { background: #4338ca; color: white; font-size: 9px; text-transform: uppercase; padding: 6px 5px; text-align: center; letter-spacing: 0.3px; }
        table.scores th:first-child { text-align: left; }
        table.scores td { padding: 5px; text-align: center; border-bottom: 1px solid #e2e8f0; font-size: 10px; }
        table.scores td:first-child { text-align: left; font-weight: 600; color: #1e293b; }
        table.scores tr:nth-child(even) { background: #f8fafc; }
        table.scores .total-col { background: #eef2ff; font-weight: bold; color: #4338ca; }
        table.scores .grade-col { font-weight: bold; }

        /* Grading Key */
        .grading-key { margin-bottom: 12px; padding: 6px 10px; background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 4px; font-size: 9px; }
        .grading-key strong { color: #1e293b; }

        /* Traits Grid */
        .traits-section { display: table; width: 100%; margin-bottom: 12px; }
        .traits-col { display: table-cell; width: 50%; vertical-align: top; padding-right: 10px; }
        .traits-col:last-child { padding-right: 0; padding-left: 10px; }
        .traits-col h4 { font-size: 10px; text-transform: uppercase; color: #4338ca; border-bottom: 1px solid #c7d2fe; padding-bottom: 3px; margin-bottom: 5px; }
        .trait-row { display: table; width: 100%; font-size: 10px; }
        .trait-name { display: table-cell; padding: 2px 0; color: #475569; }
        .trait-value { display: table-cell; text-align: right; padding: 2px 0; font-weight: bold; color: #1e293b; }

        /* Comments */
        .comments { margin-bottom: 12px; }
        .comment-box { border: 1px solid #e2e8f0; border-radius: 4px; padding: 8px 10px; margin-bottom: 8px; }
        .comment-label { font-size: 9px; text-transform: uppercase; color: #4338ca; font-weight: bold; margin-bottom: 3px; }
        .comment-text { font-size: 10px; color: #334155; font-style: italic; min-height: 20px; }

        /* Mid-term note */
        .midterm-note { margin-bottom: 12px; padding: 8px 10px; background: #fffbeb; border: 1px solid #fde68a; border-radius: 4px; font-size: 9px; color: #92400e; }

        /* Session method note */
        .session-note { margin-bottom: 12px; padding: 6px 10px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 4px; font-size: 9px; color: #166534; }

        /* Footer */
        .footer { margin-top: 15px; padding-top: 10px; border-top: 1px solid #e2e8f0; display: table; width: 100%; font-size: 9px; color: #94a3b8; }
        .footer .left { display: table-cell; text-align: left; }
        .footer .right { display: table-cell; text-align: right; }

        /* Watermark */
        .watermark { position: fixed; top: 40%; left: 20%; font-size: 60px; color: rgba(67, 56, 202, 0.04); transform: rotate(-30deg); z-index: -1; font-weight: bold; text-transform: uppercase; letter-spacing: 10px; }
    </style>
</head>
<body>
    <div class="watermark">{{ $school->name ?? 'REPORT CARD' }}</div>

    <div class="page">
        {{-- School Header --}}
        <div class="school-header">
            @if ($school->logo_url)
                <img src="{{ $school->logoMediumUrl() }}" class="school-logo" alt="">
            @endif
            <h1>{{ $school->name ?? 'School Name' }}</h1>
            <p>{{ $school->address ?? '' }}</p>
            @if ($school->phone || $school->email)
                <p>{{ $school->phone ?? '' }} {{ $school->email ? '| ' . $school->email : '' }}</p>
            @endif
            <div class="report-title">{{ $reportTitle }}</div>
        </div>

        {{-- Student Information --}}
        <div class="student-info">
            <div class="row">
                <div class="cell">
                    <div class="label">Student Name</div>
                    <div class="value">{{ $report->student->name ?? '—' }}</div>
                </div>
                <div class="cell">
                    <div class="label">Admission No.</div>
                    <div class="value">{{ $report->student->studentProfile?->admission_number ?? '—' }}</div>
                </div>
                <div class="cell">
                    <div class="label">Class</div>
                    <div class="value">{{ $report->class->name ?? '—' }}</div>
                </div>
                <div class="cell">
                    <div class="label">{{ $isSession ? 'Academic Session' : 'Session / Term' }}</div>
                    <div class="value">{{ $periodLabel }}</div>
                </div>
            </div>
        </div>

        {{-- Summary Bar --}}
        @php
            $overallGrade = $gradingScale
                ? $gradingScale->items->first(fn ($item) => $item->min_score <= ($report->average_weighted_score ?? 0) && $item->max_score >= ($report->average_weighted_score ?? 0))
                : null;
        @endphp
        <div class="summary-bar">
            <div class="summary-item">
                <div class="big">{{ number_format($report->average_weighted_score ?? 0, 1) }}%</div>
                <div class="small">{{ $isSession ? 'Session Average' : ($isMidterm ? 'Mid-Term Average' : 'Average Score') }}</div>
            </div>
            @if ($overallGrade)
                <div class="summary-item">
                    <div class="big">{{ $overallGrade->grade }}</div>
                    <div class="small">{{ $overallGrade->label }}</div>
                </div>
            @endif
            @if ($config?->show_position)
            <div class="summary-item">
                <div class="big">{{ $report->position ?? '—' }}<span style="font-size:10px;">/ {{ $report->out_of ?? '—' }}</span></div>
                <div class="small">Class Position</div>
            </div>
            @endif
            <div class="summary-item">
                <div class="big">{{ is_array($report->subject_scores_snapshot) ? count($report->subject_scores_snapshot) : 0 }}</div>
                <div class="small">Subjects Taken</div>
            </div>
        </div>

        {{-- ============================================================ --}}
        {{-- SESSION REPORT: Per-term breakdown table                      --}}
        {{-- ============================================================ --}}
        @if ($isSession && is_array($report->subject_scores_snapshot) && count($report->subject_scores_snapshot) > 0)
            <table class="scores">
                <thead>
                    <tr>
                        <th style="text-align:left;">Subject</th>
                        @if ($showTermBreakdown)
                            @foreach ($sessionTermNames as $termName)
                                <th>{{ $termName }} (%)</th>
                            @endforeach
                        @endif
                        <th>Session Avg (%)</th>
                        <th>Grade</th>
                        @if ($config?->show_position)
                            <th>Position</th>
                        @endif
                        <th>Class Avg</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($report->subject_scores_snapshot as $snapshot)
                        @php
                            $termScoresMap = collect($snapshot['term_scores'] ?? [])->keyBy('term_id');
                        @endphp
                        <tr>
                            <td>{{ $snapshot['subject_name'] ?? '—' }}</td>
                            @if ($showTermBreakdown)
                                @foreach ($sessionTermNames as $termId => $termName)
                                    <td>{{ isset($termScoresMap[$termId]) ? number_format($termScoresMap[$termId]['score'], 1) : '—' }}</td>
                                @endforeach
                            @endif
                            <td class="total-col">{{ isset($snapshot['session_total']) ? number_format($snapshot['session_total'], 1) : '—' }}</td>
                            <td class="grade-col">{{ $snapshot['grade'] ?? '—' }}</td>
                            @if ($config?->show_position)
                                <td>{{ $snapshot['position'] ?? '—' }}</td>
                            @endif
                            <td>{{ isset($snapshot['class_average']) ? number_format($snapshot['class_average'], 1) : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @php
                $methodLabels = [
                    'average_of_terms' => 'Average of all term scores',
                    'weighted_average' => 'Weighted average of term scores',
                    'best_two_of_three' => 'Best two of three term scores',
                ];
                $sessionMethod = $config?->session_calculation_method ?? 'average_of_terms';
            @endphp
            <div class="session-note">
                <strong>Calculation Method:</strong> {{ $methodLabels[$sessionMethod] ?? $sessionMethod }}
            </div>

        {{-- ============================================================ --}}
        {{-- MID-TERM & FULL-TERM: Component-based scores table            --}}
        {{-- ============================================================ --}}
        @elseif (is_array($report->subject_scores_snapshot) && count($report->subject_scores_snapshot) > 0)
            @php
                $compKeyFn = fn ($c) => $c['component_id'] ?? $c['short_name'] ?? $c['name'] ?? '';
                $masterComponents = collect($report->subject_scores_snapshot)
                    ->flatMap(fn ($s) => $s['components'] ?? [])
                    ->unique($compKeyFn)
                    ->sortBy($compKeyFn)
                    ->values();
            @endphp
            <table class="scores">
                <thead>
                    <tr>
                        <th style="text-align:left;">Subject</th>
                        @foreach ($masterComponents as $comp)
                            <th>{{ $comp['short_name'] ?? $comp['name'] ?? '' }}{{ isset($comp['max_score']) ? ' ('.$comp['max_score'].')' : '' }}</th>
                        @endforeach
                        <th>{{ $isMidterm ? 'Mid-Term Total (%)' : 'Total (%)' }}</th>
                        <th>Grade</th>
                        @if ($config?->show_position)
                            <th>Position</th>
                        @endif
                        <th>Class Avg</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($report->subject_scores_snapshot as $snapshot)
                        @php
                            $compLookup = collect($snapshot['components'] ?? [])->keyBy($compKeyFn);
                        @endphp
                        <tr>
                            <td>{{ $snapshot['subject_name'] ?? '—' }}</td>
                            @foreach ($masterComponents as $comp)
                                @php $ck = $compKeyFn($comp); @endphp
                                <td>{{ $compLookup[$ck]['score'] ?? '—' }}</td>
                            @endforeach
                            <td class="total-col">{{ isset($snapshot['weighted_total']) ? number_format($snapshot['weighted_total'], 1) : '—' }}</td>
                            <td class="grade-col">{{ $snapshot['grade'] ?? '—' }}</td>
                            @if ($config?->show_position)
                                <td>{{ $snapshot['position'] ?? '—' }}</td>
                            @endif
                            <td>{{ isset($snapshot['class_average']) ? number_format($snapshot['class_average'], 1) : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        {{-- Mid-term disclaimer note --}}
        @if ($isMidterm)
            <div class="midterm-note">
                <strong>Note:</strong> This is a mid-term progress report. Only mid-term assessment components are included.
                Final term grades may differ as additional assessments are factored in.
            </div>
        @endif

        {{-- Grading Key --}}
        @if (isset($gradingScale) && $gradingScale && $gradingScale->items->isNotEmpty())
            <div class="grading-key">
                <strong>Grading Key:</strong>
                @foreach ($gradingScale->items as $item)
                    <strong>{{ $item->grade }}</strong> = {{ $item->label }} ({{ $item->min_score }}–{{ $item->max_score }}%){{ !$loop->last ? ' | ' : '' }}
                @endforeach
            </div>
        @endif

        {{-- Psychomotor & Affective (not shown on session reports — data not stored) --}}
        @if (!$isSession && isset($config) && $config && (($config->psychomotor_traits ?? []) || ($config->affective_traits ?? [])))
            <div class="traits-section">
                @if (!empty($config->psychomotor_traits))
                    <div class="traits-col">
                        <h4>Psychomotor Skills</h4>
                        @foreach ($config->psychomotor_traits as $trait)
                            <div class="trait-row">
                                <span class="trait-name">{{ $trait }}</span>
                                <span class="trait-value">{{ $report->psychomotor_ratings[$trait] ?? '—' }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
                @if (!empty($config->affective_traits))
                    <div class="traits-col">
                        <h4>Affective Domain</h4>
                        @foreach ($config->affective_traits as $trait)
                            <div class="trait-row">
                                <span class="trait-name">{{ $trait }}</span>
                                <span class="trait-value">{{ $report->affective_ratings[$trait] ?? '—' }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        {{-- Comments (not shown on session reports) --}}
        @if (!$isSession)
            <div class="comments">
                <div class="comment-box">
                    <div class="comment-label">Class Teacher's Comment</div>
                    <div class="comment-text">{{ $report->teacher_comment ?? '—' }}</div>
                </div>
                <div class="comment-box">
                    <div class="comment-label">{{ $config?->principal_title ?? 'Principal' }}'s Comment</div>
                    <div class="comment-text">{{ $report->principal_comment ?? '—' }}</div>
                </div>
            </div>
        @endif

        {{-- Signatures --}}
        @if (($config?->principal_signature_url) || ($config?->school_stamp_url))
            <div style="margin-top: 20px; display: flex; justify-content: space-between; align-items: flex-end;">
                @if ($config->principal_signature_url)
                    <div style="text-align: center;">
                        <img src="{{ $config->principal_signature_url }}" alt="Signature" style="height: 50px; max-width: 150px; object-fit: contain;">
                        <div style="border-top: 1px solid #333; padding-top: 4px; font-size: 10px; font-weight: bold;">{{ $config->principal_title ?? 'Principal' }}</div>
                    </div>
                @endif
                @if ($config->school_stamp_url)
                    <div style="text-align: center;">
                        <img src="{{ $config->school_stamp_url }}" alt="School Stamp" style="height: 60px; max-width: 150px; object-fit: contain;">
                        <div style="font-size: 9px; color: #666;">{{ __('School Stamp') }}</div>
                    </div>
                @endif
            </div>
        @endif

        {{-- Footer --}}
        <div class="footer">
            <span class="left">Generated on {{ now()->format('F j, Y') }}</span>
            <span class="right">{{ $school->name ?? '' }}</span>
        </div>
    </div>
</body>
</html>
