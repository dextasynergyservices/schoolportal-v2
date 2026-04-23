<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class BackupDatabase extends Command
{
    protected $signature = 'db:backup
        {--path= : Custom path for the backup file}
        {--skip-upload : Skip Google Drive upload}';

    protected $description = 'Create a MySQL database backup and upload to Google Drive';

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

        $timestamp = now()->format('Y-m-d_His');
        $sqlFilename = sprintf('%s_%s.sql', $database, $timestamp);
        $gzFilename = $sqlFilename.'.gz';
        $sqlPath = $directory.DIRECTORY_SEPARATOR.$sqlFilename;
        $gzPath = $directory.DIRECTORY_SEPARATOR.$gzFilename;

        $this->info("Backing up database '{$database}'...");

        // Step 1: mysqldump
        $command = sprintf(
            'mysqldump --host=%s --port=%s --user=%s --password=%s --single-transaction --routines --triggers %s',
            escapeshellarg($host),
            escapeshellarg((string) $port),
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($database),
        );

        $result = Process::run($command.' > '.escapeshellarg($sqlPath));

        if (! $result->successful()) {
            $this->error('Backup failed: '.$result->errorOutput());
            Log::error('Database backup failed', ['error' => $result->errorOutput()]);

            return self::FAILURE;
        }

        $sqlSize = filesize($sqlPath);
        $this->info(sprintf('SQL dump created: %s KB', round($sqlSize / 1024, 2)));

        // Step 2: Compress with gzip
        $gzipResult = Process::run(sprintf('gzip -c %s > %s', escapeshellarg($sqlPath), escapeshellarg($gzPath)));

        if ($gzipResult->successful() && file_exists($gzPath) && filesize($gzPath) > 0) {
            unlink($sqlPath);
            $gzSize = filesize($gzPath);
            $this->info(sprintf('Compressed: %s KB (%.0f%% reduction)', round($gzSize / 1024, 2), (1 - $gzSize / $sqlSize) * 100));
            $uploadFile = $gzPath;
            $uploadFilename = $gzFilename;
        } else {
            $this->warn('gzip not available, uploading uncompressed backup.');
            $uploadFile = $sqlPath;
            $uploadFilename = $sqlFilename;
            if (file_exists($gzPath)) {
                unlink($gzPath);
            }
        }

        // Step 3: Upload to Google Drive
        if (! $this->option('skip-upload') && config('services.google_drive_backup.enabled')) {
            $this->uploadToGoogleDrive($uploadFile, $uploadFilename);
        } elseif (! config('services.google_drive_backup.enabled')) {
            $this->warn('Google Drive backup is disabled. Set GOOGLE_DRIVE_BACKUP_ENABLED=true to enable.');
        }

        // Step 4: Clean up old local backups (keep last 7)
        $this->cleanOldBackups($directory, 7);

        $this->info('Backup completed successfully.');
        Log::info('Database backup completed', ['file' => $uploadFilename, 'size' => filesize($uploadFile)]);

        return self::SUCCESS;
    }

    protected function uploadToGoogleDrive(string $filePath, string $filename): void
    {
        $credentialsPath = config('services.google_drive_backup.credentials_path');
        $folderId = config('services.google_drive_backup.folder_id');
        $retentionDays = (int) config('services.google_drive_backup.retention_days', 14);

        // Resolve relative path to storage/app
        if (! str_starts_with($credentialsPath, '/') && ! str_starts_with($credentialsPath, 'C:')) {
            $credentialsPath = storage_path('app/'.$credentialsPath);
        }

        if (! file_exists($credentialsPath)) {
            $this->error("Google Drive credentials file not found: {$credentialsPath}");
            Log::error('Google Drive backup failed: credentials file not found', ['path' => $credentialsPath]);

            return;
        }

        if (! $folderId) {
            $this->error('GOOGLE_DRIVE_BACKUP_FOLDER_ID is not set.');
            Log::error('Google Drive backup failed: folder ID not configured');

            return;
        }

        try {
            $accessToken = $this->getAccessToken($credentialsPath);

            $mimeType = str_ends_with($filename, '.gz') ? 'application/gzip' : 'application/sql';

            // Google Drive multipart upload: metadata + file content
            $metadata = json_encode([
                'name' => $filename,
                'parents' => [$folderId],
            ]);

            $boundary = 'backup_boundary_'.bin2hex(random_bytes(8));
            $body = "--{$boundary}\r\n"
                ."Content-Type: application/json; charset=UTF-8\r\n\r\n"
                .$metadata."\r\n"
                ."--{$boundary}\r\n"
                ."Content-Type: {$mimeType}\r\n\r\n"
                .file_get_contents($filePath)."\r\n"
                ."--{$boundary}--";

            $response = Http::withToken($accessToken)
                ->withHeaders(['Content-Type' => "multipart/related; boundary={$boundary}"])
                ->withBody($body)
                ->timeout(300)
                ->post('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart&fields=id,name,size');

            if (! $response->successful()) {
                throw new \RuntimeException('Upload failed: '.$response->body());
            }

            $driveFile = $response->json();
            $this->info(sprintf('Uploaded to Google Drive: %s (ID: %s)', $driveFile['name'], $driveFile['id']));
            Log::info('Database backup uploaded to Google Drive', [
                'file' => $driveFile['name'],
                'drive_id' => $driveFile['id'],
                'size' => $driveFile['size'] ?? null,
            ]);

            // Clean up old backups on Google Drive
            $this->cleanOldDriveBackups($accessToken, $folderId, $retentionDays);

        } catch (\Throwable $e) {
            $this->error('Google Drive upload failed: '.$e->getMessage());
            Log::error('Google Drive backup upload failed', [
                'error' => $e->getMessage(),
                'file' => $filename,
            ]);
        }
    }

    /**
     * Generate a Google OAuth2 access token from service account credentials using JWT.
     */
    protected function getAccessToken(string $credentialsPath): string
    {
        $creds = json_decode(file_get_contents($credentialsPath), true);

        $header = $this->base64urlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));

        $now = time();
        $claimSet = $this->base64urlEncode(json_encode([
            'iss' => $creds['client_email'],
            'scope' => 'https://www.googleapis.com/auth/drive.file',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]));

        $signatureInput = "{$header}.{$claimSet}";

        $privateKey = openssl_pkey_get_private($creds['private_key']);
        if (! $privateKey) {
            throw new \RuntimeException('Invalid private key in service account credentials.');
        }

        openssl_sign($signatureInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        $encodedSignature = $this->base64urlEncode($signature);

        $jwt = "{$signatureInput}.{$encodedSignature}";

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Failed to get access token: '.$response->body());
        }

        return $response->json('access_token');
    }

    protected function cleanOldDriveBackups(string $accessToken, string $folderId, int $retentionDays): void
    {
        try {
            $cutoffDate = now()->subDays($retentionDays)->toRfc3339String();

            $query = urlencode("'{$folderId}' in parents and trashed = false and createdTime < '{$cutoffDate}'");
            $response = Http::withToken($accessToken)
                ->get("https://www.googleapis.com/drive/v3/files?q={$query}&fields=files(id,name,createdTime)&orderBy=createdTime asc");

            if (! $response->successful()) {
                $this->warn('Could not list old Drive backups: '.$response->body());

                return;
            }

            $files = $response->json('files', []);
            $deleted = 0;

            foreach ($files as $file) {
                $deleteResponse = Http::withToken($accessToken)
                    ->delete("https://www.googleapis.com/drive/v3/files/{$file['id']}");

                if ($deleteResponse->successful()) {
                    $this->line('  Removed old Drive backup: '.$file['name']);
                    $deleted++;
                }
            }

            if ($deleted > 0) {
                $this->info("Cleaned up {$deleted} old backup(s) from Google Drive (>{$retentionDays} days).");
            }
        } catch (\Throwable $e) {
            $this->warn('Could not clean old Drive backups: '.$e->getMessage());
        }
    }

    protected function cleanOldBackups(string $directory, int $keep): void
    {
        $files = array_merge(
            glob($directory.DIRECTORY_SEPARATOR.'*.sql') ?: [],
            glob($directory.DIRECTORY_SEPARATOR.'*.sql.gz') ?: [],
        );

        if (count($files) <= $keep) {
            return;
        }

        usort($files, fn (string $a, string $b) => filemtime($a) <=> filemtime($b));

        $toDelete = array_slice($files, 0, count($files) - $keep);

        foreach ($toDelete as $file) {
            unlink($file);
            $this->line('  Removed old local backup: '.basename($file));
        }
    }

    private function base64urlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
