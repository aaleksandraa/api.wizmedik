<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(
    'sitemap:generate --output=' . escapeshellarg((string) config('app.sitemap_output_path'))
)
    ->dailyAt((string) config('app.sitemap_schedule_time'))
    ->timezone((string) config('app.sitemap_schedule_timezone'))
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/sitemap-schedule.log'));
