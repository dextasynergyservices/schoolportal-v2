<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Models\ExamQuestion;
use Illuminate\Support\Str;

class ExamGradingService
{
    /**
     * Grade all auto-gradable answers in an attempt and finalize the score.
     */
    public function gradeAttempt(ExamAttempt $attempt): ExamAttempt
    {
        $attempt->loadMissing(['answers.question', 'exam']);

        $totalPoints = 0;
        $earnedPoints = 0;
        $hasTheoryQuestions = false;

        foreach ($attempt->answers as $answer) {
            $question = $answer->question;
            $totalPoints += $question->points;

            if ($question->isAutoGradable()) {
                $this->gradeObjectiveAnswer($answer, $question);
                $earnedPoints += $answer->points_earned;
            } elseif ($answer->isAnswered()) {
                $hasTheoryQuestions = true;
            }
        }

        // Also account for unanswered questions
        $allQuestionPoints = $attempt->exam->questions()->sum('points');

        $attempt->total_points = $allQuestionPoints;
        $attempt->score = $earnedPoints;

        if ($hasTheoryQuestions) {
            // Theory needs manual grading — set status accordingly
            $attempt->status = 'grading';
            $attempt->percentage = null;
            $attempt->passed = null;
        } else {
            // Fully auto-graded
            $attempt->percentage = $allQuestionPoints > 0
                ? round(($earnedPoints / $allQuestionPoints) * 100, 2)
                : 0;
            $attempt->passed = $attempt->percentage >= $attempt->exam->passing_score;
        }

        $attempt->save();

        // Auto-update subject score if fully graded (no pending theory)
        if (! $hasTheoryQuestions) {
            app(ScoreAggregationService::class)->updateScoreFromExam($attempt);
        }

        return $attempt;
    }

    /**
     * Grade a single objective answer.
     */
    public function gradeObjectiveAnswer(ExamAnswer $answer, ?ExamQuestion $question = null): void
    {
        $question ??= $answer->question;

        if (! $question->isAutoGradable() || ! $answer->isAnswered()) {
            return;
        }

        $isCorrect = match ($question->type) {
            'multiple_choice', 'true_false' => $this->matchExact($answer->selected_answer, $question->correct_answer),
            'fill_blank' => $this->matchFillBlank($answer->selected_answer, $question->correct_answer),
            default => false,
        };

        $answer->update([
            'is_correct' => $isCorrect,
            'points_earned' => $isCorrect ? $question->points : 0,
            'answered_at' => $answer->answered_at ?? now(),
        ]);
    }

    /**
     * Recalculate attempt score after manual grading of a theory answer.
     */
    public function recalculateAttemptScore(ExamAttempt $attempt): ExamAttempt
    {
        $attempt->loadMissing(['answers.question', 'exam']);

        $earnedPoints = 0;
        $allGraded = true;

        foreach ($attempt->answers as $answer) {
            if ($answer->isGraded()) {
                $earnedPoints += $answer->points_earned;
            } elseif ($answer->isAnswered() && $answer->question->isTheory()) {
                $allGraded = false;
            }
        }

        $attempt->score = $earnedPoints;

        if ($allGraded) {
            $totalPoints = $attempt->exam->questions()->sum('points');
            $attempt->total_points = $totalPoints;
            $attempt->percentage = $totalPoints > 0
                ? round(($earnedPoints / $totalPoints) * 100, 2)
                : 0;
            $attempt->passed = $attempt->percentage >= $attempt->exam->passing_score;
            $attempt->status = 'graded';
        }

        $attempt->save();

        // Auto-update subject score when all grading is complete
        if ($allGraded) {
            app(ScoreAggregationService::class)->updateScoreFromExam($attempt);
        }

        return $attempt;
    }

    // ── Private helpers ──

    private function matchExact(?string $selected, ?string $correct): bool
    {
        if ($selected === null || $correct === null) {
            return false;
        }

        return Str::lower(trim($selected)) === Str::lower(trim($correct));
    }

    private function matchFillBlank(?string $selected, ?string $correct): bool
    {
        if ($selected === null || $correct === null) {
            return false;
        }

        $selected = Str::lower(trim(preg_replace('/\s+/', ' ', $selected)));
        $correct = Str::lower(trim(preg_replace('/\s+/', ' ', $correct)));

        // Exact match after normalization
        if ($selected === $correct) {
            return true;
        }

        // Allow minor variation: if correct has multiple accepted answers separated by |
        if (Str::contains($correct, '|')) {
            $acceptable = array_map(fn ($a) => Str::lower(trim($a)), explode('|', $correct));

            return in_array($selected, $acceptable, true);
        }

        return false;
    }
}
