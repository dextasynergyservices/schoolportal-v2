<?php

declare(strict_types=1);

namespace App\Livewire\Teacher;

use App\Models\Game;
use App\Models\GamePlay;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class StudentInsights extends Component
{
    public ?int $sessionId = null;

    public ?int $termId = null;

    public bool $compact = false;

    /** @var array<int, array<string, mixed>> */
    public array $insights = [];

    public int $totalStudents = 0;

    public int $onTrackCount = 0;

    public int $flaggedCount = 0;

    public int $availableQuizCount = 0;

    public int $availableGameCount = 0;

    public function mount(?int $sessionId = null, ?int $termId = null, bool $compact = false): void
    {
        $this->sessionId = $sessionId;
        $this->termId = $termId;
        $this->compact = $compact;
        $this->computeInsights();
    }

    public function render(): View
    {
        return view('livewire.teacher.student-insights');
    }

    public function placeholder(): string
    {
        return <<<'HTML'
        <div class="dash-panel">
            <div class="dash-panel-header">
                <div class="flex items-center gap-2">
                    <div class="h-4 w-4 rounded bg-zinc-200 dark:bg-zinc-700 animate-pulse"></div>
                    <div class="h-4 w-32 rounded bg-zinc-200 dark:bg-zinc-700 animate-pulse"></div>
                </div>
            </div>
            <div class="p-4 space-y-3">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-zinc-200 dark:bg-zinc-700 animate-pulse shrink-0"></div>
                    <div class="flex-1 space-y-2">
                        <div class="h-3 w-3/4 rounded bg-zinc-200 dark:bg-zinc-700 animate-pulse"></div>
                        <div class="h-2 w-1/2 rounded bg-zinc-200 dark:bg-zinc-700 animate-pulse"></div>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-zinc-200 dark:bg-zinc-700 animate-pulse shrink-0"></div>
                    <div class="flex-1 space-y-2">
                        <div class="h-3 w-2/3 rounded bg-zinc-200 dark:bg-zinc-700 animate-pulse"></div>
                        <div class="h-2 w-1/3 rounded bg-zinc-200 dark:bg-zinc-700 animate-pulse"></div>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-zinc-200 dark:bg-zinc-700 animate-pulse shrink-0"></div>
                    <div class="flex-1 space-y-2">
                        <div class="h-3 w-1/2 rounded bg-zinc-200 dark:bg-zinc-700 animate-pulse"></div>
                        <div class="h-2 w-2/5 rounded bg-zinc-200 dark:bg-zinc-700 animate-pulse"></div>
                    </div>
                </div>
            </div>
        </div>
        HTML;
    }

    private function computeInsights(): void
    {
        $teacher = auth()->user();
        $school = app('current.school');
        $classIds = $teacher->assignedClasses()->pluck('id');

        if ($classIds->isEmpty()) {
            return;
        }

        // Resolve session/term — use passed IDs or fall back to school's current
        $sessionId = $this->sessionId ?? $school->currentSession()?->id;
        $termId = $this->termId ?? $school->currentTerm()?->id;

        if (! $sessionId || ! $termId) {
            return;
        }

        // ── Available content for teacher's classes this term ─────────
        $quizInfo = Quiz::whereIn('class_id', $classIds)
            ->where('is_published', true)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId)
            ->get(['id', 'title', 'class_id']);

        $availableQuizIds = $quizInfo->pluck('id');
        $quizTitleMap = $quizInfo->pluck('title', 'id');
        $quizIdsByClass = $quizInfo->groupBy('class_id')->map(fn ($q) => $q->pluck('id')->all());

        $gameInfo = Game::whereIn('class_id', $classIds)
            ->where('is_published', true)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId)
            ->get(['id', 'title', 'class_id', 'game_type']);

        $availableGameIds = $gameInfo->pluck('id');
        $gameDetailMap = $gameInfo->mapWithKeys(fn ($g) => [$g->id => ['title' => $g->title, 'game_type' => $g->game_type]]);
        $gameIdsByClass = $gameInfo->groupBy('class_id')->map(fn ($g) => $g->pluck('id')->all());

        $this->availableQuizCount = $availableQuizIds->count();
        $this->availableGameCount = $availableGameIds->count();

        // If there's no published content, nothing to analyze
        if ($availableQuizIds->isEmpty() && $availableGameIds->isEmpty()) {
            return;
        }

        // ── Get all active students in teacher's classes ─────────────
        $students = User::where('role', 'student')
            ->where('is_active', true)
            ->whereHas('studentProfile', fn ($q) => $q->whereIn('class_id', $classIds))
            ->with('studentProfile.class:id,name')
            ->get(['id', 'name', 'avatar_url']);

        $this->totalStudents = $students->count();

        if ($students->isEmpty()) {
            return;
        }

        // ── Quiz stats per student ───────────────────────────────────
        $quizStats = collect();
        if ($availableQuizIds->isNotEmpty()) {
            $quizStats = QuizAttempt::whereIn('quiz_id', $availableQuizIds)
                ->whereIn('status', ['submitted', 'timed_out'])
                ->selectRaw('student_id, AVG(percentage) as avg_score, COUNT(DISTINCT quiz_id) as quizzes_attempted, SUM(CASE WHEN passed = 1 THEN 1 ELSE 0 END) as passed_count, COUNT(*) as total_attempts')
                ->groupBy('student_id')
                ->get()
                ->keyBy('student_id');
        }

        // ── Game stats per student ───────────────────────────────────
        $gameStats = collect();
        if ($availableGameIds->isNotEmpty()) {
            $gameStats = GamePlay::whereIn('game_id', $availableGameIds)
                ->where('completed', true)
                ->selectRaw('student_id, AVG(percentage) as avg_score, COUNT(DISTINCT game_id) as games_played, COUNT(*) as total_plays')
                ->groupBy('student_id')
                ->get()
                ->keyBy('student_id');
        }

        // ── Analyze each student ─────────────────────────────────────
        $flagged = [];

        foreach ($students as $student) {
            $concerns = [];
            $severity = 0;

            $quiz = $quizStats->get($student->id);
            $game = $gameStats->get($student->id);

            // Quiz analysis
            if ($availableQuizIds->isNotEmpty()) {
                if (! $quiz) {
                    $concerns[] = ['type' => 'no_quiz', 'label' => __('No quiz attempts')];
                    $severity += 2;
                } else {
                    $avgScore = round((float) $quiz->avg_score);

                    if ($avgScore < 40) {
                        $concerns[] = ['type' => 'low_quiz', 'label' => __('Quiz avg: :score%', ['score' => $avgScore])];
                        $severity += 4;
                    } elseif ($avgScore < 60) {
                        $concerns[] = ['type' => 'mid_quiz', 'label' => __('Quiz avg: :score%', ['score' => $avgScore])];
                        $severity += 2;
                    }

                    $attempted = (int) $quiz->quizzes_attempted;
                    $available = $availableQuizIds->count();
                    if ($available > 1 && $attempted < $available) {
                        $concerns[] = ['type' => 'missing_quiz', 'label' => __(':done/:total quizzes done', ['done' => $attempted, 'total' => $available])];
                        $severity += 1;
                    }
                }
            }

            // Game analysis
            if ($availableGameIds->isNotEmpty()) {
                if (! $game) {
                    $concerns[] = ['type' => 'no_game', 'label' => __('No games played')];
                    $severity += 1;
                } else {
                    $avgScore = round((float) $game->avg_score);

                    if ($avgScore < 40) {
                        $concerns[] = ['type' => 'low_game', 'label' => __('Game avg: :score%', ['score' => $avgScore])];
                        $severity += 3;
                    } elseif ($avgScore < 60) {
                        $concerns[] = ['type' => 'mid_game', 'label' => __('Game avg: :score%', ['score' => $avgScore])];
                        $severity += 1;
                    }
                }
            }

            if (! empty($concerns)) {
                $flagged[] = [
                    'id' => $student->id,
                    'name' => $student->name,
                    'avatar_url' => $student->avatar_url,
                    'initials' => $student->initials(),
                    'class_id' => $student->studentProfile?->class_id,
                    'class' => $student->studentProfile?->class?->name ?? '—',
                    'concerns' => $concerns,
                    'severity' => $severity,
                    'quiz_avg' => $quiz ? (int) round((float) $quiz->avg_score) : null,
                    'game_avg' => $game ? (int) round((float) $game->avg_score) : null,
                    'quizzes_done' => $quiz ? (int) $quiz->quizzes_attempted : 0,
                    'games_done' => $game ? (int) $game->games_played : 0,
                ];
            }
        }

        // Sort by severity (most concerning first) and limit to 10
        usort($flagged, fn (array $a, array $b): int => $b['severity'] <=> $a['severity']);
        $this->insights = array_slice($flagged, 0, 10);
        $this->flaggedCount = count($flagged);
        $this->onTrackCount = $this->totalStudents - count($flagged);

        // ── Enrich with per-quiz/game detail for flagged students ──
        $flaggedIds = collect($this->insights)->pluck('id');

        if ($flaggedIds->isEmpty()) {
            return;
        }

        $perQuizScores = collect();
        if ($availableQuizIds->isNotEmpty()) {
            $perQuizScores = QuizAttempt::whereIn('quiz_id', $availableQuizIds)
                ->whereIn('student_id', $flaggedIds)
                ->whereIn('status', ['submitted', 'timed_out'])
                ->selectRaw('student_id, quiz_id, MAX(percentage) as best_score, MAX(CASE WHEN passed = 1 THEN 1 ELSE 0 END) as ever_passed')
                ->groupBy('student_id', 'quiz_id')
                ->get()
                ->groupBy('student_id');
        }

        $perGameScores = collect();
        if ($availableGameIds->isNotEmpty()) {
            $perGameScores = GamePlay::whereIn('game_id', $availableGameIds)
                ->whereIn('student_id', $flaggedIds)
                ->where('completed', true)
                ->selectRaw('student_id, game_id, MAX(percentage) as best_score')
                ->groupBy('student_id', 'game_id')
                ->get()
                ->groupBy('student_id');
        }

        foreach ($this->insights as &$insight) {
            $classId = $insight['class_id'] ?? null;

            // Quiz details
            $classQuizIds = $quizIdsByClass->get($classId, []);
            $studentQuizAttempts = $perQuizScores->get($insight['id'], collect())->keyBy('quiz_id');

            $quizDetails = [];
            $quizMissed = [];
            foreach ($classQuizIds as $qId) {
                if ($attempt = $studentQuizAttempts->get($qId)) {
                    $quizDetails[] = [
                        'id' => $qId,
                        'title' => $quizTitleMap->get($qId, '—'),
                        'score' => (int) round((float) $attempt->best_score),
                        'passed' => (bool) $attempt->ever_passed,
                    ];
                } else {
                    $quizMissed[] = [
                        'id' => $qId,
                        'title' => $quizTitleMap->get($qId, '—'),
                    ];
                }
            }
            usort($quizDetails, fn (array $a, array $b): int => $a['score'] <=> $b['score']);

            // Game details
            $classGameIds = $gameIdsByClass->get($classId, []);
            $studentGamePlays = $perGameScores->get($insight['id'], collect())->keyBy('game_id');

            $gameDetails = [];
            $gameMissed = [];
            foreach ($classGameIds as $gId) {
                $gInfo = $gameDetailMap->get($gId);
                if ($play = $studentGamePlays->get($gId)) {
                    $gameDetails[] = [
                        'id' => $gId,
                        'title' => is_array($gInfo) ? $gInfo['title'] : '—',
                        'score' => (int) round((float) $play->best_score),
                        'game_type' => is_array($gInfo) ? $gInfo['game_type'] : 'unknown',
                    ];
                } else {
                    $gameMissed[] = [
                        'id' => $gId,
                        'title' => is_array($gInfo) ? $gInfo['title'] : '—',
                        'game_type' => is_array($gInfo) ? $gInfo['game_type'] : 'unknown',
                    ];
                }
            }
            usort($gameDetails, fn (array $a, array $b): int => $a['score'] <=> $b['score']);

            $insight['quiz_details'] = $quizDetails;
            $insight['quiz_missed'] = $quizMissed;
            $insight['game_details'] = $gameDetails;
            $insight['game_missed'] = $gameMissed;
        }
        unset($insight);
    }
}
