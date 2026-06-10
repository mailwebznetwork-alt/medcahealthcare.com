<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Auto-sync catalog under Services menu
    |--------------------------------------------------------------------------
    |
    | When enabled, the public header "Services" dropdown is built automatically
    | from active categories, their services, and sub-services. Manual header
    | items (Home, About, Locations, etc.) are unchanged.
    |
    */
    'auto_sync_catalog_under_services' => env('NAV_AUTO_SYNC_CATALOG', true),

    /*
    | Page slugs that identify the Services anchor in the manual header tree.
    */
    'services_page_slugs' => ['services'],

    /*
    | Include sub-services as third-level items under each service.
    */
    'include_sub_services' => env('NAV_INCLUDE_SUB_SERVICES', true),

    /*
    | Zones where catalog auto-sync applies (header only by default).
    */
    'catalog_sync_zones' => ['header'],

];
