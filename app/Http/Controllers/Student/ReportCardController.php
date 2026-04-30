<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\ExamAttempt;
use App\Models\GradingScale;
use App\Models\ReportCardConfig;
use App\Models\StudentTermReport;
use App\Models\Term;
use App\Services\ScoreAggregationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportCardController extends Controller
{
    /**
     * List all published report cards for the student.
     * Supports tabs: term-reports (default) and cbt-results.
     */
    public function index(Request $request): View
    {
        $student = auth()->user();
        $school = app('current.school');

        $activeTab = $request->input('tab', 'term-reports');

        // ── Term Reports data (always needed for tab count) ──────
        $sessions = AcademicSession::whereIn(
            'id',
            StudentTermReport::where('student_id', $student->id)
                ->where('status', 'published')
                ->distinct()
                ->pluck('session_id')
        )->orderByDesc('start_date')->get();

        $selectedSessionId = $request->input('session_id');

        $query = StudentTermReport::where('student_id', $student->id)
            ->where('status', 'published')
            ->with(['class:id,name', 'session:id,name', 'term:id,name,term_number']);

        if ($selectedSessionId) {
            $query->where('session_id', $selectedSessionId);
        }

        $reports = $query
            ->orderByDesc(
                AcademicSession::select('start_date')
                    ->whereColumn('academic_sessions.id', 'student_term_reports.session_id')
                    ->limit(1)
            )
            ->orderBy(
                Term::select('term_number')
                    ->whereColumn('terms.id', 'student_term_reports.term_id')
                    ->limit(1)
            )
            ->orderByRaw("FIELD(report_type, 'midterm', 'full_term', 'session')")
            ->get();

        // ── CBT Results data (loaded when on cbt-results tab) ────
        $attempts = null;
        $selectedCategory = 'all';
        $grades = collect();
        $cbtResultsCount = ExamAttempt::where('student_id', $student->id)
            ->whereIn('status', ['submitted', 'timed_out', 'grading', 'graded'])
            ->count();

        if ($activeTab === 'cbt-results') {
            $selectedCategory = $request->input('category', 'all');

            $cbtQuery = ExamAttempt::where('student_id', $student->id)
                ->whereIn('status', ['submitted', 'timed_out', 'grading', 'graded'])
                ->with(['exam.subject:id,name,short_name', 'exam.class:id,name'])
                ->orderByDesc('submitted_at');

            if ($selectedCategory !== 'all') {
                $cbtQuery->whereHas('exam', fn ($q) => $q->where('category', $selectedCategory));
            }

            $attempts = $cbtQuery->paginate(15)->withQueryString();

            $scoreService = app(ScoreAggregationService::class);
            $grades = $attempts->getCollection()->mapWithKeys(fn (ExamAttempt $a) => [
                $a->id => $a->percentage !== null
                    ? $scoreService->getGrade($student->school_id, (float) $a->percentage)
                    : null,
            ]);
        }

        $showPosition = ReportCardConfig::where('school_id', $school->id)->value('show_position') ?? true;

        return view('student.report-cards.index', compact(
            'reports', 'sessions', 'selectedSessionId', 'activeTab',
            'attempts', 'selectedCategory', 'grades', 'cbtResultsCount',
            'showPosition',
        ));
    }

    /**
     * View a single report card.
     */
    public function show(StudentTermReport $report): View
    {
        $student = auth()->user();
        $school = app('current.school');

        abort_unless(
            $report->student_id === $student->id && $report->isPublished(),
            403
        );

        $report->load(['student.studentProfile', 'class', 'session', 'term', 'teacher']);

        $config = $school->reportCardConfig;
        $gradingScale = GradingScale::where('school_id', $school->id)
            ->where('is_default', true)
            ->where('is_active', true)
            ->with('items')
            ->first();

        return view('student.report-cards.show', compact('report', 'school', 'config', 'gradingScale'));
    }

    /**
     * Download a report card as PDF.
     */
    public function download(StudentTermReport $report)
    {
        $student = auth()->user();
        $school = app('current.school');

        abort_unless(
            $report->student_id === $student->id && $report->isPublished(),
            403
        );

        $report->load(['student.studentProfile', 'class', 'session', 'term', 'teacher', 'approvedByUser']);

        $config = $school->reportCardConfig;
        $gradingScale = GradingScale::where('school_id', $school->id)
            ->where('is_default', true)
            ->where('is_active', true)
            ->with('items')
            ->first();

        $pdf = Pdf::loadView('admin.scores.report-pdf', compact(
            'report', 'school', 'config', 'gradingScale'
        ));

        $studentName = str_replace(' ', '_', $report->student->name ?? 'Student');
        $typeSuffix = match ($report->report_type) {
            'midterm' => 'Midterm',
            'session' => 'Session',
            default => str_replace(' ', '_', $report->term->name ?? 'Term'),
        };
        $termPrefix = $report->term ? str_replace(' ', '_', $report->term->name).'_' : '';
        $filename = $report->report_type === 'session'
            ? "Session_Report_{$studentName}.pdf"
            : "Report_Card_{$studentName}_{$termPrefix}{$typeSuffix}.pdf";

        return $pdf->download($filename);
    }

    /**
     * CBT Results overview — redirect to Report Cards with CBT tab active.
     */
    public function cbtResults(Request $request): RedirectResponse
    {
        $params = ['tab' => 'cbt-results'];
        if ($request->has('category')) {
            $params['category'] = $request->input('category');
        }

        return redirect()->route('student.report-cards.index', $params);
    }

    /**
     * Session summary — all terms for a given session.
     */
    public function sessionSummary(AcademicSession $session): View
    {
        $student = auth()->user();

        $reports = StudentTermReport::where('student_id', $student->id)
            ->where('session_id', $session->id)
            ->where('status', 'published')
            ->with(['class:id,name', 'term:id,name,term_number'])
            ->orderBy('term_id')
            ->get();

        abort_if($reports->isEmpty(), 404);

        return view('student.report-cards.session-summary', compact('reports', 'session'));
    }
}
