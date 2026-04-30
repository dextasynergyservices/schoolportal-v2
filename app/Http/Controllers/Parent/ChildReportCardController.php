<?php

declare(strict_types=1);

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\GradingScale;
use App\Models\ReportCardConfig;
use App\Models\StudentTermReport;
use App\Models\Term;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChildReportCardController extends Controller
{
    /**
     * List published report cards for a specific child.
     */
    public function index(Request $request, User $child): View
    {
        $parent = auth()->user();
        $this->authorizeChild($parent, $child);

        $sessions = AcademicSession::whereIn(
            'id',
            StudentTermReport::where('student_id', $child->id)
                ->where('status', 'published')
                ->distinct()
                ->pluck('session_id')
        )->orderByDesc('start_date')->get();

        $selectedSessionId = $request->input('session_id');

        $query = StudentTermReport::where('student_id', $child->id)
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

        $child->load('studentProfile.class:id,name');

        $showPosition = ReportCardConfig::where('school_id', $child->school_id)->value('show_position') ?? true;

        return view('parent.children.report-cards.index', compact(
            'child', 'reports', 'sessions', 'selectedSessionId', 'showPosition'
        ));
    }

    /**
     * View a single report card for a child.
     */
    public function show(User $child, StudentTermReport $report): View
    {
        $parent = auth()->user();
        $this->authorizeChild($parent, $child);

        abort_unless(
            $report->student_id === $child->id && $report->isPublished(),
            403
        );

        $school = app('current.school');
        $report->load(['student.studentProfile', 'class', 'session', 'term', 'teacher']);

        $config = $school->reportCardConfig;
        $gradingScale = GradingScale::where('school_id', $school->id)
            ->where('is_default', true)
            ->where('is_active', true)
            ->with('items')
            ->first();

        $child->load('studentProfile.class:id,name');

        return view('parent.children.report-cards.show', compact(
            'child', 'report', 'school', 'config', 'gradingScale'
        ));
    }

    /**
     * Download a report card PDF for a child.
     */
    public function download(User $child, StudentTermReport $report)
    {
        $parent = auth()->user();
        $this->authorizeChild($parent, $child);

        abort_unless(
            $report->student_id === $child->id && $report->isPublished(),
            403
        );

        $school = app('current.school');
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

    private function authorizeChild(User $parent, User $child): void
    {
        $isLinked = $parent->children()
            ->where('student_id', $child->id)
            ->exists();

        abort_unless($isLinked && $child->school_id === $parent->school_id, 403);
    }
}
