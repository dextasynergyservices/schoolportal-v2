<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AcademicSession;
use App\Models\AuditLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ArchiveCompletedSessions extends Command
{
    protected $signature = 'sessions:archive
        {--school-id= : Limit archival to a specific school ID}';

    protected $description = 'Archive completed academic sessions older than 1 year by writing a JSON snapshot and updating their status to "archived".';

    public function handle(): int
    {
        $schoolId = $this->option('school-id');

        $query = AcademicSession::query()
            ->where('status', 'completed')
            ->where('end_date', '<=', now()->subYear())
            ->with(['terms']);

        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        $sessions = $query->get();

        if ($sessions->isEmpty()) {
            $this->info('No sessions to archive.');

            return self::SUCCESS;
        }

        $this->info("Archiving {$sessions->count()} session(s)...");

        $archived = 0;

        foreach ($sessions as $session) {
            try {
                $this->archiveSession($session);
                $archived++;
                $this->line("  ✔ Archived session [{$session->id}] {$session->name} (school {$session->school_id})");
            } catch (\Throwable $e) {
                $this->error("  ✘ Failed to archive session [{$session->id}]: {$e->getMessage()}");
                Log::error('sessions:archive failed for session', [
                    'session_id' => $session->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Done. {$archived}/{$sessions->count()} sessions archived.");

        return self::SUCCESS;
    }

    private function archiveSession(AcademicSession $session): void
    {
        // Build archive payload.
        $data = [
            'archived_at' => now()->toIso8601String(),
            'session' => [
                'id' => $session->id,
                'school_id' => $session->school_id,
                'name' => $session->name,
                'start_date' => $session->start_date->toDateString(),
                'end_date' => $session->end_date->toDateString(),
                'status' => $session->status,
            ],
            'terms' => $session->terms->map(fn ($term) => [
                'id' => $term->id,
                'term_number' => $term->term_number,
                'name' => $term->name,
                'start_date' => $term->start_date?->toDateString(),
                'end_date' => $term->end_date?->toDateString(),
                'status' => $term->status,
            ])->toArray(),
        ];

        // Write JSON snapshot to storage.
        $directory = "archive/school_{$session->school_id}";
        $sessionSlug = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $session->name);
        $filename = "{$directory}/session_{$session->id}_{$sessionSlug}.json";

        Storage::put($filename, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // Update session status.
        $session->update([
            'status' => 'archived',
            'archived_at' => now(),
        ]);

        // Log to audit trail.
        AuditLog::create([
            'school_id' => $session->school_id,
            'user_id' => null, // System action.
            'action' => 'session.archived',
            'entity_type' => 'academic_session',
            'entity_id' => $session->id,
            'new_values' => ['status' => 'archived', 'archive_file' => $filename],
            'ip_address' => null,
            'user_agent' => 'artisan:sessions:archive',
        ]);
    }
}
