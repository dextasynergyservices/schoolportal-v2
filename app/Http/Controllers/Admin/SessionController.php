<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Term;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SessionController extends Controller
{
    public function index(): View
    {
        $sessions = AcademicSession::with(['terms' => fn ($q) => $q->orderBy('term_number')])
            ->withCount('terms')
            ->orderByDesc('start_date')
            ->paginate(10);

        return view('admin.sessions.index', compact('sessions'));
    }

    public function create(): View
    {
        return view('admin.sessions.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
        ]);

        $session = AcademicSession::create($validated);

        // Auto-create 3 terms
        $termNames = ['First Term', 'Second Term', 'Third Term'];
        foreach ($termNames as $index => $termName) {
            Term::create([
                'school_id' => $session->school_id,
                'session_id' => $session->id,
                'term_number' => $index + 1,
                'name' => $termName,
            ]);
        }

        return redirect()->route('admin.sessions.index')
            ->with('success', __('Academic session created with 3 terms.'));
    }

    public function edit(AcademicSession $session): View
    {
        $session->load('terms');

        return view('admin.sessions.edit', compact('session'));
    }

    public function update(Request $request, AcademicSession $session): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
        ]);

        $session->update($validated);

        return redirect()->route('admin.sessions.index')
            ->with('success', __('Academic session updated.'));
    }

    public function activate(AcademicSession $session): RedirectResponse
    {
        // Deactivate all other sessions for this school
        AcademicSession::where('is_current', true)->update([
            'is_current' => false,
            'status' => 'completed',
        ]);

        $session->update([
            'is_current' => true,
            'status' => 'active',
        ]);

        return redirect()->route('admin.sessions.index')
            ->with('success', __('Session ":name" is now active.', ['name' => $session->name]));
    }

    public function activateTerm(Term $term): RedirectResponse
    {
        // Deactivate all other terms for this school
        Term::where('is_current', true)->update([
            'is_current' => false,
            'status' => 'completed',
        ]);

        $term->update([
            'is_current' => true,
            'status' => 'active',
        ]);

        return redirect()->route('admin.sessions.index')
            ->with('success', __('":name" is now the active term.', ['name' => $term->name]));
    }

    public function updateTerm(Request $request, Term $term): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
        ]);

        $term->update($validated);

        return redirect()->route('admin.sessions.index')
            ->with('success', __('Term updated.'));
    }

    public function destroy(AcademicSession $session): RedirectResponse
    {
        if ($session->is_current) {
            return redirect()->route('admin.sessions.index')
                ->with('error', __('Cannot delete the active session.'));
        }

        $session->delete();

        return redirect()->route('admin.sessions.index')
            ->with('success', __('Academic session deleted.'));
    }
}
