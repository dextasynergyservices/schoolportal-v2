<?php

declare(strict_types=1);

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Assignment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChildAssignmentController extends Controller
{
    public function index(Request $request, User $child): View
    {
        $parent = auth()->user();
        $this->authorizeChild($parent, $child);

        $school = app('current.school');
        $profile = $child->studentProfile;
        $classId = $profile?->class_id;

        $currentSession = $school->currentSession();
        $currentTerm = $school->currentTerm();

        // Build query scoped to the child's class + approved only
        $query = Assignment::where('status', 'approved')
            ->with(['class:id,name', 'session:id,name', 'term:id,name']);

        if ($classId) {
            $query->where('class_id', $classId);
        } else {
            $query->whereNull('id');
        }

        $selectedSessionId = $request->input('session_id', $currentSession?->id);
        $selectedTermId = $request->input('term_id', $currentTerm?->id);

        if ($selectedSessionId) {
            $query->where('session_id', $selectedSessionId);
        }

        if ($selectedTermId) {
            $query->where('term_id', $selectedTermId);
        }

        $selectedWeek = $request->input('week');
        if ($selectedWeek) {
            $query->where('week_number', $selectedWeek);
        }

        $assignments = $query->orderByDesc('week_number')->paginate(10)->withQueryString();

        $sessions = $school->academicSessions()->orderByDesc('start_date')->get();
        $terms = collect();
        if ($selectedSessionId) {
            $terms = AcademicSession::find($selectedSessionId)?->terms ?? collect();
        }

        $weeksPerTerm = $school->setting('academic.weeks_per_term', 12);

        $child->load('studentProfile.class:id,name');

        return view('parent.children.assignments', compact(
            'child',
            'assignments',
            'sessions',
            'terms',
            'selectedSessionId',
            'selectedTermId',
            'selectedWeek',
            'weeksPerTerm',
        ));
    }

    private function authorizeChild(User $parent, User $child): void
    {
        $isLinked = $parent->children()
            ->where('student_id', $child->id)
            ->exists();

        abort_unless($isLinked && $child->school_id === $parent->school_id, 403);
    }
}
