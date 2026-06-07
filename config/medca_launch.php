<?php

/**
 * Production launch configuration — populate from environment before go-live.
 */
return [
    'imports_path' => storage_path('imports/production'),

    'gtm_container_id' => env('MEDCA_GTM_CONTAINER_ID'),
    'ga4_measurement_id' => env('MEDCA_GA4_MEASUREMENT_ID'),
    'google_site_verification' => env('MEDCA_GSC_VERIFICATION'),
    'whatsapp_number' => env('MEDCA_WHATSAPP_NUMBER'),

    'import_order' => [
        'categories',
        'pincodes',
        'services',
        'sub_services',
        'geo',
    ],
];
