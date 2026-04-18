<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── Scheduled Tasks ──

// Daily database backup at 2:00 AM
Schedule::command('db:backup')->dailyAt('02:00')->onOneServer();

// Reset free AI credits on the 1st of each month at midnight
Schedule::command('credits:reset-free')->monthlyOn(1, '00:00')->onOneServer();

// Process queued jobs
Schedule::command('queue:work --stop-when-empty --max-time=300')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onOneServer();
