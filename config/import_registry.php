<?php

/**
 * Bulk import entity registry — database becomes master source.
 */
return [
    'entities' => [
        'categories' => ['status' => 'implemented', 'importer' => \App\Services\Import\CategoryEntityImporter::class, 'formats' => ['csv', 'xls', 'xlsx']],
        'services' => ['status' => 'implemented', 'importer' => \App\Services\Import\ServiceEntityImporter::class, 'formats' => ['csv', 'xls', 'xlsx']],
        'sub_services' => ['status' => 'implemented', 'importer' => \App\Services\Import\SubServiceEntityImporter::class, 'formats' => ['csv', 'xls', 'xlsx']],
        'pincodes' => ['status' => 'implemented', 'importer' => \App\Services\Import\PinCodeEntityImporter::class, 'formats' => ['csv', 'xls', 'xlsx']],
        'locations' => ['status' => 'planned', 'formats' => ['csv', 'xlsx']],
        'mappings' => ['status' => 'implemented', 'importer' => \App\Services\Import\MappingEntityImporter::class, 'formats' => ['csv', 'xls', 'xlsx']],
        'seo' => ['status' => 'planned', 'formats' => ['csv', 'xlsx'], 'note' => 'Use entity imports with SEO columns'],
        'geo' => ['status' => 'implemented', 'importer' => \App\Services\Import\GeoEnrichmentEntityImporter::class, 'formats' => ['csv', 'xls', 'xlsx']],
        'aeo' => ['status' => 'planned', 'formats' => ['csv', 'xlsx'], 'note' => 'Use faq_pairs column on entity imports'],
        'schema' => ['status' => 'planned', 'formats' => ['json', 'csv'], 'note' => 'Generated via page provisioners post-import'],
        'meta' => ['status' => 'planned', 'formats' => ['csv', 'xlsx'], 'note' => 'Use SEO columns on entity imports'],
        'faq' => ['status' => 'planned', 'formats' => ['csv', 'xlsx'], 'note' => 'Use faq_pairs column on entity imports'],
        'reviews' => ['status' => 'planned', 'formats' => ['csv', 'xlsx']],
        'category_assignments' => ['status' => 'planned', 'formats' => ['csv', 'xlsx'], 'note' => 'Use category_codes on service import'],
        'visibility' => ['status' => 'planned', 'formats' => ['csv', 'xlsx'], 'note' => 'Use visibility columns on entity imports'],
        'featured_flags' => ['status' => 'planned', 'formats' => ['csv', 'xlsx'], 'note' => 'Use is_featured on entity imports'],
        'top_rated_flags' => ['status' => 'planned', 'formats' => ['csv', 'xlsx'], 'note' => 'Use is_top_rated on entity imports'],
    ],

    'entity_plans' => [
        'categories' => [
            'required_columns' => ['code', 'name'],
            'optional_columns' => ['slug', 'description', 'parent_code', 'is_featured', 'meta_title', 'meta_description', 'focus_keywords', 'faq_pairs', 'show_on_homepage', 'show_on_about', 'show_on_contact'],
            'validation' => ['unique_code', 'valid_parent_chain'],
        ],
        'services' => [
            'required_columns' => ['service_code', 'title'],
            'optional_columns' => ['description', 'category_codes', 'is_featured', 'is_top_rated', 'show_on_homepage', 'meta_title', 'key_benefits', 'eligibility', 'process_steps', 'faq_pairs'],
            'validation' => ['unique_service_code', 'category_codes_exist'],
        ],
        'sub_services' => [
            'required_columns' => ['parent_service_code', 'sub_service_code', 'title'],
            'optional_columns' => ['description', 'is_featured', 'sort_order', 'faq_pairs', 'meta_title'],
            'validation' => ['parent_exists', 'unique_sub_code_per_parent'],
        ],
        'mappings' => [
            'required_columns' => ['service_code', 'pincode'],
            'optional_columns' => ['priority', 'is_visible', 'is_featured', 'coverage_notes', 'category_filter_codes', 'effective_from', 'effective_until'],
            'validation' => ['service_exists', 'pincode_exists'],
        ],
        'geo' => [
            'required_columns' => ['pincode'],
            'optional_columns' => ['coverage_text', 'emergency_coverage_text', 'landmark_names', 'hospital_names', 'nearby_areas', 'faq_pairs'],
            'validation' => ['pincode_valid'],
        ],
        'visibility' => [
            'required_columns' => ['entity_type', 'entity_key'],
            'optional_columns' => ['show_on_homepage', 'show_on_about', 'show_on_contact', 'is_featured', 'is_top_rated'],
            'validation' => ['entity_resolvable'],
        ],
    ],

    'import_order' => [
        'categories',
        'services',
        'sub_services',
        'pincodes',
        'geo',
        'mappings',
    ],

    'workbooks' => [
        'services' => [
            'label' => 'services.xlsx',
            'filename_hints' => ['services.xlsx', 'services.xls', 'medca-services.xlsx'],
            'sheet_order' => ['categories', 'services', 'subservices', 'servicedefaults'],
            'sheets' => [
                'categories' => [
                    'entity' => 'categories',
                    'aliases' => ['category', 'service_categories'],
                ],
                'services' => [
                    'entity' => 'services',
                    'aliases' => ['service'],
                ],
                'subservices' => [
                    'entity' => 'sub_services',
                    'aliases' => ['sub_services', 'subs'],
                ],
                'servicedefaults' => [
                    'entity' => 'service_defaults',
                    'aliases' => ['service_defaults', 'defaults'],
                    'optional' => true,
                ],
            ],
        ],
        'pincodes' => [
            'label' => 'pincodes.xlsx',
            'filename_hints' => ['pincodes.xlsx', 'pincodes.xls', 'medca-pincodes.xlsx'],
            'sheet_order' => ['pincodes', 'geoenrichment', 'mappings'],
            'sheets' => [
                'pincodes' => [
                    'entity' => 'pincodes',
                    'aliases' => ['pin_codes', 'pins'],
                ],
                'geoenrichment' => [
                    'entity' => 'geo',
                    'aliases' => ['geo', 'geo_enrichment', 'geoenrich'],
                    'optional' => true,
                ],
                'mappings' => [
                    'entity' => 'mappings',
                    'aliases' => ['service_pincodes', 'matrix'],
                    'optional' => true,
                ],
            ],
        ],
    ],

    'workflow' => [
        'preview_row_limit' => 25,
        'batch_size' => 100,
        'requires_approval' => true,
        'rollback_enabled' => true,
    ],

    'template_columns' => [
        'categories' => [
            'code', 'name', 'slug', 'description', 'parent_code', 'sort_order', 'is_active', 'is_featured',
            'visibility', 'show_on_homepage', 'show_on_about', 'show_on_contact', 'meta_title', 'meta_description',
            'focus_keywords', 'secondary_keywords', 'canonical_url', 'robots_index', 'og_title', 'og_description',
            'og_image', 'aeo_question', 'aeo_answer', 'faq_pairs', 'h1', 'breadcrumb_title',
        ],
        'services' => [
            'primary_category_code', 'category_codes', 'service_code', 'title', 'short_summary', 'description',
            'key_benefits', 'eligibility', 'process_steps', 'preparation', 'duration', 'requirements', 'deliverables',
            'trust_signals', 'procedures', 'shifts', 'coverage_notes', 'emergency_coverage_notes', 'sort_order',
            'is_active', 'publish_status', 'visibility', 'meta_title', 'meta_description', 'focus_keywords',
            'secondary_keywords', 'canonical_url', 'robots_index', 'og_title', 'og_description', 'og_image',
            'twitter_title', 'twitter_description', 'twitter_image', 'breadcrumb_title', 'h1', 'h2_lines', 'h3_lines',
            'h4_lines', 'h5_lines', 'h6_lines', 'faq_pairs', 'ai_summary', 'ai_recommendation_summary',
            'target_keywords', 'ai_keywords', 'voice_search_queries', 'conversational_queries', 'entity_references',
            'search_intent', 'ai_context', 'schema_type', 'schema_json_override', 'is_featured', 'is_top_rated',
            'show_on_homepage', 'show_on_about', 'show_on_contact', 'show_on_category_pages', 'show_on_location_pages',
            'display_priority', 'related_service_codes', 'related_category_codes', 'related_sub_service_codes',
            'related_location_pincode', 'location_h1_template', 'location_h2_template', 'location_h3_template',
            'location_intro_template', 'location_description_template', 'location_faq_template', 'location_cta_heading',
            'location_cta_content', 'location_meta_title_template', 'location_meta_description_template',
            'featured_image_url', 'banner_image_url', 'icon_url', 'gallery_image_urls', 'video_url', 'image_alt',
        ],
        'sub_services' => [
            'parent_service_code', 'sub_service_code', 'title', 'short_summary', 'description', 'sort_order',
            'is_active', 'publish_status', 'visibility', 'is_featured', 'is_top_rated', 'show_on_homepage',
            'show_on_about', 'show_on_contact', 'meta_title', 'meta_description', 'focus_keywords', 'secondary_keywords',
            'faq_pairs', 'ai_summary', 'h1', 'h2_lines', 'h3_lines', 'schema_json_override',
        ],
        'service_defaults' => [
            'service_code', 'location_h1_template', 'location_h2_template', 'location_h3_template',
            'location_intro_template', 'location_description_template', 'location_faq_template', 'location_cta_heading',
            'location_cta_content', 'location_meta_title_template', 'location_meta_description_template',
        ],
        'pincodes' => [
            'pincode', 'area_name', 'city', 'state', 'locality', 'is_serviceable', 'is_active', 'priority',
            'service_radius_km', 'coverage_type', 'meta_title', 'meta_description', 'seo_keywords',
        ],
        'geo_enrichment' => [
            'pincode', 'coverage_text', 'emergency_coverage_text', 'landmark_names', 'hospital_names',
            'nearby_areas', 'faq_pairs', 'geo_entity_signals', 'local_intent_keywords',
        ],
        'mappings' => [
            'service_code', 'pincode', 'priority', 'is_visible', 'is_featured', 'coverage_notes',
            'category_filter_codes', 'effective_from', 'effective_until',
        ],
    ],
];
