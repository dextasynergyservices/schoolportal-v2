<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class QuizController extends Controller
{
    public function index(): View
    {
        $student = auth()->user();
        $classId = $student->studentProfile?->class_id;

        $quizzes = Quiz::with(['class:id,name', 'attempts' => fn ($q) => $q->where('student_id', $student->id)])
            ->published()
            ->forClass($classId)
            ->orderByDesc('published_at')
            ->get();

        $available = $quizzes->filter(fn (Quiz $q) => $q->canStudentAttempt($student->id));
        $completed = $quizzes->filter(fn (Quiz $q) => ! $q->canStudentAttempt($student->id) && $q->attemptsForStudent($student->id) > 0);

        return view('student.quizzes.index', compact('available', 'completed'));
    }

    public function start(Quiz $quiz): RedirectResponse|View
    {
        $student = auth()->user();
        $classId = $student->studentProfile?->class_id;

        if ($quiz->class_id !== $classId || ! $quiz->canStudentAttempt($student->id)) {
            abort(403, 'You cannot take this quiz.');
        }

        // Check for an in-progress attempt
        $existingAttempt = $quiz->attempts()
            ->where('student_id', $student->id)
            ->where('status', 'in_progress')
            ->first();

        if ($existingAttempt) {
            return redirect()->route('student.quizzes.take', $existingAttempt);
        }

        $attemptNumber = $quiz->attemptsForStudent($student->id) + 1;

        $attempt = QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'student_id' => $student->id,
            'school_id' => $student->school_id,
            'attempt_number' => $attemptNumber,
            'started_at' => now(),
            'status' => 'in_progress',
        ]);

        return redirect()->route('student.quizzes.take', $attempt);
    }

    public function take(QuizAttempt $attempt): View|RedirectResponse
    {
        $student = auth()->user();

        if ($attempt->student_id !== $student->id) {
            abort(403);
        }

        if ($attempt->status !== 'in_progress') {
            return redirect()->route('student.quizzes.results', $attempt);
        }

        $quiz = $attempt->quiz;
        $quiz->load('questions');

        $questions = $quiz->shuffle_questions
            ? $quiz->questions->shuffle()
            : $quiz->questions;

        // Get already answered questions
        $answers = QuizAnswer::where('attempt_id', $attempt->id)
            ->pluck('selected_answer', 'question_id')
            ->toArray();

        // Calculate remaining time
        $remainingSeconds = null;
        if ($quiz->time_limit_minutes) {
            $elapsed = (int) $attempt->started_at->diffInSeconds(now());
            $totalSeconds = $quiz->time_limit_minutes * 60;
            $remainingSeconds = max(0, $totalSeconds - $elapsed);

            if ($remainingSeconds <= 0) {
                $this->submitAttempt($attempt, 'timed_out');

                return redirect()->route('student.quizzes.results', $attempt);
            }
        }

        return view('student.quizzes.take', compact('quiz', 'attempt', 'questions', 'answers', 'remainingSeconds'));
    }

    public function saveAnswer(Request $request, QuizAttempt $attempt): RedirectResponse
    {
        $student = auth()->user();

        if ($attempt->student_id !== $student->id || $attempt->status !== 'in_progress') {
            abort(403);
        }

        $validated = $request->validate([
            'question_id' => ['required', 'exists:quiz_questions,id'],
            'selected_answer' => ['nullable', 'string', 'max:500'],
        ]);

        QuizAnswer::updateOrCreate(
            [
                'attempt_id' => $attempt->id,
                'question_id' => $validated['question_id'],
            ],
            [
                'school_id' => $student->school_id,
                'selected_answer' => $validated['selected_answer'],
                'answered_at' => now(),
            ]
        );

        return redirect()->back();
    }

    public function submit(QuizAttempt $attempt): RedirectResponse
    {
        $student = auth()->user();

        if ($attempt->student_id !== $student->id || $attempt->status !== 'in_progress') {
            abort(403);
        }

        $this->submitAttempt($attempt, 'submitted');

        return redirect()->route('student.quizzes.results', $attempt);
    }

    public function results(QuizAttempt $attempt): View
    {
        $student = auth()->user();

        if ($attempt->student_id !== $student->id) {
            abort(403);
        }

        if ($attempt->status === 'in_progress') {
            return redirect()->route('student.quizzes.take', $attempt);
        }

        $quiz = $attempt->quiz;
        $quiz->load('questions');

        $answers = QuizAnswer::where('attempt_id', $attempt->id)
            ->get()
            ->keyBy('question_id');

        return view('student.quizzes.results', compact('quiz', 'attempt', 'answers'));
    }

    private function submitAttempt(QuizAttempt $attempt, string $status): void
    {
        $quiz = $attempt->quiz;
        $quiz->load('questions');

        DB::transaction(function () use ($attempt, $quiz, $status) {
            $totalPoints = 0;
            $earnedPoints = 0;

            foreach ($quiz->questions as $question) {
                $totalPoints += $question->points;

                $answer = QuizAnswer::where('attempt_id', $attempt->id)
                    ->where('question_id', $question->id)
                    ->first();

                if ($answer) {
                    $isCorrect = mb_strtolower(trim($answer->selected_answer ?? '')) === mb_strtolower(trim($question->correct_answer));
                    $pointsEarned = $isCorrect ? $question->points : 0;
                    $earnedPoints += $pointsEarned;

                    $answer->update([
                        'is_correct' => $isCorrect,
                        'points_earned' => $pointsEarned,
                    ]);
                } else {
                    // Unanswered — create a record with no answer
                    QuizAnswer::create([
                        'attempt_id' => $attempt->id,
                        'question_id' => $question->id,
                        'school_id' => $attempt->school_id,
                        'selected_answer' => null,
                        'is_correct' => false,
                        'points_earned' => 0,
                        'answered_at' => now(),
                    ]);
                }
            }

            $percentage = $totalPoints > 0 ? round(($earnedPoints / $totalPoints) * 100, 2) : 0;

            $attempt->update([
                'score' => $earnedPoints,
                'total_points' => $totalPoints,
                'percentage' => $percentage,
                'passed' => $percentage >= $quiz->passing_score,
                'submitted_at' => now(),
                'time_spent_seconds' => now()->diffInSeconds($attempt->started_at),
                'status' => $status,
            ]);
        });
    }
}
