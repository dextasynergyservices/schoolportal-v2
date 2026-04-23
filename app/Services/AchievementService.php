<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Game;
use App\Models\GamePlay;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\StudentAchievement;
use App\Models\User;
use Illuminate\Support\Carbon;

class AchievementService
{
    /**
     * All achievement definitions.
     * Each key maps to: name, description, icon (emoji), category, and a color for the badge.
     *
     * @return array<string, array{name: string, description: string, icon: string, category: string, color: string}>
     */
    public static function definitions(): array
    {
        return [
            // ── Login Streaks ──────────────────────────────────────
            'login_streak_3' => [
                'name' => '3-Day Streak',
                'description' => 'Logged in 3 days in a row!',
                'icon' => '🔥',
                'category' => 'streak',
                'color' => 'amber',
            ],
            'login_streak_7' => [
                'name' => 'Week Warrior',
                'description' => 'Logged in 7 days in a row!',
                'icon' => '🔥',
                'category' => 'streak',
                'color' => 'amber',
            ],
            'login_streak_14' => [
                'name' => 'Two-Week Star',
                'description' => 'Logged in 14 days in a row!',
                'icon' => '⭐',
                'category' => 'streak',
                'color' => 'amber',
            ],
            'login_streak_30' => [
                'name' => 'Monthly Legend',
                'description' => 'Logged in 30 days in a row — amazing!',
                'icon' => '👑',
                'category' => 'streak',
                'color' => 'yellow',
            ],

            // ── Quiz Milestones ────────────────────────────────────
            'quiz_first' => [
                'name' => 'First Quiz!',
                'description' => 'Completed your very first quiz.',
                'icon' => '📝',
                'category' => 'quiz',
                'color' => 'blue',
            ],
            'quiz_5' => [
                'name' => 'Quiz Explorer',
                'description' => 'Completed 5 quizzes!',
                'icon' => '📝',
                'category' => 'quiz',
                'color' => 'blue',
            ],
            'quiz_10' => [
                'name' => 'Quiz Pro',
                'description' => 'Completed 10 quizzes — keep it up!',
                'icon' => '🏅',
                'category' => 'quiz',
                'color' => 'blue',
            ],
            'quiz_25' => [
                'name' => 'Quiz Champion',
                'description' => 'Completed 25 quizzes — incredible!',
                'icon' => '🏆',
                'category' => 'quiz',
                'color' => 'indigo',
            ],
            'quiz_perfect' => [
                'name' => 'Perfect Score!',
                'description' => 'Scored 100% on a quiz!',
                'icon' => '💯',
                'category' => 'quiz',
                'color' => 'emerald',
            ],
            'quiz_perfect_3' => [
                'name' => 'Hat Trick',
                'description' => 'Scored 100% on 3 different quizzes!',
                'icon' => '🎩',
                'category' => 'quiz',
                'color' => 'emerald',
            ],
            'quiz_pass_streak_5' => [
                'name' => 'On a Roll!',
                'description' => 'Passed 5 quizzes in a row!',
                'icon' => '🎯',
                'category' => 'quiz',
                'color' => 'cyan',
            ],
            'quiz_pass_streak_10' => [
                'name' => 'Unstoppable!',
                'description' => 'Passed 10 quizzes in a row!',
                'icon' => '🚀',
                'category' => 'quiz',
                'color' => 'cyan',
            ],

            // ── Game Milestones ────────────────────────────────────
            'game_first' => [
                'name' => 'First Game!',
                'description' => 'Played your very first game.',
                'icon' => '🎮',
                'category' => 'game',
                'color' => 'pink',
            ],
            'game_5' => [
                'name' => 'Game Explorer',
                'description' => 'Played 5 games!',
                'icon' => '🎮',
                'category' => 'game',
                'color' => 'pink',
            ],
            'game_10' => [
                'name' => 'Game Pro',
                'description' => 'Played 10 games — awesome!',
                'icon' => '🕹️',
                'category' => 'game',
                'color' => 'pink',
            ],
            'game_25' => [
                'name' => 'Game Champion',
                'description' => 'Played 25 games — legendary!',
                'icon' => '🏆',
                'category' => 'game',
                'color' => 'rose',
            ],

            // ── Learning Combined ──────────────────────────────────
            'learning_10' => [
                'name' => 'Active Learner',
                'description' => '10 total quizzes + games completed!',
                'icon' => '📚',
                'category' => 'learning',
                'color' => 'purple',
            ],
            'learning_25' => [
                'name' => 'Super Student',
                'description' => '25 total quizzes + games — outstanding!',
                'icon' => '🌟',
                'category' => 'learning',
                'color' => 'purple',
            ],
            'learning_50' => [
                'name' => 'Scholar',
                'description' => '50 total quizzes + games — you\'re a star!',
                'icon' => '🎓',
                'category' => 'learning',
                'color' => 'violet',
            ],
        ];
    }

