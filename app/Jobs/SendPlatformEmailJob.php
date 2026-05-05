<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\PlatformEmail as PlatformEmailMailable;
use App\Models\PlatformEmail;
use App\Models\School;
use App\Services\FileUploadService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendPlatformEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Retry the job up to 3 times before marking it as failed. */
    public int $tries = 3;

    /** Seconds to wait before the next retry attempt (5 min, 15 min, 30 min). */
    public array $backoff = [300, 900, 1800];

    /** Per-job timeout (seconds). */
    public int $timeout = 120;

    public function __construct(
        private readonly int $emailId,
        private readonly int $schoolId,
    ) {}

    public function handle(): void
    {
        $record = PlatformEmail::find($this->emailId);
        $school = School::find($this->schoolId);

        // Record or school was deleted after dispatch — silently skip.
        if (! $record || ! $school) {
            return;
        }

        // School has no valid email — count as failed without retrying.
        if (! $school->email) {
            $record->increment('failed_count');

            return;
        }

        try {
            Mail::to($school->email, $school->name)
                ->send(new PlatformEmailMailable(
                    $record->subject,
                    $record->body,
                    $record->attachments ?? [],
                    $school,
                ));

            $record->increment('sent_count');
        } catch (\Throwable $e) {
            Log::error('Platform email dispatch failed', [
                'email_id' => $this->emailId,
                'school_id' => $this->schoolId,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);

            // On final attempt, record as failed and do not rethrow
            // (prevents job from being marked as "failed" in the jobs table
            //  when we handle the failure ourselves).
            if ($this->attempts() >= $this->tries) {
                $record->increment('failed_count');
                $this->cleanupAttachments($record);

                return;
            }

            // Re-throw so the queue retries with backoff.
            throw $e;
        }

        $this->cleanupAttachments($record);
    }

    /**
     * Delete Cloudinary attachments once all recipient jobs have completed.
     * Called after every sent/failed increment so we catch the last job
     * regardless of whether it succeeded or failed.
     */
    private function cleanupAttachments(PlatformEmail $record): void
    {
        $record->refresh();

        $done = ((int) $record->sent_count) + ((int) $record->failed_count);

        if ($done < (int) $record->total_recipients) {
            return; // More jobs still in flight — nothing to clean up yet.
        }

        $attachments = $record->attachments ?? [];

        if (empty($attachments)) {
            return;
        }

        $uploadService = app(FileUploadService::class);

        foreach ($attachments as $att) {
            if (empty($att['public_id'])) {
                continue;
            }

            try {
                $uploadService->deleteRaw($att['public_id']);
            } catch (\Throwable $e) {
                Log::warning('Failed to delete email attachment from Cloudinary after send', [
                    'public_id' => $att['public_id'],
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
