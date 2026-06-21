<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Catalog operations cascade
    |--------------------------------------------------------------------------
    |
    | Rules enforced by CatalogOperationsCascade (same PHP process as the save;
    | deferred until after the HTTP response — never detached shell workers).
    |
    | 1. Category GEO master save → propagate to primary-category services
    | 2. Affected services → reconcile service × country location pages
    | 3. Category → discovery + hub page sync
    | 4. Universal page registry refresh
    | 5. Service save → reconcile location matrix + registry
    | 6. Import commit → inline post-sync artisan commands (no background exec)
    | 7. Country delete → detach GEO pivots + tear down generated catalog pages
    |    (service/category/sub-service pages require active country coverage)
    |
    */
    'cascade_after_response' => env('CATALOG_CASCADE_AFTER_RESPONSE', true),
];
