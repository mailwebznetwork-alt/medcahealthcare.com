<?php

return [

    'cache_enabled' => env('SITEMAP_CACHE_ENABLED', true),

    'queue_enabled' => env('SITEMAP_QUEUE_ENABLED', true),

    'paginated_enabled' => env('SITEMAP_PAGINATED_ENABLED', true),

    'cache_disk' => env('SITEMAP_CACHE_DISK', 'local'),

    'cache_directory' => env('SITEMAP_CACHE_DIRECTORY', 'sitemaps'),

    'location_chunk_size' => (int) env('SITEMAP_LOCATION_CHUNK_SIZE', 10000),

];
