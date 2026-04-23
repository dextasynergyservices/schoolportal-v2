<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\QuizAttempt;
use App\Models\Result;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __invoke(): View
    {
        $student = auth()->user();
        $profile = $student->studentProfile;

        $class = $profile?->class()
            ->with(['teacher:id,name', 'level:id,name'])
            ->first();

        $enrolledSession = $profile?->enrolledSession;

        // Quick academic stats
        $resultsCount = Result::where('student_id', $student->id)
            ->where('status', 'approved')
            ->count();

        $quizzesTaken = QuizAttempt::where('student_id', $student->id)
            ->whereIn('status', ['submitted', 'timed_out'])
            ->count();

        return view('student.profile', compact(
            'student',
            'profile',
            'class',
            'enrolledSession',
            'resultsCount',
            'quizzesTaken',
        ));
    }
}
