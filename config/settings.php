<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Webhook Manager — outbound events
    |--------------------------------------------------------------------------
    | Configure receivers under Settings → Webhooks (multiple endpoints + logs).
    | Legacy: Settings → Integrations entry “Webhook” is used when no endpoint
    | subscribes to an event.
    */

    /*
    | Do not call __() here — config loads before the translator is registered.
    | Wrap descriptions with __('…') in Blade when displaying.
    */
    'webhook_events' => [
        ['key' => 'lead.created', 'description' => 'New inquiry captured (API or Operations).'],
        ['key' => 'job.application.submitted', 'description' => 'Careers job application stored.'],
        ['key' => 'service.booked', 'description' => 'Service booking / lead pipeline event (when wired).'],
        ['key' => 'contact.form.submitted', 'description' => 'Public contact form submission (when wired).'],
        ['key' => 'user.registered', 'description' => 'New staff user invited or registered (when wired).'],
        ['key' => 'page.published', 'description' => 'CMS page active / publicly reachable.'],
        ['key' => 'blog.published', 'description' => 'Blog post marked published.'],
        ['key' => 'payment.received', 'description' => 'Payment captured (when wired).'],
        ['key' => 'navigation.updated', 'description' => 'Site Architect header/footer menus saved.'],
        ['key' => 'integration.test', 'description' => 'Manual test ping from Webhooks workspace.'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Super-admin operations token — Backup & Maintenance POST forms
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

    /*
    |--------------------------------------------------------------------------
    | Webhook dispatch mode — queue jobs vs synchronous HTTP
    |--------------------------------------------------------------------------
    */
    'webhooks' => [
        'async_dispatch' => filter_var(env('SETTINGS_WEBHOOKS_ASYNC', true), FILTER_VALIDATE_BOOLEAN),
    ],

    /*
    |--------------------------------------------------------------------------
    | Bearer token for POST /api/payments/notify (external payment bridges)
    |--------------------------------------------------------------------------
    */
    'payment_ingest_bearer' => env('SETTINGS_PAYMENT_INGEST_BEARER'),

];
