<?php

return [

    'enabled' => env('MARKETING_AUTOMATION_ENABLED', true),

    'attribution' => [
        'enabled' => env('MARKETING_ATTRIBUTION_ENABLED', true),
        'cookie_name' => 'medca_first_touch',
        'cookie_days' => (int) env('MARKETING_ATTRIBUTION_COOKIE_DAYS', 90),
        'session_key' => 'marketing.last_touch',
    ],

    'click_tracking' => [
        'enabled' => env('MARKETING_CLICK_TRACKING_ENABLED', true),
        'rate_limit_per_minute' => (int) env('MARKETING_CLICK_RATE_LIMIT', 120),
        'dedupe_seconds' => (int) env('MARKETING_CLICK_DEDUPE_SECONDS', 3),
    ],

    'analytics' => [
        'cache_ttl' => (int) env('MARKETING_ANALYTICS_CACHE_TTL', 900),
        'aggregate_daily_at' => env('MARKETING_AGGREGATE_DAILY_AT', '01:15'),
    ],

    'retention' => [
        'click_events_days' => (int) env('MARKETING_RETENTION_CLICK_EVENTS_DAYS', 365),
        'activities_days' => (int) env('MARKETING_RETENTION_ACTIVITIES_DAYS', 730),
        'archive_before_delete' => env('MARKETING_RETENTION_ARCHIVE', true),
    ],

    'reporting' => [
        'max_export_rows' => (int) env('MARKETING_REPORT_MAX_ROWS', 10000),
    ],

    /*
    |--------------------------------------------------------------------------
    | GA4 conversion event names (mark as conversions in GA4 Admin → Events)
    |--------------------------------------------------------------------------
    */
    'ga4_conversion_events' => [
        'phone_click',
        'whatsapp_click',
        'form_submit',
    ],

];
