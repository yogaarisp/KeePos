<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Run every day at 08:00 WIB (01:00 UTC)
Schedule::command('subscriptions:send-expiry-reminders')
    ->dailyAt('01:00')
    ->withoutOverlapping()
    ->runInBackground();
