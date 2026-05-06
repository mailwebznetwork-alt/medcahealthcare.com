<?php

return [
    /*
    |--------------------------------------------------------------------------
    | GA4 Data API cache (seconds)
    |--------------------------------------------------------------------------
    */
    'ga4_cache_ttl' => (int) env('MARKETING_GA4_CACHE_TTL', 3600),

    /*
    |--------------------------------------------------------------------------
    | Optional: path to Google Cloud service account JSON for GA4 Data API.
    | Falls back to GOOGLE_APPLICATION_CREDENTIALS when unset.
    |--------------------------------------------------------------------------
    */
    'ga4_credentials_path' => env('MARKETING_GA4_CREDENTIALS_PATH'),

    /*
    |--------------------------------------------------------------------------
    | Google Ads API (optional partial integration)
    |--------------------------------------------------------------------------
    */
    'google_ads_developer_token' => env('GOOGLE_ADS_DEVELOPER_TOKEN'),
    'google_ads_customer_id' => env('GOOGLE_ADS_CUSTOMER_ID'),
    'google_ads_refresh_token' => env('GOOGLE_ADS_REFRESH_TOKEN'),
    'google_ads_client_id' => env('GOOGLE_ADS_CLIENT_ID'),
    'google_ads_client_secret' => env('GOOGLE_ADS_CLIENT_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Meta Marketing API (optional partial integration)
    |--------------------------------------------------------------------------
    */
    'meta_access_token' => env('META_MARKETING_ACCESS_TOKEN'),
    'meta_ad_account_id' => env('META_AD_ACCOUNT_ID'),

    /*
    |--------------------------------------------------------------------------
    | External dashboards (links only — not duplicate products)
    |--------------------------------------------------------------------------
    */
    'ga4_dashboard_url' => env('MARKETING_GA4_DASHBOARD_URL', 'https://analytics.google.com/'),

    'insights' => [
        'cost_per_lead_warn' => (float) env('MARKETING_INSIGHT_COST_PER_LEAD_WARN', 500),
        'whatsapp_click_ratio_high' => (float) env('MARKETING_WHATSAPP_CLICK_RATIO_HIGH', 0.15),
        /** Dominant channel share (e.g. Organic Search ≥ 62%) — diversification hint */
        'channel_share_warn' => (float) env('MARKETING_INSIGHT_CHANNEL_SHARE_WARN', 0.62),
        /** Engagement rate % below this triggers an on-page relevance hint */
        'engagement_rate_warn_pct' => (float) env('MARKETING_INSIGHT_ENGAGEMENT_WARN_PCT', 42),
    ],
];
