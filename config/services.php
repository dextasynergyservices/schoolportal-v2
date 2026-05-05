<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
    ],

    'paystack' => [
        'secret_key' => env('PAYSTACK_SECRET_KEY'),
        'public_key' => env('PAYSTACK_PUBLIC_KEY'),
        'base_url' => env('PAYSTACK_BASE_URL', 'https://api.paystack.co'),
    ],

    'recaptcha' => [
        'site_key' => env('RECAPTCHA_SITE_KEY'),
        'secret_key' => env('RECAPTCHA_SECRET_KEY'),
        'threshold' => env('RECAPTCHA_THRESHOLD', 0.5),
    ],

    'google_drive_backup' => [
        'enabled' => env('GOOGLE_DRIVE_BACKUP_ENABLED', false),
        'credentials_path' => env('GOOGLE_DRIVE_CREDENTIALS_PATH', 'google-drive-backup.json'),
        'folder_id' => env('GOOGLE_DRIVE_BACKUP_FOLDER_ID'),
        'retention_days' => env('GOOGLE_DRIVE_BACKUP_RETENTION_DAYS', 14),
    ],

    'backup' => [
        'alert_email' => env('BACKUP_ALERT_EMAIL'),
        'keep_local' => env('BACKUP_KEEP_LOCAL', 7),
    ],

    'platform' => [
        // Email address that receives alerts for critical system events (queue failures, etc.).
        // Falls back to BACKUP_ALERT_EMAIL if PLATFORM_ALERT_EMAIL is not set.
        'alert_email' => env('PLATFORM_ALERT_EMAIL', env('BACKUP_ALERT_EMAIL')),
    ],

];
