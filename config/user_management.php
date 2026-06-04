<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Profile read-only in User Management
    |--------------------------------------------------------------------------
    |
    | Comma-separated lists (case-insensitive). Matching users cannot be edited,
    | activated/deactivated, or deleted from User Management, and are omitted from
    | the User Management directory for every viewer. Root super-admin rules
    | (edit protection, full access) still apply via RootAccount.
    |
    | hide_root_account_in_directory — when true, the root account is omitted
    | from the User Management table (default: true).
    |
    */
    'hide_root_account_in_directory' => filter_var(
        env('USER_MANAGEMENT_HIDE_ROOT_ACCOUNT', true),
        FILTER_VALIDATE_BOOLEAN
    ),

    /*
    |--------------------------------------------------------------------------
    | Profile read-only in User Management (continued)
    |--------------------------------------------------------------------------
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
