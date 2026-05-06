<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Webhook Manager — outbound events (PDF §4–6 alignment)
    |--------------------------------------------------------------------------
    | Wire receivers via Settings → Integrations (integration entry “Webhook”).
    | OutboundWebhookDispatcher POSTs JSON when domain events fire (see list below).
    */

    'webhook_events' => [
        ['key' => 'lead.created', 'description' => __('New inquiry captured (API or Operations).')],
        ['key' => 'page.published', 'description' => __('CMS page active / publicly reachable.')],
        ['key' => 'blog.published', 'description' => __('Blog post marked published.')],
        ['key' => 'navigation.updated', 'description' => __('Site Architect header/footer menus saved.')],
        ['key' => 'integration.test', 'description' => __('Manual test ping from Integrations workspace.')],
    ],

    /*
    |--------------------------------------------------------------------------
    | Super-admin operations token — Backup & Maintenance POST forms (PDF Settings)
    |--------------------------------------------------------------------------
    */
    'operations_token' => env('SETTINGS_OPERATIONS_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Laravel maintenance bypass secret — required before enabling maintenance UI
    |--------------------------------------------------------------------------
    | Visitors may bypass maintenance via /{secret} when using php artisan down.
    */
    'maintenance_bypass_secret' => env('SETTINGS_MAINTENANCE_BYPASS_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Optional scheduled SQLite backup (mom:backup-database)
    |--------------------------------------------------------------------------
    */
    'schedule_database_backup' => filter_var(env('SETTINGS_SCHEDULE_DATABASE_BACKUP', false), FILTER_VALIDATE_BOOLEAN),

];
