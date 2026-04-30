<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Models\ExamQuestion;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Component;

class ExamAnalytics extends Component
{
    public Exam $exam;

    public function mount(Exam $exam): void
    {
        $this->exam = $exam->load(['class:id,name', 'subject:id,name', 'session:id,name', 'term:id,name']);
    }

    public function render(): View
    {
        $attempts = ExamAttempt::where('exam_id', $this->exam->id)
            ->whereIn('status', ['submitted', 'timed_out', 'grading', 'graded'])
            ->with('student:id,name,username')
            ->get();

        $gradedAttempts = $attempts->whereNotNull('percentage');

        // ── Summary Stats ──
        $stats = [
            'total_attempts' => $attempts->count(),
            'graded' => $gradedAttempts->count(),
            'pending_grading' => $attempts->where('status', 'grading')->count(),
            'average_score' => $gradedAttempts->count() > 0 ? round($gradedAttempts->avg('percentage'), 1) : null,
            'median_score' => $this->calculateMedian($gradedAttempts->pluck('percentage')),
            'highest_score' => $gradedAttempts->max('percentage'),
            'lowest_score' => $gradedAttempts->min('percentage'),
            'pass_count' => $gradedAttempts->where('passed', true)->count(),
            'fail_count' => $gradedAttempts->where('passed', false)->count(),
            'pass_rate' => $gradedAttempts->count() > 0
                ? round(($gradedAttempts->where('passed', true)->count() / $gradedAttempts->count()) * 100, 1)
                : null,
            'avg_time' => $attempts->avg('time_spent_seconds'),
        ];

        // ── Score Distribution (for chart) ──
        $distribution = $this->getScoreDistribution($gradedAttempts);

        // ── Hardest Questions ──
        $hardestQuestions = $this->getHardestQuestions();

        // ── Per-Student Results ──
        $studentResults = $gradedAttempts->sortByDesc('percentage')->values();

        return view('livewire.admin.exam-analytics', compact('stats', 'distribution', 'hardestQuestions', 'studentResults', 'attempts'));
    }

    private function calculateMedian(Collection $values): ?float
    {
        $sorted = $values->filter()->map(fn ($v) => (float) $v)->sort()->values();
        $count = $sorted->count();

        if ($count === 0) {
            return null;
        }

        $mid = intdiv($count, 2);

        if ($count % 2 === 0) {
            return round(($sorted[$mid - 1] + $sorted[$mid]) / 2, 1);
        }

        return round($sorted[$mid], 1);
    }

    private function getScoreDistribution(Collection $gradedAttempts): array
    {
        $ranges = [
            '0-9' => 0, '10-19' => 0, '20-29' => 0, '30-39' => 0, '40-49' => 0,
            '50-59' => 0, '60-69' => 0, '70-79' => 0, '80-89' => 0, '90-100' => 0,
        ];

        foreach ($gradedAttempts as $attempt) {
            $pct = (int) $attempt->percentage;
            match (true) {
                $pct >= 90 => $ranges['90-100']++,
                $pct >= 80 => $ranges['80-89']++,
                $pct >= 70 => $ranges['70-79']++,
                $pct >= 60 => $ranges['60-69']++,
                $pct >= 50 => $ranges['50-59']++,
                $pct >= 40 => $ranges['40-49']++,
                $pct >= 30 => $ranges['30-39']++,
                $pct >= 20 => $ranges['20-29']++,
                $pct >= 10 => $ranges['10-19']++,
                default => $ranges['0-9']++,
            };
        }

        return $ranges;
    }

    private function getHardestQuestions(): Collection
    {
        $questions = ExamQuestion::where('exam_id', $this->exam->id)
            ->orderBy('sort_order')
            ->get();

        if ($questions->isEmpty()) {
            return collect();
        }

        $attemptIds = ExamAttempt::where('exam_id', $this->exam->id)
            ->whereIn('status', ['submitted', 'timed_out', 'grading', 'graded'])
            ->pluck('id');

        if ($attemptIds->isEmpty()) {
            return collect();
        }

        return $questions->map(function ($question) use ($attemptIds) {
            $answers = ExamAnswer::where('question_id', $question->id)
                ->whereIn('attempt_id', $attemptIds)
                ->get();

            $totalAnswered = $answers->count();
            $correctCount = $answers->where('is_correct', true)->count();
            $incorrectRate = $totalAnswered > 0 ? round((($totalAnswered - $correctCount) / $totalAnswered) * 100, 1) : 0;

            return (object) [
                'id' => $question->id,
                'question_text' => $question->question_text,
                'type' => $question->type,
                'points' => $question->points,
                'total_answered' => $totalAnswered,
                'correct_count' => $correctCount,
                'incorrect_rate' => $incorrectRate,
                'correct_rate' => $totalAnswered > 0 ? round(($correctCount / $totalAnswered) * 100, 1) : 0,
            ];
        })->sortByDesc('incorrect_rate')->values();
    }
}
