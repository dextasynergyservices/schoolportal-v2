<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Result;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResultController extends Controller
{
    public function index(Request $request): View
    {
        $student = auth()->user();
        $school = app('current.school');

        // Available sessions for this student's results
        $sessions = AcademicSession::whereIn(
            'id',
            Result::where('student_id', $student->id)
                ->where('status', 'approved')
                ->distinct()
                ->pluck('session_id')
        )->orderByDesc('start_date')->get();

        // Determine selected session (default to current)
        $currentSession = $school->currentSession();
        $selectedSessionId = $request->input('session_id', $currentSession?->id);

        // Get terms for the selected session
        $terms = collect();
        if ($selectedSessionId) {
            $terms = AcademicSession::find($selectedSessionId)?->terms ?? collect();
        }

        // Determine selected term
        $currentTerm = $school->currentTerm();
        $selectedTermId = $request->input('term_id');

        // Fetch results
        $query = Result::where('student_id', $student->id)
            ->where('status', 'approved')
            ->with(['session:id,name', 'term:id,name', 'class:id,name']);

        if ($selectedSessionId) {
            $query->where('session_id', $selectedSessionId);
        }

        if ($selectedTermId) {
            $query->where('term_id', $selectedTermId);
        }

        $results = $query->latest()->paginate(20)->withQueryString();

        return view('student.results.index', compact(
            'results',
            'sessions',
            'terms',
            'selectedSessionId',
            'selectedTermId',
        ));
    }

    public function show(Result $result): View
    {
        $student = auth()->user();

        // Ensure the result belongs to this student and is approved
        abort_unless(
            $result->student_id === $student->id && $result->status === 'approved',
            403
        );

        $result->load(['session:id,name', 'term:id,name', 'class:id,name']);

        return view('student.results.show', compact('result'));
    }
}
