<?php

declare(strict_types=1);

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
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

        $submissions = $query->latest('created_at')->paginate(20)->withQueryString();

        // Counts per status
        $counts = [
            'all' => TeacherAction::where('teacher_id', $teacher->id)->count(),
            'pending' => TeacherAction::where('teacher_id', $teacher->id)->where('status', 'pending')->count(),
            'approved' => TeacherAction::where('teacher_id', $teacher->id)->where('status', 'approved')->count(),
            'rejected' => TeacherAction::where('teacher_id', $teacher->id)->where('status', 'rejected')->count(),
        ];

        return view('teacher.submissions.index', compact('submissions', 'counts'));
    }
}
