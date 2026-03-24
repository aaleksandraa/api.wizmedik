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

Schedule::command(
    'seo:prerender-pages --output=' . escapeshellarg((string) config('app.sitemap_output_path'))
)
    ->dailyAt((string) config('app.seo_prerender_schedule_time', '03:10'))
    ->timezone((string) config('app.sitemap_schedule_timezone'))
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/seo-prerender-schedule.log'));

Schedule::command('backup:run --only-db')
    ->dailyAt('02:00')
    ->timezone((string) config('app.timezone', 'Europe/Sarajevo'))
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/database-backup-schedule.log'));

Schedule::command('backup:clean')
    ->dailyAt('02:30')
    ->timezone((string) config('app.timezone', 'Europe/Sarajevo'))
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/database-backup-schedule.log'));
