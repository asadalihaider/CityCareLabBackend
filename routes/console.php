<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('expo:notifications:send')->everySixHours();
Schedule::command('expo:tickets:check')->monthly();
Schedule::command('otp:clean-expired')->monthly();
Schedule::command('queue:work --stop-when-empty --queue=default --tries=3')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->runInBackground();
