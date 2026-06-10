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
    | Backup UI — operator display names (Settings → Backup)
    |--------------------------------------------------------------------------
    | Comma-separated user names (users.name), case-insensitive. Must also be
    | super_admin. Example: MOMJERRIE or MOMJERRIE,OpsLead
    */
    'backup_operator_names' => array_values(array_filter(array_map(
        static fn (string $part): string => strtolower(trim($part)),
        explode(',', (string) env('SETTINGS_BACKUP_OPERATOR_NAMES', 'MOMJERRIE'))
    ))),

    /*
    |--------------------------------------------------------------------------
    | Full-site zip — paths under project/ to skip (relative to base_path)
    |--------------------------------------------------------------------------
    | Comma-separated POSIX-style prefixes. storage/app/backups is always
    | skipped (recursion). Default excludes .git only; node_modules is
    | included so backups match typical server disk usage. Use e.g.
    | ".git,node_modules" for a smaller archive.
    */
    'site_backup_excluded_prefixes' => array_values(array_filter(array_map(
        static function (string $part): string {
            return trim(str_replace('\\', '/', $part), '/');
        },
        explode(',', (string) env('SETTINGS_SITE_BACKUP_EXCLUDED_PREFIXES', '.git'))
    ), static fn (string $prefix): bool => $prefix !== '' && $prefix !== '.' && $prefix !== '..')),

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

    /*
    |--------------------------------------------------------------------------
    | HMAC secret for POST /api/payments/notify (hex X-Payment-Signature header)
    |--------------------------------------------------------------------------
    | When set, VerifyPaymentIngestSignature middleware requires a valid
    | hash_hmac('sha256', raw_body, secret). Bearer token is optional extra.
    */
    'payment_ingest_hmac_secret' => env('SETTINGS_PAYMENT_INGEST_HMAC_SECRET'),

];
