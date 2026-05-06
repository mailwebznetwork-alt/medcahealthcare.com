<?php

use App\Services\Growth\AiPulseService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:sync-google-business-reviews')->everyFourHours();

if (config('settings.schedule_database_backup')) {
    Schedule::command('mom:backup-database')->dailyAt('02:15');
}

if (config('growth.schedule_ai_pulse_daily')) {
    Schedule::call(function (): void {
        app(AiPulseService::class)->rebuildSnapshotCache(true);
    })
        ->dailyAt('03:33')
        ->name('ai-pulse-rebuild')
        ->withoutOverlapping();
}