    /**
     * Get the definition for a single achievement.
     */
    public static function definition(string $key): ?array
    {
        return self::definitions()[$key] ?? null;
    }

    /**
     * Process login streak for a student.
     * Called when a student logs in.
     *
     * @return list<string> Newly unlocked achievement keys
     */
    public function processLoginStreak(User $student): array
    {
        if (! $student->isStudent()) {
            return [];
        }

        $profile = $student->studentProfile;
        if (! $profile) {
            return [];
        }

        $today = Carbon::today();
        $lastDate = $profile->last_streak_date ? Carbon::parse($profile->last_streak_date) : null;

        // Already counted today
        if ($lastDate && $lastDate->isSameDay($today)) {
            return [];
        }

        if ($lastDate && $lastDate->isSameDay($today->copy()->subDay())) {
            // Consecutive day — increment
            $profile->login_streak += 1;
        } else {
            // Gap — reset to 1
            $profile->login_streak = 1;
        }

        $profile->last_streak_date = $today;

        if ($profile->login_streak > $profile->best_login_streak) {
            $profile->best_login_streak = $profile->login_streak;
        }

        $profile->save();

        // Check streak achievements
        $newlyUnlocked = [];
        $streakThresholds = [3, 7, 14, 30];

        foreach ($streakThresholds as $threshold) {
            if ($profile->login_streak >= $threshold) {
                $key = "login_streak_{$threshold}";
                if ($this->award($student, $key, ['streak' => $profile->login_streak])) {
                    $newlyUnlocked[] = $key;
                }
            }
        }

        return $newlyUnlocked;
    }

    /**
     * Check and award quiz-related achievements after a quiz submission.
     *
     * @return list<string> Newly unlocked achievement keys
     */
    public function processQuizCompletion(User $student, QuizAttempt $attempt): array
    {
        if (! $student->isStudent()) {
            return [];
        }

        $profile = $student->studentProfile;
        $newlyUnlocked = [];

        // ── Quiz pass streak ───────────────────────────────────
        if ($profile) {
            if ($attempt->passed) {
                $profile->quiz_pass_streak += 1;
                if ($profile->quiz_pass_streak > $profile->best_quiz_pass_streak) {
                    $profile->best_quiz_pass_streak = $profile->quiz_pass_streak;
                }
            } else {
                $profile->quiz_pass_streak = 0;
            }
            $profile->save();

            // Pass streak achievements
            foreach ([5, 10] as $threshold) {
                if ($profile->quiz_pass_streak >= $threshold) {
                    $key = "quiz_pass_streak_{$threshold}";
                    if ($this->award($student, $key, ['streak' => $profile->quiz_pass_streak])) {
                        $newlyUnlocked[] = $key;
                    }
                }
            }
        }

        // ── Quiz completion count milestones ───────────────────
        $totalCompleted = QuizAttempt::withoutGlobalScopes()
            ->where('student_id', $student->id)
            ->whereIn('status', ['submitted', 'timed_out'])
            ->count();

        $milestones = [1 => 'quiz_first', 5 => 'quiz_5', 10 => 'quiz_10', 25 => 'quiz_25'];
        foreach ($milestones as $threshold => $key) {
            if ($totalCompleted >= $threshold) {
                if ($this->award($student, $key, ['count' => $totalCompleted])) {
                    $newlyUnlocked[] = $key;
                }
            }
        }

        // ── Perfect score ──────────────────────────────────────
        if ($attempt->percentage >= 100) {
            if ($this->award($student, 'quiz_perfect', ['quiz_id' => $attempt->quiz_id, 'percentage' => $attempt->percentage])) {
                $newlyUnlocked[] = 'quiz_perfect';
            }

            // Hat trick — 3 perfect scores on different quizzes
            $perfectCount = QuizAttempt::withoutGlobalScopes()
                ->where('student_id', $student->id)
                ->whereIn('status', ['submitted', 'timed_out'])
                ->where('percentage', '>=', 100)
                ->distinct('quiz_id')
                ->count('quiz_id');

            if ($perfectCount >= 3) {
                if ($this->award($student, 'quiz_perfect_3', ['perfect_count' => $perfectCount])) {
                    $newlyUnlocked[] = 'quiz_perfect_3';
                }
            }
        }

        // ── Combined learning milestones ───────────────────────
        $newlyUnlocked = array_merge($newlyUnlocked, $this->checkLearningMilestones($student));

        return $newlyUnlocked;
    }

