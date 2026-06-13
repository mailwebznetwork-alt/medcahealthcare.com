<?php

return [

    'cache_enabled' => env('SITEMAP_CACHE_ENABLED', true),

    'queue_enabled' => env('SITEMAP_QUEUE_ENABLED', true),

    'paginated_enabled' => env('SITEMAP_PAGINATED_ENABLED', true),

    'cache_disk' => env('SITEMAP_CACHE_DISK', 'local'),

    'cache_directory' => env('SITEMAP_CACHE_DIRECTORY', 'sitemaps'),

    'location_chunk_size' => (int) env('SITEMAP_LOCATION_CHUNK_SIZE', 10000),

    /** Application cache TTL for sitemap index read (seconds) */
    'application_cache_ttl' => (int) env('SITEMAP_APPLICATION_CACHE_TTL', 600),
];
