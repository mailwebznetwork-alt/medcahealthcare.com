<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Admin authority governance
    |--------------------------------------------------------------------------
    |
    | Database is authoritative. Registry, pages, and cache follow database.
    | Automated processes must never recreate records marked deleted_by_admin.
    |
    */

    'enforce_admin_authority' => env('GOVERNANCE_ENFORCE_ADMIN_AUTHORITY', true),

    'audit_automated_writes' => env('GOVERNANCE_AUDIT_AUTOMATED_WRITES', true),

    'block_sync_restore_trashed' => env('GOVERNANCE_BLOCK_SYNC_RESTORE_TRASHED', false),

    'global_content_cache_ttl' => env('GOVERNANCE_GLOBAL_CONTENT_CACHE_TTL', 0),

];
