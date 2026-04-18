<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class BackupDatabase extends Command
{
    protected $signature = 'db:backup {--path= : Custom path for the backup file}';

    protected $description = 'Create a MySQL database backup using mysqldump';

    public function handle(): int
    {
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port', '3306');

        $directory = $this->option('path') ?: storage_path('app/backups');

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filename = sprintf('%s_%s.sql', $database, now()->format('Y-m-d_His'));
        $filepath = $directory.DIRECTORY_SEPARATOR.$filename;

        $this->info("Backing up database '{$database}' to {$filepath}...");

        $command = sprintf(
            'mysqldump --host=%s --port=%s --user=%s --password=%s --single-transaction --routines --triggers %s',
            escapeshellarg($host),
            escapeshellarg((string) $port),
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($database),
        );

        $result = Process::run($command.' > '.escapeshellarg($filepath));

        if ($result->successful()) {
            $size = round(filesize($filepath) / 1024, 2);
            $this->info("Backup completed successfully ({$size} KB).");

            // Clean up old backups (keep last 7)
            $this->cleanOldBackups($directory, 7);

            return self::SUCCESS;
        }

        $this->error('Backup failed: '.$result->errorOutput());

        return self::FAILURE;
    }

    protected function cleanOldBackups(string $directory, int $keep): void
    {
        $files = glob($directory.DIRECTORY_SEPARATOR.'*.sql');

        if ($files === false || count($files) <= $keep) {
            return;
        }

        // Sort by modification time, oldest first
        usort($files, fn (string $a, string $b) => filemtime($a) <=> filemtime($b));

        $toDelete = array_slice($files, 0, count($files) - $keep);

        foreach ($toDelete as $file) {
            unlink($file);
            $this->line('  Removed old backup: '.basename($file));
        }
    }
}
