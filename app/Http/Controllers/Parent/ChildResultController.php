<?php

declare(strict_types=1);

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Result;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChildResultController extends Controller
{
    public function index(Request $request, User $child): View
    {
        $parent = auth()->user();
        $this->authorizeChild($parent, $child);

        $school = app('current.school');

        // Available sessions for this child's results
        $sessions = AcademicSession::whereIn(
            'id',
            Result::where('student_id', $child->id)
                ->where('status', 'approved')
                ->distinct()
                ->pluck('session_id')
        )->orderByDesc('start_date')->get();

        // Determine selected session
        $currentSession = $school->currentSession();
        $selectedSessionId = $request->input('session_id', $currentSession?->id);

        // Get terms for the selected session
        $terms = collect();
        if ($selectedSessionId) {
            $terms = AcademicSession::find($selectedSessionId)?->terms ?? collect();
        }

        $selectedTermId = $request->input('term_id');

        // Fetch approved results for this child
        $query = Result::where('student_id', $child->id)
            ->where('status', 'approved')
            ->with(['session:id,name', 'term:id,name', 'class:id,name']);

        if ($selectedSessionId) {
            $query->where('session_id', $selectedSessionId);
        }

        if ($selectedTermId) {
            $query->where('term_id', $selectedTermId);
        }

        $results = $query->latest()->paginate(10)->withQueryString();

        $child->load('studentProfile.class:id,name');

        return view('parent.children.results', compact(
            'child',
            'results',
            'sessions',
            'terms',
            'selectedSessionId',
            'selectedTermId',
        ));
    }

    public function show(User $child, Result $result): View
    {
        $parent = auth()->user();
        $this->authorizeChild($parent, $child);

        // Ensure the result belongs to this child and is approved
        abort_unless(
            $result->student_id === $child->id && $result->status === 'approved',
            403
        );

        $result->load(['session:id,name', 'term:id,name', 'class:id,name']);
        $child->load('studentProfile.class:id,name');

        return view('parent.children.result-show', compact('child', 'result'));
    }

    private function authorizeChild(User $parent, User $child): void
    {
        $isLinked = $parent->children()
            ->where('student_id', $child->id)
            ->exists();

        abort_unless($isLinked && $child->school_id === $parent->school_id, 403);
    }
}
