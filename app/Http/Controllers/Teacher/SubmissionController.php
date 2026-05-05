<?php

declare(strict_types=1);

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\TeacherAction;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubmissionController extends Controller
{
    public function index(Request $request): View
    {
        $teacher = auth()->user();

        $query = TeacherAction::where('teacher_id', $teacher->id)
            ->with('reviewer:id,name');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('type')) {
            $query->where('entity_type', $request->input('type'));
        }

        $submissions = $query->latest('created_at')->paginate(15)->withQueryString();

        // Pre-load exam entities to avoid N+1 queries in the view.
        // The view needs each exam's `category` for the badge label and its ID for route generation.
        $examIds = $submissions->getCollection()
            ->where('entity_type', 'exam')
            ->pluck('entity_id')
            ->unique()
            ->values()
            ->toArray();

        $examEntities = $examIds
            ? Exam::whereIn('id', $examIds)->get(['id', 'category', 'slug'])->keyBy('id')
            : collect();

        // Counts per status
        $baseQuery = TeacherAction::where('teacher_id', $teacher->id);
        $counts = [
            'all' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
            'approved' => (clone $baseQuery)->where('status', 'approved')->count(),
            'rejected' => (clone $baseQuery)->where('status', 'rejected')->count(),
        ];

        return view('teacher.submissions.index', compact('submissions', 'counts', 'examEntities'));
    }
}
