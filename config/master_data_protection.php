<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Master Data Protection (Emergency Containment)
    |--------------------------------------------------------------------------
    |
    | When enabled, blocks automated master-data writes from seeders, populate
    | commands, and bulk imports. Operations UI and authorized admin imports
    | remain available.
    |
    */
    'enabled' => (bool) env('MASTER_DATA_PROTECTION_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Country workbook import upsert
    |--------------------------------------------------------------------------
    |
    | pincodes.xlsx workbook imports ALWAYS update existing rows (see
    | MasterDataProtection::countryWorkbookUpsertEnabled). This env var only
    | affects single-entity country file uploads (legacy CSV path).
    |
    | Production defaults to false (skip duplicates). Local unchanged when null.
    | Staging follows country_upsert_in_staging when APP_ENV=staging.
    |
    */
    'country_upsert_default' => env('MASTER_DATA_PINCODE_UPSERT_DEFAULT'),

    'country_upsert_in_staging' => (bool) env('MASTER_DATA_PINCODE_UPSERT_STAGING', false),

];
