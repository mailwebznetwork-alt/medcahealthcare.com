<?php

return [

    'premium_healthcare_intro' => [
        'name' => 'Premium Healthcare Intro',
        'description' => 'Hero + statistics-style services overview + CTA.',
        'style_pack_slug' => 'healthcare_premium',
        'blocks_json' => [
            ['slug' => 'hero-home', 'style_variant' => 'style_2'],
            ['slug' => 'services-overview-home', 'style_variant' => 'style_2'],
            ['slug' => 'near-you-home', 'style_variant' => 'style_2'],
            ['slug' => 'cta-home', 'style_variant' => 'style_2'],
        ],
    ],

    'trust_builder' => [
        'name' => 'Trust Builder',
        'description' => 'Services grid + CTA for conversion.',
        'style_pack_slug' => 'healthcare_professional',
        'blocks_json' => [
            ['slug' => 'services-grid-full', 'style_variant' => 'style_1'],
            ['slug' => 'cta-services', 'style_variant' => 'style_1'],
        ],
    ],

    'landing_healthcare_full' => [
        'name' => 'Landing — Healthcare full funnel',
        'description' => 'Hero, trust, features, testimonials, FAQ, and CTA using shared elements.',
        'style_pack_slug' => 'healthcare_premium',
        'blocks_json' => [
            ['slug' => 'hero-healthcare', 'style_variant' => 'style_2'],
            ['slug' => 'trust-bar-icons', 'style_variant' => 'style_2'],
            ['slug' => 'features-grid', 'style_variant' => 'style_2'],
            ['slug' => 'statistics-row', 'style_variant' => 'style_2'],
            ['slug' => 'testimonials-highlight', 'style_variant' => 'style_2'],
            ['slug' => 'faq-accordion', 'style_variant' => 'style_2'],
            ['slug' => 'cta-banner', 'style_variant' => 'style_2'],
        ],
    ],

    'landing_conversion_strip' => [
        'name' => 'Landing — Conversion strip',
        'description' => 'Split hero, pricing teaser, lead magnet, sticky CTA.',
        'style_pack_slug' => 'healthcare_modern',
        'blocks_json' => [
            ['slug' => 'hero-split', 'style_variant' => 'style_3'],
            ['slug' => 'pricing-tiers', 'style_variant' => 'style_3'],
            ['slug' => 'lead-magnet-guide', 'style_variant' => 'style_3'],
            ['slug' => 'cta-sticky', 'style_variant' => 'style_3'],
        ],
    ],

    'pack_healthcare_services_funnel' => [
        'name' => 'Pack — Healthcare service funnel',
        'description' => 'Trust, benefits, process, FAQ, CTA for clinical service pages.',
        'style_pack_slug' => 'healthcare_professional',
        'blocks_json' => [
            ['slug' => 'hero-split', 'style_variant' => 'style_1'],
            ['slug' => 'services-benefits', 'style_variant' => 'style_1'],
            ['slug' => 'process-steps', 'style_variant' => 'style_1'],
            ['slug' => 'faq-accordion', 'style_variant' => 'style_1'],
            ['slug' => 'cta-banner', 'style_variant' => 'style_1'],
        ],
    ],

    'pack_care_home_admissions' => [
        'name' => 'Pack — Care home admissions',
        'description' => 'Admissions journey with pricing and callback form.',
        'style_pack_slug' => 'healthcare_premium',
        'blocks_json' => [
            ['slug' => 'hero-centered', 'style_variant' => 'style_2'],
            ['slug' => 'process-flow', 'style_variant' => 'style_2'],
            ['slug' => 'pricing-table', 'style_variant' => 'style_2'],
            ['slug' => 'form-callback', 'style_variant' => 'style_2'],
        ],
    ],

    'pack_real_estate_listing' => [
        'name' => 'Pack — Real estate listing',
        'description' => 'Property cards, trust, lead form.',
        'style_pack_slug' => 'modern_purple',
        'blocks_json' => [
            ['slug' => 'cards-image-row', 'style_variant' => 'style_3'],
            ['slug' => 'trust-bar-icons', 'style_variant' => 'style_3'],
            ['slug' => 'form-callback', 'style_variant' => 'style_3'],
        ],
    ],

    'pack_clinic_consultation' => [
        'name' => 'Pack — Clinic consultation',
        'description' => 'Before/after, pricing teaser, consultation CTA.',
        'style_pack_slug' => 'luxury_black',
        'blocks_json' => [
            ['slug' => 'before-after', 'style_variant' => 'style_5'],
            ['slug' => 'pricing-tiers', 'style_variant' => 'style_5'],
            ['slug' => 'cta-banner', 'style_variant' => 'style_5'],
        ],
    ],

];
