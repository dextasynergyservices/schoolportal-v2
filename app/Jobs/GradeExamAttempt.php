<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ExamAttempt;
use App\Models\User;
use App\Notifications\ExamGradingFailedNotification;
use App\Services\ExamGradingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class GradeExamAttempt implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Max attempts before marking the job as failed.
     */
    public int $tries = 3;

    /**
     * Seconds to wait between retries.
     */
    public int $backoff = 30;

    /**
     * Timeout in seconds for each attempt.
     */
    public int $timeout = 60;

    public function __construct(public readonly ExamAttempt $attempt) {}

    public function handle(ExamGradingService $gradingService): void
    {
        // Reload fresh state to avoid race conditions or stale data from serialization
        $this->attempt->refresh();

        // Guard: only grade if the attempt is in a state that still needs grading.
        // 'submitted' = normal submit, 'timed_out' = timer expired auto-submit.
        // Any other status means it was already processed (e.g., job ran twice).
        if (! in_array($this->attempt->status, ['submitted', 'timed_out'], true)) {
            return;
        }

        $gradingService->gradeAttempt($this->attempt);
    }

    /**
     * Handle final job failure after all retries are exhausted.
     *
     * Marks the attempt as 'grading_failed' so students and admins know something
     * went wrong — the attempt is no longer stuck in 'submitted' forever.
     * Notifies all active school admins so they can manually grade or re-trigger.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('GradeExamAttempt job failed permanently after all retries', [
            'exam_attempt_id' => $this->attempt->id,
            'student_id' => $this->attempt->student_id,
            'school_id' => $this->attempt->school_id,
            'exam_id' => $this->attempt->exam_id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Mark the attempt so it is no longer stuck in limbo.
        try {
            $this->attempt->update(['status' => 'grading_failed']);
        } catch (\Throwable $e) {
            Log::error('GradeExamAttempt: could not update attempt status to grading_failed', [
                'exam_attempt_id' => $this->attempt->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Notify school admins so they can investigate or manually grade.
        try {
            $admins = User::where('school_id', $this->attempt->school_id)
                ->where('role', 'school_admin')
                ->where('is_active', true)
                ->get();

            if ($admins->isNotEmpty()) {
                $this->attempt->loadMissing(['student:id,name', 'exam:id,title']);
                Notification::send($admins, new ExamGradingFailedNotification($this->attempt));
            }
        } catch (\Throwable $e) {
            Log::error('GradeExamAttempt: could not notify admins of grading failure', [
                'exam_attempt_id' => $this->attempt->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
