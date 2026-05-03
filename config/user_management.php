<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Profile read-only in User Management
    |--------------------------------------------------------------------------
    |
    | Comma-separated lists (case-insensitive). Matching users cannot be edited,
    | activated/deactivated, or deleted from User Management, and are omitted from
    | the User Management directory for every viewer. The configured root super
    | administrator is always listed and never treated as read-only here; root
    | rules apply separately via RootAccount.
    |
    */
    'profile_readonly_emails' => array_values(array_filter(array_unique(array_map(
        static fn (string $e): string => strtolower(trim($e)),
        explode(',', (string) env('USER_MANAGEMENT_PROFILE_READONLY_EMAILS', 'mail.webznetwork@gmail.com'))
    )))),

    'profile_readonly_names' => array_values(array_filter(array_unique(array_map(
        static fn (string $n): string => strtolower(trim($n)),
        explode(',', (string) env('USER_MANAGEMENT_PROFILE_READONLY_NAMES', 'WDJERRIE'))
    )))),

];
