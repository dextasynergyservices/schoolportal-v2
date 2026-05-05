<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;

class AuditVerifyIntegrity extends Command
{
    protected $signature = 'audit:verify-integrity
                            {--school= : Only check logs for a specific school ID}
                            {--limit=1000 : Max rows to verify per run (0 = all)}';

    protected $description = 'Verify audit log row hashes to detect tampering';

    public function handle(): int
    {
        $query = AuditLog::query()->orderBy('id');

        if ($schoolId = $this->option('school')) {
            $query->where('school_id', (int) $schoolId);
        }

        $limit = (int) $this->option('limit');
        if ($limit > 0) {
            $query->limit($limit);
        }

        $tampered = 0;
        $nullHash = 0;
        $ok = 0;
        $total = 0;

        $this->info('Verifying audit log integrity…');
        $bar = $this->output->createProgressBar();

        $query->chunk(200, function ($rows) use ($bar, &$tampered, &$nullHash, &$ok, &$total): void {
            foreach ($rows as $log) {
                $total++;
                $bar->advance();

                if ($log->row_hash === null) {
                    $nullHash++;

                    continue;
                }

                if ($log->isIntact()) {
                    $ok++;
                } else {
                    $tampered++;
                    $this->newLine();
                    $this->error("TAMPERED — id={$log->id} school={$log->school_id} action={$log->action} created_at={$log->created_at}");
                }
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total checked', $total],
                ['OK', $ok],
                ['Missing hash (legacy rows)', $nullHash],
                ['TAMPERED', $tampered],
            ]
        );

        if ($tampered > 0) {
            $this->error("{$tampered} tampered row(s) detected. Investigate immediately.");

            return self::FAILURE;
        }

        $this->info('All rows with hashes are intact.');

        return self::SUCCESS;
    }
}
