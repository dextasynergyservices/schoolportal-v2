<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── Scheduled Tasks ──

// Process queued jobs every minute (emails, notifications)
// Required for shared hosting where persistent queue workers can't run
Schedule::command('queue:work --stop-when-empty --max-time=55')->everyMinute()->withoutOverlapping();

// Daily database backup at 2:00 AM
Schedule::command('db:backup')->dailyAt('02:00')->onOneServer();

// Reset free AI credits on the 1st of each month at midnight
Schedule::command('credits:reset-free')->monthlyOn(1, '00:00')->onOneServer();

// Send weekly digest emails to parents every Monday at 7:00 AM
Schedule::command('digest:parents-weekly')->weeklyOn(1, '07:00')->onOneServer();

// Process queued jobs
Schedule::command('queue:work --stop-when-empty --max-time=300')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onOneServer();
