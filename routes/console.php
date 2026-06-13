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

if (config('growth.schedule_backlink_refresh_daily')) {
    Schedule::job(new \App\Jobs\RefreshBacklinkIntelligenceJob())
        ->dailyAt('04:05')
        ->name('backlink-intelligence-refresh')
        ->withoutOverlapping();
}

if (config('marketing_automation.enabled', true)) {
    Schedule::job(new \App\Jobs\Marketing\AggregateMarketingAnalyticsJob())
        ->dailyAt(config('marketing_automation.analytics.aggregate_daily_at', '01:15'))
        ->name('marketing-analytics-aggregate')
        ->withoutOverlapping();

    Schedule::job(new \App\Jobs\Marketing\PurgeMarketingAnalyticsJob())
        ->weeklyOn(0, '05:30')
        ->name('marketing-analytics-retention')
        ->withoutOverlapping();
}

Schedule::command('medca:backup-health-report')
    ->weeklyOn(1, '05:45')
    ->name('medca-backup-health-weekly')
    ->withoutOverlapping();

Schedule::command('medca:post-launch-ops')
    ->monthlyOn(1, '06:00')
    ->name('medca-post-launch-ops-monthly')
    ->withoutOverlapping();