    /**
     * Check and award game-related achievements after a game completion.
     *
     * @return list<string> Newly unlocked achievement keys
     */
    public function processGameCompletion(User $student): array
    {
        if (! $student->isStudent()) {
            return [];
        }

        $newlyUnlocked = [];

        // ── Game completion count milestones ───────────────────
        $totalPlayed = GamePlay::withoutGlobalScopes()
            ->where('student_id', $student->id)
            ->where('completed', true)
            ->count();

        $milestones = [1 => 'game_first', 5 => 'game_5', 10 => 'game_10', 25 => 'game_25'];
        foreach ($milestones as $threshold => $key) {
            if ($totalPlayed >= $threshold) {
                if ($this->award($student, $key, ['count' => $totalPlayed])) {
                    $newlyUnlocked[] = $key;
                }
            }
        }

        // ── Combined learning milestones ───────────────────────
        $newlyUnlocked = array_merge($newlyUnlocked, $this->checkLearningMilestones($student));

        return $newlyUnlocked;
    }

    /**
     * Check combined learning milestones (quizzes + games).
     *
     * @return list<string> Newly unlocked achievement keys
     */
    private function checkLearningMilestones(User $student): array
    {
        $quizCount = QuizAttempt::withoutGlobalScopes()
            ->where('student_id', $student->id)
            ->whereIn('status', ['submitted', 'timed_out'])
            ->count();

        $gameCount = GamePlay::withoutGlobalScopes()
            ->where('student_id', $student->id)
            ->where('completed', true)
            ->count();

        $total = $quizCount + $gameCount;
        $newlyUnlocked = [];

        $milestones = [10 => 'learning_10', 25 => 'learning_25', 50 => 'learning_50'];
        foreach ($milestones as $threshold => $key) {
            if ($total >= $threshold) {
                if ($this->award($student, $key, ['total' => $total, 'quizzes' => $quizCount, 'games' => $gameCount])) {
                    $newlyUnlocked[] = $key;
                }
            }
        }

        return $newlyUnlocked;
    }

    /**
     * Award an achievement to a student. Returns true if newly awarded, false if already had it.
     */
    public function award(User $student, string $achievementKey, array $metadata = []): bool
    {
        // Don't award if definition doesn't exist
        if (! self::definition($achievementKey)) {
            return false;
        }

        // Check if already unlocked (using withoutGlobalScopes to avoid tenant filtering issues)
        $exists = StudentAchievement::withoutGlobalScopes()
            ->where('student_id', $student->id)
            ->where('achievement_key', $achievementKey)
            ->exists();

        if ($exists) {
            return false;
        }

        StudentAchievement::create([
            'student_id' => $student->id,
            'school_id' => $student->school_id,
            'achievement_key' => $achievementKey,
            'unlocked_at' => now(),
            'metadata' => $metadata ?: null,
        ]);

        return true;
    }

    /**
     * Get all achievements for a student, with locked/unlocked status.
     *
     * @return array{unlocked: list<array>, locked: list<array>, total: int, unlocked_count: int}
     */
    public function getStudentProgress(User $student): array
    {
        $earned = StudentAchievement::withoutGlobalScopes()
            ->where('student_id', $student->id)
            ->get()
            ->keyBy('achievement_key');

        $unlocked = [];
        $locked = [];

        foreach (self::definitions() as $key => $def) {
            $achievement = $earned->get($key);
            $item = array_merge($def, ['key' => $key]);

            if ($achievement) {
                $item['unlocked_at'] = $achievement->unlocked_at;
                $item['metadata'] = $achievement->metadata;
                $unlocked[] = $item;
            } else {
                $locked[] = $item;
            }
        }

        // Sort unlocked by most recent
        usort($unlocked, fn ($a, $b) => $b['unlocked_at']->timestamp - $a['unlocked_at']->timestamp);

        return [
            'unlocked' => $unlocked,
            'locked' => $locked,
            'total' => count(self::definitions()),
            'unlocked_count' => count($unlocked),
        ];
    }

