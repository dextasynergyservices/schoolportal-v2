<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Result;
use App\Models\SchoolClass;
use App\Models\Term;
use App\Services\FileUploadService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResultController extends Controller
{
    public function index(Request $request): View
    {
        $query = Result::with([
            'student:id,name,username',
            'class:id,name',
            'session:id,name',
            'term:id,name',
            'uploader:id,name',
        ]);

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->input('class_id'));
        }

        if ($request->filled('term_id')) {
            $query->where('term_id', $request->input('term_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('student', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('username', 'like', "%{$search}%"));
        }

        $results = $query->orderByDesc('created_at')->paginate(10)->withQueryString();
        $classes = SchoolClass::where('is_active', true)->orderBy('name')->get();
        $terms = Term::orderByDesc('id')->take(6)->get();

        return view('admin.results.index', compact('results', 'classes', 'terms'));
    }

    public function create(): View
    {
        $school = app('current.school');
        $currentSession = $school->currentSession();
        $currentTerm = $school->currentTerm();

        return view('admin.results.create', compact('currentSession', 'currentTerm'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:users,id'],
            'class_id' => ['required', 'exists:classes,id'],
            'session_id' => ['required', 'exists:academic_sessions,id'],
            'term_id' => ['required', 'exists:terms,id'],
            'result_file' => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'notes' => ['nullable', 'string'],
        ]);

        $school = app('current.school');
        $upload = app(FileUploadService::class)->uploadResult($request->file('result_file'), $school->id);

        $result = Result::create([
            'student_id' => $validated['student_id'],
            'class_id' => $validated['class_id'],
            'session_id' => $validated['session_id'],
            'term_id' => $validated['term_id'],
            'notes' => $validated['notes'] ?? null,
            'file_url' => $upload['url'],
            'file_public_id' => $upload['public_id'],
            'uploaded_by' => auth()->id(),
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'status' => 'approved',
        ]);

        app(NotificationService::class)->notifyResultUploaded($result);

        return redirect()->route('admin.results.index')
            ->with('success', __('Result uploaded.'));
    }

    public function show(Result $result): View
    {
        $result->load(['student', 'class', 'session', 'term', 'uploader', 'approver']);

        return view('admin.results.show', compact('result'));
    }

    public function destroy(Result $result): RedirectResponse
    {
        if ($result->file_public_id) {
            app(FileUploadService::class)->delete($result->file_public_id);
        }

        $result->delete();

        return redirect()->route('admin.results.index')
            ->with('success', __('Result deleted.'));
    }
}
