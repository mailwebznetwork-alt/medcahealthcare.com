<?php

return [
    'enabled' => filter_var(env('PUBLIC_CACHE_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

    'store' => env('PUBLIC_CACHE_STORE', env('CACHE_STORE', 'database')),

    /** Seconds — document meta, related content cards */
    'ttl' => (int) env('PUBLIC_CACHE_TTL', 3600),

    'prefix' => 'medca_public',
];