    /**
     * Get the next closest achievements the student can unlock, with progress hints.
     *
     * @return list<array{key: string, name: string, icon: string, progress: string}>
     */
    public function getNextGoals(User $student, int $limit = 3): array
    {
        $earned = StudentAchievement::withoutGlobalScopes()
            ->where('student_id', $student->id)
            ->pluck('achievement_key')
            ->all();

        $profile = $student->studentProfile;
        $classId = $profile?->class_id;
        $goals = [];

        // Quiz count goals — only show if there are published quizzes for the student's class
        $hasAvailableQuizzes = $classId && Quiz::withoutGlobalScopes()
            ->where('school_id', $student->school_id)
            ->where('class_id', $classId)
            ->where('is_published', true)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()))
            ->exists();

        $quizCount = QuizAttempt::withoutGlobalScopes()
            ->where('student_id', $student->id)
            ->whereIn('status', ['submitted', 'timed_out'])
            ->count();

        $quizMilestones = [1 => 'quiz_first', 5 => 'quiz_5', 10 => 'quiz_10', 25 => 'quiz_25'];
        if ($hasAvailableQuizzes || $quizCount > 0) {
            foreach ($quizMilestones as $threshold => $key) {
                if (! in_array($key, $earned, true) && $quizCount < $threshold) {
                    $goals[] = [
                        'key' => $key,
                        'name' => self::definitions()[$key]['name'],
                        'icon' => self::definitions()[$key]['icon'],
                        'color' => self::definitions()[$key]['color'],
                        'category' => 'quiz',
                        'progress' => $quizCount.'/'.$threshold.' quizzes',
                        'percent' => $threshold > 0 ? min(100, (int) round(($quizCount / $threshold) * 100)) : 0,
                    ];
                    break; // Only show the next quiz milestone
                }
            }
        }

        // Game count goals — only show if there are published games for the student's class
        $hasAvailableGames = $classId && Game::withoutGlobalScopes()
            ->where('school_id', $student->school_id)
            ->where('class_id', $classId)
            ->where('is_published', true)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()))
            ->exists();
        $gameCount = GamePlay::withoutGlobalScopes()
            ->where('student_id', $student->id)
            ->where('completed', true)
            ->count();

        $gameMilestones = [1 => 'game_first', 5 => 'game_5', 10 => 'game_10', 25 => 'game_25'];
        if ($hasAvailableGames || $gameCount > 0) {
            foreach ($gameMilestones as $threshold => $key) {
                if (! in_array($key, $earned, true) && $gameCount < $threshold) {
                    $goals[] = [
                        'key' => $key,
                        'name' => self::definitions()[$key]['name'],
                        'icon' => self::definitions()[$key]['icon'],
                        'color' => self::definitions()[$key]['color'],
                        'category' => 'game',
                        'progress' => $gameCount.'/'.$threshold.' games',
                        'percent' => $threshold > 0 ? min(100, (int) round(($gameCount / $threshold) * 100)) : 0,
                    ];
                    break;
                }
            }
        }

        // Login streak goals
        $currentStreak = $profile?->login_streak ?? 0;
        $streakThresholds = [3, 7, 14, 30];
        foreach ($streakThresholds as $threshold) {
            $key = "login_streak_{$threshold}";
            if (! in_array($key, $earned, true) && $currentStreak < $threshold) {
                $goals[] = [
                    'key' => $key,
                    'name' => self::definitions()[$key]['name'],
                    'icon' => self::definitions()[$key]['icon'],
                    'color' => self::definitions()[$key]['color'],
                    'category' => 'streak',
                    'progress' => $currentStreak.'/'.$threshold.' days',
                    'percent' => $threshold > 0 ? min(100, (int) round(($currentStreak / $threshold) * 100)) : 0,
                ];
                break;
            }
        }

        // Sort by closest to completion (highest percent first)
        usort($goals, fn ($a, $b) => $b['percent'] - $a['percent']);

        return array_slice($goals, 0, $limit);
    }
}
