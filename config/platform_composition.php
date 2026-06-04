<?php

/**
 * Platform composition rules — single source of truth for ownership and render policy.
 * See PLATFORM-COMPOSITION-REPAIR-PLAN.md
 */
return [

    'rules' => [
        'data_single_owner' => 'Each fact (service title, phone, meta title) has one authoritative store.',
        'layout_single_owner' => 'Page content tokens define public layout order; blocks/sections do not reorder pages.',
        'preview_production_path' => 'Admin preview must use ContentParser and layouts.app (or documented public route).',
        'blocks_render_not_own' => 'Blocks read data via $service, $blockSettings, global interpolation — they do not define catalog facts.',
        'pages_compose_not_duplicate' => 'Pages hold composition tokens; service facts stay on services table.',
        'global_shared_only' => 'Global content variables are for cross-page business constants (phone, brand).',
        'services_own_facts' => 'Operations Enterprise Services owns catalog clinical/commercial facts.',
        'forms_own_submissions' => 'Leads table owns submissions; pages/modules own placement only.',
        'page_seo_canonical_when_linked' => 'When detail_page_id is set, live meta/FAQs prefer Page fields if filled.',
        'service_tokens_in_blocks' => 'Related offerings use {{service:code}} inside block regions, not duplicate service rows on pages.',
    ],

    'production_render' => [
        'page' => \App\Services\ContentParser::class.'::parse',
        'layout' => 'layouts.app',
        'service_public_route' => 'public.services.show',
        'page_preview_route' => 'site-architect.pages.preview',
    ],

    'service_detail_slug_pattern' => 'service-{code}',

    /*
    |--------------------------------------------------------------------------
    | Phase B — composition decisions (approved)
    |--------------------------------------------------------------------------
    */
    'block_content_owner' => 'blocks.settings_json.content',
    'global_content_scope' => 'shared_business_constants_only',
    'section_library_deprecated' => true,
    'section_library_deprecation_note' => 'Section Library has no items in production. Use page block tokens. {{section:slug}} parsing remains for backward compatibility.',
    'elements_admin_exposure' => 'implementation_detail_only',

    /** Blocks on the five public marketing pages (MedcaPublicPagesSeeder) with content schemas. */
    'marketing_surface_block_slugs' => [
        'hero-home', 'services-overview-home', 'near-you-home', 'locations-overview-home', 'cta-home',
        'hero-about', 'body-about',
        'hero-services', 'services-grid-full', 'cta-services',
        'hero-locations', 'near-you-locations', 'locations-coverage',
        'hero-contact', 'contact-info', 'form-callback',
    ],

];
