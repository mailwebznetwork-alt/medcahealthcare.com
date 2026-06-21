<?php

/**
 * Services Module — single source of truth for SEO, AEO, GEO, schema, and auto-pages.
 */
return [

    'location_page_slug_pattern' => env('SERVICES_LOCATION_PAGE_SLUG_PATTERN', 'service-{code}-loc-{country}'),

    'location_page_title_pattern' => env('SERVICES_LOCATION_PAGE_TITLE_PATTERN', '{service} in {area}'),

    'location_page_content' => '{{block:location-geo-enrichment}}',

    'web_page_slugs' => [
        'home',
        'about-us',
        'contact',
        'careers',
        'locations',
        'services',
        'privacy-policy',
        'terms-and-conditions',
        'terms',
    ],

    'blog_slug_prefix' => 'blog',

    'landing_slug_prefixes' => [
        'lp-',
        'landing-',
        'campaign-',
    ],

    'schema_types' => [
        'ProfessionalService',
        'LocalBusiness',
        'Service',
        'FAQPage',
        'BreadcrumbList',
        'Organization',
    ],

    'auto_sync_on_save' => env('SERVICES_MASTER_AUTO_SYNC', true),

    'gemini_suggestions' => env('SERVICES_MASTER_GEMINI_SUGGESTIONS', true),

    /** When true, location URLs use /services/{code}/{city}/{country} instead of /services/{code}/{area-slug}. */
    'public_url_include_country' => env('SERVICES_LOCATION_URL_WITH_PINCODE', false),

    'internal_links' => [
        'related_services_limit' => 4,
        'related_locations_limit' => 6,
    ],

    'html_sitemap' => [
        'per_page' => 50,
    ],

    'quality_thresholds' => [
        'composite_min' => (int) env('LOCATION_QUALITY_COMPOSITE_MIN', 40),
        'content_uniqueness_min' => (int) env('LOCATION_QUALITY_CONTENT_MIN', 35),
        'geo_readiness_min' => (int) env('LOCATION_QUALITY_GEO_MIN', 35),
    ],

    'country_expansion' => [
        'city_filter' => env('LOCATION_EXPANSION_CITY'),
        'require_serviceable' => env('LOCATION_EXPANSION_REQUIRE_SERVICEABLE', true),
        'require_active' => env('LOCATION_EXPANSION_REQUIRE_ACTIVE', true),
    ],

    /**
     * Canonical SEO ownership — Operations service_seo is master; Growth layer mirror disabled by default.
     */
    'seo_ownership' => [
        'operations_canonical' => env('SERVICES_SEO_OPERATIONS_CANONICAL', true),
        'mirror_to_growth_layer' => env('SERVICES_SEO_MIRROR_GROWTH', false),
    ],

    'category_discovery' => [
        'auto_sync' => env('CATEGORY_DISCOVERY_AUTO_SYNC', true),
    ],

];
