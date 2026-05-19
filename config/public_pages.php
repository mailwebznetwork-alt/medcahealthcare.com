<?php

/**
 * CMS pages served at clean root paths (no /p/ prefix).
 * Home is always served at / via the dedicated route.
 */
return [

    'root_slugs' => [
        'about-us',
        'careers',
        'contact',
        'locations',
        'services',
    ],

    /*
    | Shared Site Architect page for /services/{code} when Operations has no
    | per-service detail page. Blocks receive $service.
    */
    'service_detail_page_slug' => env('SERVICES_DETAIL_PAGE_SLUG', 'services-detail-template'),

];
