<?php

declare(strict_types=1);

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\View\View;

class ChildQuizResultController extends Controller
{
    public function index(User $child): View
    {
        $parent = auth()->user();

        // Verify parent-child link
        if (! $parent->children()->where('student_id', $child->id)->exists()) {
            abort(403);
        }

        $attempts = QuizAttempt::with(['quiz:id,title,class_id,passing_score', 'quiz.class:id,name'])
            ->where('student_id', $child->id)
            ->where('status', '!=', 'in_progress')
            ->orderByDesc('submitted_at')
            ->get();

        return view('parent.quizzes.results', compact('child', 'attempts'));
    }
}
