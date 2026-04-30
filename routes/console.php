<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── Scheduled Tasks ──

// Process queued jobs every 5 minutes (emails, notifications)
// Required for shared hosting where persistent queue workers can't run
Schedule::command('queue:work --stop-when-empty --max-time=240')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onOneServer();

// Daily database backup at 2:00 AM
Schedule::command('db:backup')->dailyAt('02:00')->onOneServer();

// Reset free AI credits on the 1st of each month at midnight
Schedule::command('credits:reset-free')->monthlyOn(1, '00:00')->onOneServer();

// Send weekly digest emails to parents every Monday at 7:00 AM
Schedule::command('digest:parents-weekly')->weeklyOn(1, '07:00')->onOneServer();

// Send 24-hour deadline reminders for quizzes and exams every day at 8:00 AM
Schedule::command('reminders:send-deadlines')->dailyAt('08:00')->onOneServer();
