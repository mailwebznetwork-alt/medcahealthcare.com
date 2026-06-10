<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Legacy service_code aliases
    |--------------------------------------------------------------------------
    |
    | Maps stale {{service:token}} values to the current catalog service_code.
    | Populated via import migrations or manual ops — never use display titles.
    |
    */
    'aliases' => [
        // 'elder-care' => 'SRV-CAREGIVER-1',
    ],

    /*
    |--------------------------------------------------------------------------
    | Catalog fallback for service-picker blocks
    |--------------------------------------------------------------------------
    |
    | When every token in a block fails to resolve, load live public services
    | from the database so the section stays visible after catalog imports.
    |
    */
    'catalog_fallback' => [
        'services-block-carousel' => [
            'limit' => 6,
            'prefer_featured' => true,
        ],
        'services-block-grid' => [
            'limit' => 12,
            'prefer_featured' => true,
        ],
    ],

];
