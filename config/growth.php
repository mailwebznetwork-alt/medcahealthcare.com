<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Pulse — heuristic speed score (0–100) until PageSpeed API is wired
    |--------------------------------------------------------------------------
    */
    'ai_pulse_speed_baseline' => (int) env('AI_PULSE_SPEED_BASELINE', 72),

    /*
    |--------------------------------------------------------------------------
    | Google PageSpeed Insights API key (same GCP project as other APIs is fine)
    |--------------------------------------------------------------------------
    */
    'pagespeed_api_key' => env('GOOGLE_PAGESPEED_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | URL to score (defaults to APP_URL home). Use your primary landing URL.
    |--------------------------------------------------------------------------
    */
    'pagespeed_target_url' => env('AI_PULSE_PAGESPEED_URL'),

    /** Cache PageSpeed responses (seconds) — Lighthouse runs are expensive. */
    'pagespeed_cache_ttl' => (int) env('AI_PULSE_PAGESPEED_CACHE_TTL', 21600),

    /*
    |--------------------------------------------------------------------------
    | Daily rebuild of AI Pulse snapshot (queue worker runs the job)
    |--------------------------------------------------------------------------
    */
    'schedule_ai_pulse_daily' => filter_var(env('AI_PULSE_SCHEDULE_DAILY', true), FILTER_VALIDATE_BOOLEAN),

    /*
    |--------------------------------------------------------------------------
    | Auto-fill SEO / AEO when saving pages, blogs, and services
    |--------------------------------------------------------------------------
    | When enabled, empty fields get meta titles, descriptions, AEO Q&A,
    | JSON-LD stubs, sync page_seo + page_elements, and refresh seo_ai_signals.
    | Gemini is used only when gemini.api_key is set and content_seo_gemini is true.
    */
    'content_seo_auto_fill' => filter_var(env('GROWTH_CONTENT_SEO_AUTO_FILL', true), FILTER_VALIDATE_BOOLEAN),

    'content_seo_fill_only_empty' => filter_var(env('GROWTH_CONTENT_SEO_FILL_ONLY_EMPTY', true), FILTER_VALIDATE_BOOLEAN),

    'content_seo_gemini' => filter_var(env('GROWTH_CONTENT_SEO_GEMINI', true), FILTER_VALIDATE_BOOLEAN),

    /*
    |--------------------------------------------------------------------------
    | Autonomous content + backlink intelligence
    |--------------------------------------------------------------------------
    */
    'schedule_backlink_refresh_daily' => filter_var(env('GROWTH_BACKLINK_SCHEDULE_DAILY', true), FILTER_VALIDATE_BOOLEAN),

    'backlink_monitor' => [
        'api_url' => env('BACKLINK_MONITOR_API_URL'),
        'api_key' => env('BACKLINK_MONITOR_API_KEY'),
        'timeout' => (int) env('BACKLINK_MONITOR_TIMEOUT', 15),
        'stale_hours' => (int) env('BACKLINK_MONITOR_STALE_HOURS', 168),
        'citation_domains' => [
            [
                'domain' => 'practo.com',
                'probe_url_template' => 'https://www.practo.com/search?q={brand}',
                'assume_for_intercept_targets' => true,
            ],
            [
                'domain' => 'justdial.com',
                'probe_url_template' => 'https://www.justdial.com/search?q={brand}',
                'assume_for_intercept_targets' => true,
            ],
            [
                'domain' => 'sulekha.com',
                'assume_for_intercept_targets' => false,
            ],
            [
                'domain' => 'healthgrades.com',
                'assume_for_intercept_targets' => false,
            ],
        ],
    ],

    'google_search_console' => [
        'access_token' => env('MEDCA_GSC_ACCESS_TOKEN'),
        'site_url' => env('MEDCA_GSC_SITE_URL', env('APP_URL')),
    ],
];
