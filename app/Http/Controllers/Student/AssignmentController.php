<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Assignment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssignmentController extends Controller
{
    public function index(Request $request): View
    {
        $student = auth()->user();
        $school = app('current.school');
        $profile = $student->studentProfile;
        $classId = $profile?->class_id;

        $currentSession = $school->currentSession();
        $currentTerm = $school->currentTerm();

        // Build query scoped to the student's class + approved only
        $query = Assignment::where('status', 'approved')
            ->with(['class:id,name', 'session:id,name', 'term:id,name']);

        if ($classId) {
            $query->where('class_id', $classId);
        } else {
            // No class assigned — return empty
            $query->whereNull('id');
        }

        // Default to current session/term
        $selectedSessionId = $request->input('session_id', $currentSession?->id);
        $selectedTermId = $request->input('term_id', $currentTerm?->id);

        if ($selectedSessionId) {
            $query->where('session_id', $selectedSessionId);
        }

        if ($selectedTermId) {
            $query->where('term_id', $selectedTermId);
        }

        // Filter by week
        $selectedWeek = $request->input('week');
        if ($selectedWeek) {
            $query->where('week_number', $selectedWeek);
        }

        $assignments = $query->orderByDesc('week_number')->paginate(10)->withQueryString();

        // Get available sessions/terms for filters
        $sessions = $school->academicSessions()->orderByDesc('start_date')->get();
        $terms = collect();
        if ($selectedSessionId) {
            $terms = AcademicSession::find($selectedSessionId)?->terms ?? collect();
        }

        // Weeks per term from school settings
        $weeksPerTerm = $school->setting('academic.weeks_per_term', 12);

        return view('student.assignments.index', compact(
            'assignments',
            'sessions',
            'terms',
            'selectedSessionId',
            'selectedTermId',
            'selectedWeek',
            'weeksPerTerm',
        ));
    }
}
