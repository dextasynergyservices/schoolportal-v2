<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Assignment;
use App\Models\SchoolClass;
use App\Models\Term;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssignmentController extends Controller
{
    public function index(Request $request): View
    {
        $query = Assignment::with(['class:id,name', 'session:id,name', 'term:id,name', 'uploader:id,name']);

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->input('class_id'));
        }

        if ($request->filled('term_id')) {
            $query->where('term_id', $request->input('term_id'));
        }

        $assignments = $query->orderByDesc('created_at')->paginate(20)->withQueryString();
        $classes = SchoolClass::where('is_active', true)->orderBy('name')->get();
        $terms = Term::where('is_current', true)->orWhere('status', 'active')->get();

        return view('admin.assignments.index', compact('assignments', 'classes', 'terms'));
    }

    public function create(): View
    {
        $school = app('current.school');
        $classes = SchoolClass::where('is_active', true)->orderBy('name')->get();
        $currentSession = $school->currentSession();
        $currentTerm = $school->currentTerm();

        return view('admin.assignments.create', compact('classes', 'currentSession', 'currentTerm'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'session_id' => ['required', 'exists:academic_sessions,id'],
            'term_id' => ['required', 'exists:terms,id'],
            'week_number' => ['required', 'integer', 'min:1', 'max:12'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
            'file_url' => ['nullable', 'url'],
            'file_public_id' => ['nullable', 'string'],
        ]);

        Assignment::create([
            ...$validated,
            'uploaded_by' => auth()->id(),
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'status' => 'approved',
        ]);

        return redirect()->route('admin.assignments.index')
            ->with('success', __('Assignment created.'));
    }

    public function edit(Assignment $assignment): View
    {
        $classes = SchoolClass::where('is_active', true)->orderBy('name')->get();
        $sessions = AcademicSession::orderByDesc('start_date')->get();
        $terms = Term::where('session_id', $assignment->session_id)->get();

        return view('admin.assignments.edit', compact('assignment', 'classes', 'sessions', 'terms'));
    }

    public function update(Request $request, Assignment $assignment): RedirectResponse
    {
        $validated = $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'week_number' => ['required', 'integer', 'min:1', 'max:12'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
        ]);

        $assignment->update($validated);

        return redirect()->route('admin.assignments.index')
            ->with('success', __('Assignment updated.'));
    }

    public function destroy(Assignment $assignment): RedirectResponse
    {
        $assignment->delete();

        return redirect()->route('admin.assignments.index')
            ->with('success', __('Assignment deleted.'));
    }
}
