<?php

/**
 * Phase 2 — Page generation, discovery, SEO/GEO/AEO expansion.
 */
return [

    'category_page_slug_pattern' => env('PHASE2_CATEGORY_PAGE_SLUG', 'category-{code}'),

    'category_page_content' => "{{block:category-discovery-hero}}\n{{block:category-services-list}}\n{{block:category-areas-served}}",

    'sub_service_page_slug_pattern' => env('PHASE2_SUB_SERVICE_PAGE_SLUG', 'service-{code}-sub-{sub}'),

    'sub_service_page_content' => '{{block:sub-service-detail-hero}}',

    'sub_service_public_path_pattern' => '/services/{code}/sub/{sub}',

    'auto_sync_category_pages' => env('PHASE2_AUTO_SYNC_CATEGORY_PAGES', true),

    'auto_sync_sub_service_pages' => env('PHASE2_AUTO_SYNC_SUB_SERVICE_PAGES', true),

    'display' => [
        'homepage_category_limit' => 6,
        'homepage_service_limit' => 8,
        'featured_limit' => 6,
        'top_rated_limit' => 6,
        'related_categories_limit' => 4,
        'related_sub_services_limit' => 8,
    ],

    'top_rated' => [
        'min_reviews' => 3,
        'min_rating' => 4.5,
    ],

];
