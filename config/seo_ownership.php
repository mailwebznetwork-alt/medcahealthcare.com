<?php

return [

    /*
    | Phase 8 — SEO ownership cleanup (rollback via env).
    */
    'skip_autofill_on_generated_pages' => env('SEO_SKIP_AUTOFILL_GENERATED', true),

    'skip_location_meta_duplicates' => env('SEO_SKIP_LOCATION_META_DUPLICATES', true),

    'skip_page_seo_for_generated_pages' => env('SEO_SKIP_PAGE_SEO_GENERATED', true),

    'hide_seo_editing_on_generated_pages' => env('SEO_HIDE_EDITING_GENERATED', true),

    'data_driven_seo_enabled' => env('SEO_DATA_DRIVEN_ENABLED', true),

];
