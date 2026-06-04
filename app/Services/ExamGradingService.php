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
            $attempt->status = 'graded';
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

        [$isCorrect, $pointsEarned] = match ($question->type) {
            'multiple_choice', 'true_false' => $this->gradeExactAnswer($answer->selected_answer, $question->correct_answer, $question->points),
            'fill_blank' => $this->gradeFillBlankAnswer($answer->selected_answer, $question->correct_answer, $question->points),
            'matching' => $this->gradeMatchingAnswer($answer->selected_answer, $question),
            default => [false, 0],
        };

        $answer->update([
            'is_correct' => $isCorrect,
            'points_earned' => $pointsEarned,
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

    /**
     * @return array{0: bool, 1: int}
     */
    private function gradeExactAnswer(?string $selected, ?string $correct, int $points): array
    {
        $isCorrect = $this->matchExact($selected, $correct);

        return [$isCorrect, $isCorrect ? $points : 0];
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

    /**
     * @return array{0: bool, 1: int}
     */
    private function gradeFillBlankAnswer(?string $selected, ?string $correct, int $points): array
    {
        $isCorrect = $this->matchFillBlank($selected, $correct);

        return [$isCorrect, $isCorrect ? $points : 0];
    }

    /**
     * @return array{0: bool, 1: int}
     */
    private function gradeMatchingAnswer(?string $selected, ExamQuestion $question): array
    {
        if ($selected === null || $selected === '') {
            return [false, 0];
        }

        $selectedPairs = json_decode($selected, true);
        if (! is_array($selectedPairs)) {
            return [false, 0];
        }

        $correctPairs = collect($question->options ?? [])
            ->values()
            ->filter(fn ($pair) => is_array($pair) && isset($pair['right']))
            ->values();

        if ($correctPairs->isEmpty()) {
            return [false, 0];
        }

        $correctCount = 0;
        foreach ($correctPairs as $index => $pair) {
            $selectedRight = $selectedPairs[$index] ?? $selectedPairs[(string) $index] ?? null;

            if ($this->normaliseMatchValue($selectedRight) === $this->normaliseMatchValue($pair['right'])) {
                $correctCount++;
            }
        }

        $totalPairs = $correctPairs->count();
        $isCorrect = $correctCount === $totalPairs;
        $pointsEarned = (int) round(($correctCount / $totalPairs) * $question->points);

        return [$isCorrect, $pointsEarned];
    }

    private function normaliseMatchValue(mixed $value): string
    {
        return Str::lower(trim(preg_replace('/\s+/', ' ', (string) $value)));
    }
}
