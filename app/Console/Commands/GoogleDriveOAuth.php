<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class GoogleDriveOAuth extends Command
{
    protected $signature = 'google-drive:oauth
        {code? : Authorization code copied from the Google redirect URL}
        {--url : Only show the Google authorization URL}';

    protected $description = 'Generate a Google Drive OAuth URL or exchange an authorization code for a refresh token';

    public function handle(): int
    {
        $clientId = config('services.google_drive_backup.oauth_client_id');
        $clientSecret = config('services.google_drive_backup.oauth_client_secret');
        $redirectUri = config('services.google_drive_backup.oauth_redirect_uri', 'http://localhost');

        if (! $clientId || ! $clientSecret) {
            $this->error('Set GOOGLE_DRIVE_OAUTH_CLIENT_ID and GOOGLE_DRIVE_OAUTH_CLIENT_SECRET first.');

            return self::FAILURE;
        }

        $code = $this->argument('code');

        if ($this->option('url') || ! $code) {
            $this->info('Open this URL in your browser, approve access, then copy the code from the redirect URL:');
            $this->newLine();
            $this->line($this->authorizationUrl((string) $clientId, (string) $redirectUri));
            $this->newLine();
            $this->line('Then run: php artisan google-drive:oauth "PASTE_CODE_HERE"');

            return self::SUCCESS;
        }

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $redirectUri,
        ]);

        if (! $response->successful()) {
            $this->error('Could not exchange authorization code: '.$response->body());

            return self::FAILURE;
        }

        $refreshToken = $response->json('refresh_token');

        if (! $refreshToken) {
            $this->error('Google did not return a refresh token.');
            $this->line('Generate a fresh URL with php artisan google-drive:oauth --url and approve again.');
            $this->line('If it still happens, remove the app from your Google Account permissions and retry.');

            return self::FAILURE;
        }

        $this->info('Add this to production .env:');
        $this->newLine();
        $this->line('GOOGLE_DRIVE_OAUTH_REFRESH_TOKEN='.$refreshToken);

        return self::SUCCESS;
    }

    private function authorizationUrl(string $clientId, string $redirectUri): string
    {
        return 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/drive.file',
            'access_type' => 'offline',
            'prompt' => 'consent',
        ]);
    }
}
