<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\SchoolClass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuizController extends Controller
{
    public function index(Request $request): View
    {
        $query = Quiz::with(['class:id,name', 'creator:id,name', 'session:id,name', 'term:id,name'])
            ->withCount('questions');

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->input('class_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $quizzes = $query->orderByDesc('created_at')->paginate(20)->withQueryString();
        $classes = SchoolClass::where('is_active', true)->orderBy('name')->get();

        return view('admin.quizzes.index', compact('quizzes', 'classes'));
    }

    public function show(Quiz $quiz): View
    {
        $quiz->load(['class:id,name', 'creator:id,name', 'questions', 'session:id,name', 'term:id,name']);

        return view('admin.quizzes.show', compact('quiz'));
    }

    public function publish(Quiz $quiz): RedirectResponse
    {
        if ($quiz->status !== 'approved') {
            return redirect()->back()->with('error', __('Only approved quizzes can be published.'));
        }

        $quiz->update([
            'is_published' => true,
            'published_at' => now(),
        ]);

        return redirect()->route('admin.quizzes.index')
            ->with('success', __('Quiz published and visible to students.'));
    }

    public function unpublish(Quiz $quiz): RedirectResponse
    {
        $quiz->update([
            'is_published' => false,
            'published_at' => null,
        ]);

        return redirect()->route('admin.quizzes.index')
            ->with('success', __('Quiz unpublished.'));
    }

    public function results(Quiz $quiz): View
    {
        $quiz->load('class:id,name');

        $attempts = $quiz->attempts()
            ->with('student:id,name,username')
            ->where('status', '!=', 'in_progress')
            ->orderByDesc('percentage')
            ->get();

        $stats = [
            'total_attempts' => $attempts->count(),
            'unique_students' => $attempts->pluck('student_id')->unique()->count(),
            'average' => $attempts->avg('percentage') ? round($attempts->avg('percentage'), 1) : 0,
            'highest' => $attempts->max('percentage') ?? 0,
            'lowest' => $attempts->min('percentage') ?? 0,
            'passed' => $attempts->where('passed', true)->count(),
            'failed' => $attempts->where('passed', false)->count(),
        ];

        return view('admin.quizzes.results', compact('quiz', 'attempts', 'stats'));
    }

    public function exportCsv(Quiz $quiz): StreamedResponse
    {
        $attempts = $quiz->attempts()
            ->with('student:id,name,username')
            ->where('status', '!=', 'in_progress')
            ->orderByDesc('percentage')
            ->get();

        $filename = 'quiz-'.Str::slug($quiz->title).'-results-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($attempts) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'Student Name', 'Username', 'Attempt #', 'Score', 'Total Points',
                'Percentage', 'Status', 'Time Spent', 'Submitted At',
            ]);

            foreach ($attempts as $attempt) {
                $time = $attempt->time_spent_seconds;
                $timeFormatted = $time !== null
                    ? sprintf('%02d:%02d', intdiv($time, 60), $time % 60)
                    : '';

                fputcsv($handle, [
                    $attempt->student?->name ?? '',
                    $attempt->student?->username ?? '',
                    $attempt->attempt_number,
                    $attempt->score ?? 0,
                    $attempt->total_points ?? 0,
                    $attempt->percentage !== null ? number_format((float) $attempt->percentage, 2) : '',
                    $attempt->passed ? 'Passed' : 'Failed',
                    $timeFormatted,
                    $attempt->submitted_at?->format('Y-m-d H:i:s') ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function destroy(Quiz $quiz): RedirectResponse
    {
        if ($quiz->is_published) {
            return redirect()->back()->with('error', __('Published quizzes cannot be deleted.'));
        }

        $quiz->delete();

        return redirect()->route('admin.quizzes.index')
            ->with('success', __('Quiz deleted.'));
    }
}
