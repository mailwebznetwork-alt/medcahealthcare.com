<?php

/**
 * Canonical Git-managed block templates (Phase 3 governance).
 *
 * Each entry syncs to the `blocks` table via BlockTemplateSyncService / `php artisan blocks:sync`.
 * Template markup lives in resources/views/blocks/{category}/{slug}.blade.php unless `code` is set.
 */
return [

    'backup_directory' => storage_path('app/block-backups'),

    'categories' => [
        'home',
        'about',
        'careers',
        'services',
        'locations',
        'contact',
        'shared',
    ],

    'templates' => [

        // ── Home ──────────────────────────────────────────────────────────
        'hero-home' => [
            'category' => 'home',
            'block_name' => 'Home — Hero',
            'description' => 'Marketing hero for the public home page.',
            'block_type' => 'Hero',
            'view' => 'blocks.home.hero-home',
        ],
        'services-overview-home' => [
            'category' => 'home',
            'block_name' => 'Home — Services Overview',
            'description' => 'Three-card teaser for the four flagship Medca services on the home page.',
            'block_type' => 'Service Grid',
            'view' => 'blocks.home.services-overview-home',
        ],
        'near-you-home' => [
            'category' => 'home',
            'block_name' => 'Home — Near You (pincode services)',
            'description' => 'Hyper-local service grid based on the visitor pincode. Service cards come from Operations → Services.',
            'block_type' => 'Service Grid',
            'view' => 'blocks.shared.near-you-services',
            'is_required_on_pages' => ['home'],
        ],
        'near-you-locations' => [
            'category' => 'locations',
            'block_name' => 'Locations — Near You (pincode services)',
            'description' => 'Same pincode-based service grid for the Locations page. Uses the home Near You block code.',
            'block_type' => 'Service Grid',
            'view' => 'blocks.home.near-you-home',
            'code' => "@include('blocks.home.near-you-home')",
            'is_required_on_pages' => ['locations'],
        ],
        'locations-overview-home' => [
            'category' => 'home',
            'block_name' => 'Home — Locations Overview',
            'description' => 'Coverage strip for the home page with pin-code belt around Arekere.',
            'block_type' => 'Sections',
            'view' => 'blocks.home.locations-overview-home',
        ],
        'cta-home' => [
            'category' => 'home',
            'block_name' => 'Home — CTA',
            'description' => 'Closing call-to-action on the home page.',
            'block_type' => 'CTA',
            'view' => 'blocks.home.cta-home',
        ],

        // ── About ─────────────────────────────────────────────────────────
        'hero-about' => [
            'category' => 'about',
            'block_name' => 'About — Hero',
            'description' => 'Hero block for the About Us page.',
            'block_type' => 'Hero',
            'view' => 'blocks.about.hero-about',
        ],
        'body-about' => [
            'category' => 'about',
            'block_name' => 'About — Body',
            'description' => 'Mission, story and care philosophy for About Us.',
            'block_type' => 'Text',
            'view' => 'blocks.about.body-about',
        ],

        // ── Careers ───────────────────────────────────────────────────────
        'hero-careers' => [
            'category' => 'careers',
            'block_name' => 'Careers — Hero',
            'description' => 'Marketing hero for the public /careers hub.',
            'block_type' => 'Hero',
            'view' => 'blocks.careers.hero-careers',
        ],
        'careers-open-roles' => [
            'category' => 'careers',
            'block_name' => 'Careers — Open roles listing',
            'description' => 'Searchable vacancy cards aligned with site header. Requires $vacancies on /careers.',
            'block_type' => 'Listing',
            'view' => 'blocks.careers.open-roles-listing',
            'code' => "@include('blocks.careers.open-roles-listing', ['vacancies' => \$vacancies ?? collect()])",
        ],
        'careers-job-detail-layout' => [
            'category' => 'careers',
            'block_name' => 'Careers — Job detail layout',
            'description' => 'Full job detail with apply panel. Requires $vacancy on /careers/{slug}.',
            'block_type' => 'Layout',
            'view' => 'blocks.careers.job-detail-layout',
            'code' => "@include('blocks.careers.job-detail-layout', ['vacancy' => \$vacancy])",
        ],
        'careers' => [
            'category' => 'careers',
            'block_name' => 'Careers — Open roles (legacy alias)',
            'description' => 'Alias of careers-open-roles for backward compatibility.',
            'block_type' => 'Listing',
            'view' => 'blocks.careers.open-roles-listing',
            'code' => "@include('blocks.careers.open-roles-listing', ['vacancies' => \$vacancies ?? collect()])",
        ],

        // ── Services (marketing + detail) ─────────────────────────────────
        'hero-services' => [
            'category' => 'services',
            'block_name' => 'Services — Hero',
            'description' => 'Hero block for the Services page.',
            'block_type' => 'Hero',
            'view' => 'blocks.services.hero-services',
        ],
        'services-grid-full' => [
            'category' => 'services',
            'block_name' => 'Services — Full Grid',
            'description' => 'Full-width service grid covering Medca\'s flagship offerings.',
            'block_type' => 'Service Grid',
            'view' => 'blocks.services.services-grid-full',
        ],
        'cta-services' => [
            'category' => 'services',
            'block_name' => 'Services — CTA',
            'description' => 'Closing CTA for Services page.',
            'block_type' => 'CTA',
            'view' => 'blocks.services.cta-services',
        ],
        'services-block-carousel' => [
            'category' => 'services',
            'block_name' => 'Services — carousel (pick services)',
            'description' => 'Carousel of services selected via {{service:code}} tokens in Site Architect.',
            'block_type' => 'Service Grid',
            'view' => 'blocks.services.block-carousel',
            'code' => <<<'BLADE'
{{service:homenursing-services}}
{{service:elder-care}}
{{service:caregivers}}
{{service:doctor-home-visit}}
{{service:physiotherapy-at-home}}
{{service:icu-care-at-home}}
@include('blocks.services.block-carousel')
BLADE,
        ],
        'services-block-grid' => [
            'category' => 'services',
            'block_name' => 'Services — grid (pick services)',
            'description' => 'Grid of services selected via {{service:code}} tokens in Site Architect.',
            'block_type' => 'Service Grid',
            'view' => 'blocks.services.block-grid',
        ],
        'service-detail-hero' => [
            'category' => 'services',
            'block_name' => 'Service detail — hero (uses $service)',
            'description' => 'Minimal hero for service detail pages.',
            'block_type' => 'Hero',
            'view' => 'blocks.services.service-detail-hero',
        ],
        'service-detail-areas' => [
            'category' => 'services',
            'block_name' => 'Service detail — areas served',
            'description' => 'GEO pincode grid for the current service.',
            'block_type' => 'Text',
            'view' => 'blocks.services.service-detail-areas',
        ],
        'service-detail-related' => [
            'category' => 'services',
            'block_name' => 'Service detail — related (Insert service tokens)',
            'description' => 'Related services carousel driven by inserted service tokens.',
            'block_type' => 'Service Grid',
            'view' => 'blocks.services.service-detail-related',
        ],
        'services-detail-layout' => [
            'category' => 'services',
            'block_name' => 'Services — detail fallback',
            'description' => 'Composed layout for /services/{code} Site Architect pages.',
            'block_type' => 'Layout',
            'code' => "{{block:service-detail-hero}}\n{{block:service-detail-areas}}",
        ],

        // ── Locations ─────────────────────────────────────────────────────
        'hero-locations' => [
            'category' => 'locations',
            'block_name' => 'Locations — Hero',
            'description' => 'Hero block for the Locations page.',
            'block_type' => 'Hero',
            'view' => 'blocks.locations.hero-locations',
        ],
        'locations-coverage' => [
            'category' => 'locations',
            'block_name' => 'Locations — Coverage Areas',
            'description' => 'Pin-code/area grid for Locations page.',
            'block_type' => 'Sections',
            'view' => 'blocks.locations.locations-coverage',
        ],

        // ── Contact ───────────────────────────────────────────────────────
        'hero-contact' => [
            'category' => 'contact',
            'block_name' => 'Contact — Hero',
            'description' => 'Hero block for the Contact Us page.',
            'block_type' => 'Hero',
            'view' => 'blocks.contact.hero-contact',
        ],
        'contact-info' => [
            'category' => 'contact',
            'block_name' => 'Contact — Info',
            'description' => 'Contact channels (call, WhatsApp, hours) for Contact Us page.',
            'block_type' => 'Text',
            'view' => 'blocks.contact.contact-info',
        ],

        // ── Shared element library (blueprints, sections, landing pages) ─
        ...require __DIR__.'/block_templates_shared.php',
    ],
];
