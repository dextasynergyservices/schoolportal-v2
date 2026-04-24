<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\SchoolClass;
use App\Models\StudentProfile;
use App\Models\StudentPromotion;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PromotionController extends Controller
{
    public function index(): View
    {
        $classes = SchoolClass::with(['level:id,name'])
            ->withCount('students')
            ->where('is_active', true)
            ->orderBy('level_id')
            ->orderBy('sort_order')
            ->get();

        $sessions = AcademicSession::orderByDesc('start_date')->take(5)->get();

        $recentPromotions = StudentPromotion::with([
            'student:id,name',
            'fromClass:id,name',
            'toClass:id,name',
            'promoter:id,name',
        ])
            ->orderByDesc('promoted_at')
            ->paginate(10);

        // All active students for single-student promotion search
        $allStudents = User::where('role', 'student')
            ->where('is_active', true)
            ->with('studentProfile:user_id,class_id')
            ->get()
            ->map(function ($s) use ($classes) {
                $classId = $s->studentProfile?->class_id;
                $class = $classes->firstWhere('id', $classId);

                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'username' => $s->username,
                    'class_id' => $classId,
                    'class_name' => $class?->name ?? '—',
                ];
            });

        return view('admin.promotions.index', compact('classes', 'sessions', 'recentPromotions', 'allStudents'));
    }

    public function preview(Request $request): View
    {
        $validated = $request->validate([
            'from_class_id' => ['required', 'exists:classes,id'],
            'to_class_id' => ['required', 'exists:classes,id', 'different:from_class_id'],
            'to_session_id' => ['required', 'exists:academic_sessions,id'],
        ]);

        $fromClass = SchoolClass::findOrFail($validated['from_class_id']);
        $toClass = SchoolClass::findOrFail($validated['to_class_id']);
        $toSession = AcademicSession::findOrFail($validated['to_session_id']);

        $students = StudentProfile::where('class_id', $fromClass->id)
            ->with('user:id,name,username,gender')
            ->get();

        $currentSession = app('current.school')->currentSession();

        return view('admin.promotions.preview', compact(
            'fromClass',
            'toClass',
            'toSession',
            'students',
            'currentSession',
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'from_class_id' => ['required', 'exists:classes,id'],
            'to_class_id' => ['required', 'exists:classes,id'],
            'from_session_id' => ['required', 'exists:academic_sessions,id'],
            'to_session_id' => ['required', 'exists:academic_sessions,id'],
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['exists:users,id'],
        ]);

        $toClass = SchoolClass::findOrFail($validated['to_class_id']);

        DB::transaction(function () use ($validated, $toClass) {
            foreach ($validated['student_ids'] as $studentId) {
                // Log the promotion
                StudentPromotion::create([
                    'student_id' => $studentId,
                    'from_class_id' => $validated['from_class_id'],
                    'to_class_id' => $validated['to_class_id'],
                    'from_session_id' => $validated['from_session_id'],
                    'to_session_id' => $validated['to_session_id'],
                    'promoted_by' => auth()->id(),
                ]);

                // Update student profile to new class and level
                StudentProfile::where('user_id', $studentId)->update([
                    'class_id' => $validated['to_class_id'],
                ]);

                // Update user's level_id if class level changed
                if ($toClass->level_id) {
                    User::where('id', $studentId)->update([
                        'level_id' => $toClass->level_id,
                    ]);
                }
            }
        });

        return redirect()->route('admin.promotions.index')
            ->with('success', __(':count student(s) promoted successfully.', ['count' => count($validated['student_ids'])]));
    }
}
