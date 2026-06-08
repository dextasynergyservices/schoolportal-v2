<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Notifications\BackupFailedNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Process;
use Sentry\Severity;
use Sentry\State\Scope;

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
            $this->reportToSentry('Database backup dump failed', [
                'database' => $database,
                'error' => $result->errorOutput(),
            ]);
            $this->notifyFailure($result->errorOutput(), $database);

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
        $uploadFailed = false;

        if (! $this->option('skip-upload') && config('services.google_drive_backup.enabled')) {
            $uploadFailed = ! $this->uploadToGoogleDrive($uploadFile, $uploadFilename);
        } elseif (! config('services.google_drive_backup.enabled')) {
            $this->warn('Google Drive backup is disabled. Set GOOGLE_DRIVE_BACKUP_ENABLED=true to enable.');
        }

        // Step 4: Clean up old local backups (configurable retention)
        $this->cleanOldBackups($directory, (int) config('services.backup.keep_local', 7));

        if ($uploadFailed) {
            $this->error('Local backup was created, but Google Drive upload failed.');
            Log::warning('Database backup created locally, but Google Drive upload failed', [
                'file' => $uploadFilename,
                'path' => $uploadFile,
                'size' => filesize($uploadFile),
            ]);
            $this->reportToSentry('Database backup created locally, but Google Drive upload failed', [
                'database' => $database,
                'file' => $uploadFilename,
                'path' => $uploadFile,
                'size' => filesize($uploadFile),
            ]);

            return self::FAILURE;
        }

        $this->info('Backup completed successfully.');
        Log::info('Database backup completed', ['file' => $uploadFilename, 'size' => filesize($uploadFile)]);

        return self::SUCCESS;
    }

    protected function uploadToGoogleDrive(string $filePath, string $filename): bool
    {
        $authMode = strtolower((string) config('services.google_drive_backup.auth', 'service_account'));
        $credentialsPath = $this->resolveGoogleDriveCredentialsPath();
        $folderId = config('services.google_drive_backup.folder_id');
        $retentionDays = (int) config('services.google_drive_backup.retention_days', 14);

        if ($authMode === 'service_account' && ! file_exists($credentialsPath)) {
            $this->error("Google Drive credentials file not found: {$credentialsPath}");
            Log::error('Google Drive backup failed: credentials file not found', ['path' => $credentialsPath]);
            $this->reportToSentry('Google Drive backup credentials file not found', [
                'path' => $credentialsPath,
            ]);
            $this->notifyFailure("Google Drive credentials file not found: {$credentialsPath}", config('database.connections.mysql.database', 'unknown'));

            return false;
        }

        if (! $folderId) {
            $this->error('GOOGLE_DRIVE_BACKUP_FOLDER_ID is not set.');
            Log::error('Google Drive backup failed: folder ID not configured');
            $this->reportToSentry('Google Drive backup folder ID is not configured');
            $this->notifyFailure('GOOGLE_DRIVE_BACKUP_FOLDER_ID is not set.', config('database.connections.mysql.database', 'unknown'));

            return false;
        }

        try {
            $accessToken = $this->getAccessToken();

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
                ->withBody($body, "multipart/related; boundary={$boundary}")
                ->timeout(300)
                ->post('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart&supportsAllDrives=true&fields=id,name,size,mimeType');

            if (! $response->successful()) {
                throw new \RuntimeException('Upload failed: '.$response->body());
            }

            $driveFile = $response->json();

            if (($driveFile['name'] ?? null) !== $filename) {
                throw new \RuntimeException(sprintf(
                    'Upload completed, but Google Drive saved the file as "%s" instead of "%s".',
                    $driveFile['name'] ?? 'unknown',
                    $filename,
                ));
            }

            $this->info(sprintf('Uploaded to Google Drive: %s (ID: %s)', $driveFile['name'], $driveFile['id']));
            Log::info('Database backup uploaded to Google Drive', [
                'file' => $driveFile['name'],
                'drive_id' => $driveFile['id'],
                'size' => $driveFile['size'] ?? null,
                'mime_type' => $driveFile['mimeType'] ?? null,
            ]);

            // Clean up old backups on Google Drive
            $this->cleanOldDriveBackups($accessToken, $folderId, $retentionDays);

            return true;
        } catch (\Throwable $e) {
            $this->error('Google Drive upload failed: '.$e->getMessage());
            Log::error('Google Drive backup upload failed', [
                'error' => $e->getMessage(),
                'file' => $filename,
            ]);
            $this->reportToSentry('Google Drive backup upload failed', [
                'file' => $filename,
            ], $e);
            $this->notifyFailure('Google Drive upload failed: '.$e->getMessage(), config('database.connections.mysql.database', 'unknown'));

            return false;
        }
    }

    /**
     * Generate a Google OAuth2 access token for the configured backup auth mode.
     */
    protected function getAccessToken(): string
    {
        $authMode = strtolower((string) config('services.google_drive_backup.auth', 'service_account'));

        if ($authMode === 'oauth') {
            return $this->getOAuthAccessToken();
        }

        if ($authMode !== 'service_account') {
            throw new \RuntimeException("Unsupported Google Drive backup auth mode: {$authMode}");
        }

        return $this->getServiceAccountAccessToken($this->resolveGoogleDriveCredentialsPath());
    }

    /**
     * Generate a Google OAuth2 access token from service account credentials using JWT.
     */
    protected function getServiceAccountAccessToken(string $credentialsPath): string
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

    /**
     * Generate a Google OAuth2 access token from a personal Google account refresh token.
     */
    protected function getOAuthAccessToken(): string
    {
        $clientId = config('services.google_drive_backup.oauth_client_id');
        $clientSecret = config('services.google_drive_backup.oauth_client_secret');
        $refreshToken = config('services.google_drive_backup.oauth_refresh_token');

        if (! $clientId || ! $clientSecret || ! $refreshToken) {
            throw new \RuntimeException('Google Drive OAuth is missing GOOGLE_DRIVE_OAUTH_CLIENT_ID, GOOGLE_DRIVE_OAUTH_CLIENT_SECRET, or GOOGLE_DRIVE_OAUTH_REFRESH_TOKEN.');
        }

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Failed to refresh Google Drive OAuth access token: '.$response->body());
        }

        return $response->json('access_token');
    }

    private function resolveGoogleDriveCredentialsPath(): string
    {
        $credentialsPath = (string) config('services.google_drive_backup.credentials_path');

        if (! str_starts_with($credentialsPath, '/') && ! str_contains($credentialsPath, ':')) {
            return storage_path('app/'.$credentialsPath);
        }

        return $credentialsPath;
    }

    protected function cleanOldDriveBackups(string $accessToken, string $folderId, int $retentionDays): void
    {
        try {
            $cutoffDate = now()->subDays($retentionDays)->toRfc3339String();

            $query = urlencode("'{$folderId}' in parents and trashed = false and createdTime < '{$cutoffDate}'");
            $response = Http::withToken($accessToken)
                ->get("https://www.googleapis.com/drive/v3/files?q={$query}&supportsAllDrives=true&includeItemsFromAllDrives=true&fields=files(id,name,createdTime)&orderBy=createdTime asc");

            if (! $response->successful()) {
                $this->warn('Could not list old Drive backups: '.$response->body());

                return;
            }

            $files = $response->json('files', []);
            $deleted = 0;

            foreach ($files as $file) {
                $deleteResponse = Http::withToken($accessToken)
                    ->delete("https://www.googleapis.com/drive/v3/files/{$file['id']}?supportsAllDrives=true");

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

    /**
     * Send a failure alert email if BACKUP_ALERT_EMAIL is configured.
     */
    private function notifyFailure(string $reason, string $database): void
    {
        $alertEmail = config('services.backup.alert_email');

        if (! $alertEmail) {
            return;
        }

        try {
            Notification::route('mail', $alertEmail)
                ->notify(new BackupFailedNotification($reason, $database));
        } catch (\Throwable $e) {
            Log::error('Failed to send backup failure notification', ['error' => $e->getMessage()]);
            $this->reportToSentry('Failed to send backup failure notification', [
                'database' => $database,
                'reason' => $reason,
            ], $e);
        }
    }

    /**
     * Report handled backup failures to Sentry when the SDK is available.
     */
    private function reportToSentry(string $message, array $context = [], ?\Throwable $exception = null): void
    {
        if (! function_exists('Sentry\captureMessage')) {
            return;
        }

        try {
            \Sentry\configureScope(function (Scope $scope) use ($context): void {
                $scope->setTag('area', 'database_backup');
                $scope->setContext('backup', $context);
            });

            if ($exception && function_exists('Sentry\captureException')) {
                \Sentry\captureException($exception);

                return;
            }

            \Sentry\captureMessage($message, Severity::error());
        } catch (\Throwable $sentryError) {
            Log::debug('Could not report backup failure to Sentry', [
                'error' => $sentryError->getMessage(),
                'message' => $message,
            ]);
        }
    }
}
