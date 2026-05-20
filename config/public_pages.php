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
    | Auto-match Site Architect page slug for /services/{code} when Operations
    | has no detail_page_id. Example: service-caregivers for code "caregivers".
    */
    'service_detail_page_slug_pattern' => env('SERVICES_DETAIL_PAGE_SLUG_PATTERN', 'service-{code}'),

    /*
    | Shared fallback page when neither detail_page_id nor pattern slug exists.
    | Blocks receive $service and the service token variable (e.g. $caregivers).
    */
    'service_detail_page_slug' => env('SERVICES_DETAIL_PAGE_SLUG', 'services-detail-template'),

];
