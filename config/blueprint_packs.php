<?php

/**
 * Production-ready industry blueprint packs (extends core blueprints in config/blueprints.php).
 *
 * @return array<string, array<string, mixed>>
 */
$style = static fn (string $variant = 'style_1'): array => ['style_variant' => $variant];

$servicePage = static fn (
    string $title,
    string $slug,
    string $variant = 'style_1',
    string $hero = 'hero-split',
): array => [
    'title' => $title,
    'slug' => $slug,
    'layout_mode' => 'contained',
    'blocks' => [
        ['slug' => $hero, ...$style($variant)],
        ['slug' => 'services-benefits', ...$style($variant)],
        ['slug' => 'process-steps', ...$style($variant)],
        ['slug' => 'faq-accordion', ...$style($variant)],
        ['slug' => 'cta-banner', ...$style($variant)],
    ],
];

return [

    'home_healthcare career consultancy' => [
        'label' => 'Healthcare Career Pack (Medca Consultancy)',
        'industry' => 'healthcare career consultancy',
        'description' => 'Complete healthcare career consultancy site: six clinical service lines, trust, FAQ, testimonials, and conversion CTAs.',
        'default_style_pack' => 'healthcare career consultancy_professional',
        'default_theme_preset' => 'clinical_blue',
        'pack_meta' => [
            'version' => '2.0',
            'services' => ['Home Care', 'Advisory', 'Consulting Services', 'Consultations', 'Consulting', 'Palliative Care'],
            'cta_strategy' => 'Primary: cta-banner on service pages; home: cta-home; landings: cta-sticky + form-callback',
        ],
        'pages' => [
            'home' => [
                'title' => 'Home',
                'slug' => 'home',
                'layout_mode' => 'canvas',
                'blocks' => [
                    ['slug' => 'hero-healthcare career consultancy', ...$style('style_1')],
                    ['slug' => 'trust-bar-icons', ...$style('style_1')],
                    ['slug' => 'services-overview-home', ...$style('style_1')],
                    ['slug' => 'statistics-row', ...$style('style_1')],
                    ['slug' => 'testimonials-highlight', ...$style('style_1')],
                    ['slug' => 'locations-overview-home', ...$style('style_1')],
                    ['slug' => 'cta-home', ...$style('style_1')],
                ],
            ],
            'services' => [
                'title' => 'Services',
                'slug' => 'services',
                'layout_mode' => 'contained',
                'blocks' => [
                    ['slug' => 'hero-services', ...$style('style_1')],
                    ['slug' => 'services-grid-full', ...$style('style_1')],
                    ['slug' => 'comparison-features', ...$style('style_1')],
                    ['slug' => 'cta-services', ...$style('style_1')],
                ],
            ],
            'about' => [
                'title' => 'About Us',
                'slug' => 'about',
                'layout_mode' => 'contained',
                'blocks' => [
                    ['slug' => 'hero-about', ...$style('style_1')],
                    ['slug' => 'body-about', ...$style('style_1')],
                    ['slug' => 'team-grid', ...$style('style_1')],
                    ['slug' => 'logos-partners', ...$style('style_1')],
                ],
            ],
            'contact' => [
                'title' => 'Contact',
                'slug' => 'contact',
                'layout_mode' => 'contained',
                'blocks' => [
                    ['slug' => 'hero-contact', ...$style('style_1')],
                    ['slug' => 'contact-split', ...$style('style_1')],
                    ['slug' => 'form-callback', ...$style('style_1')],
                    ['slug' => 'location-radius', ...$style('style_1')],
                ],
            ],
            'faq' => [
                'title' => 'FAQ',
                'slug' => 'faq',
                'layout_mode' => 'contained',
                'blocks' => [
                    ['slug' => 'hero-centered', ...$style('style_1')],
                    ['slug' => 'faq-accordion', ...$style('style_1')],
                    ['slug' => 'faq-columns', ...$style('style_1')],
                    ['slug' => 'cta-simple', ...$style('style_1')],
                ],
            ],
            'testimonials' => [
                'title' => 'Testimonials',
                'slug' => 'testimonials',
                'layout_mode' => 'contained',
                'blocks' => [
                    ['slug' => 'hero-centered', ...$style('style_1')],
                    ['slug' => 'testimonials-grid', ...$style('style_1')],
                    ['slug' => 'reviews-grid', ...$style('style_1')],
                    ['slug' => 'cta-banner', ...$style('style_1')],
                ],
            ],
            'service-home-care' => $servicePage('Home Care', 'service-home-care'),
            'service-elder-care' => $servicePage('Advisory', 'service-elder-care'),
            'service-consulting' => $servicePage('Consulting Services', 'service-consulting'),
            'service-doctor-visits' => $servicePage('Consultations at Home', 'service-doctor-visits'),
            'service-consulting' => $servicePage('Consulting at Home', 'service-consulting'),
            'service-palliative-care' => $servicePage('Palliative Care', 'service-palliative-care', 'style_2', 'hero-healthcare career consultancy'),
        ],
        'landing_pages' => [
            'consultation' => [
                'title' => 'Book a Consultation',
                'slug' => 'consultation',
                'layout_mode' => 'canvas',
                'blocks' => [
                    ['slug' => 'hero-healthcare career consultancy', ...$style('style_2')],
                    ['slug' => 'form-callback', ...$style('style_2')],
                    ['slug' => 'cta-sticky', ...$style('style_2')],
                ],
            ],
            'lp-consulting' => [
                'title' => 'Consulting Care — Landing',
                'slug' => 'lp-consulting',
                'layout_mode' => 'canvas',
                'blocks' => [
                    ['slug' => 'hero-split', ...$style('style_2')],
                    ['slug' => 'statistics-cards', ...$style('style_2')],
                    ['slug' => 'testimonials-carousel', ...$style('style_2')],
                    ['slug' => 'cta-banner', ...$style('style_2')],
                ],
            ],
            'lp-consulting' => [
                'title' => 'Consulting — Landing',
                'slug' => 'lp-consulting',
                'layout_mode' => 'canvas',
                'blocks' => [
                    ['slug' => 'hero-split', ...$style('style_2')],
                    ['slug' => 'process-flow', ...$style('style_2')],
                    ['slug' => 'lead-magnet-guide', ...$style('style_2')],
                    ['slug' => 'cta-sticky', ...$style('style_2')],
                ],
            ],
        ],
    ],

    'care_home' => [
        'label' => 'Care Home Pack',
        'industry' => 'care_home',
        'description' => 'Residential care: admissions funnel, facilities, services, reviews, and FAQ.',
        'default_style_pack' => 'healthcare career consultancy_premium',
        'default_theme_preset' => 'premium_gold',
        'pack_meta' => [
            'version' => '2.0',
            'cta_strategy' => 'Admissions: cta-banner; home: premium hero + trust; reviews on dedicated page',
        ],
        'pages' => [
            'home' => [
                'title' => 'Care Home',
                'slug' => 'home',
                'layout_mode' => 'canvas',
                'blocks' => [
                    ['slug' => 'hero-healthcare career consultancy', ...$style('style_2')],
                    ['slug' => 'trust-bar-badges', ...$style('style_2')],
                    ['slug' => 'features-icons', ...$style('style_2')],
                    ['slug' => 'gallery-showcase', ...$style('style_2')],
                    ['slug' => 'testimonials-highlight', ...$style('style_2')],
                    ['slug' => 'cta-banner', ...$style('style_2')],
                ],
            ],
            'about' => [
                'title' => 'About Our Home',
                'slug' => 'about',
                'layout_mode' => 'contained',
                'blocks' => [
                    ['slug' => 'hero-about', ...$style('style_2')],
                    ['slug' => 'body-about', ...$style('style_1')],
                    ['slug' => 'team-leaders', ...$style('style_2')],
                    ['slug' => 'timeline-milestones', ...$style('style_2')],
                ],
            ],
            'services' => [
                'title' => 'Care Services',
                'slug' => 'services',
                'layout_mode' => 'contained',
                'blocks' => [
                    ['slug' => 'hero-services', ...$style('style_2')],
                    ['slug' => 'services-grid-full', ...$style('style_2')],
                    ['slug' => 'process-steps', ...$style('style_2')],
                    ['slug' => 'cta-services', ...$style('style_2')],
                ],
            ],
            'admissions' => [
                'title' => 'Admissions',
                'slug' => 'admissions',
                'layout_mode' => 'contained',
                'blocks' => [
                    ['slug' => 'hero-centered', ...$style('style_2')],
                    ['slug' => 'process-flow', ...$style('style_2')],
                    ['slug' => 'pricing-table', ...$style('style_2')],
                    ['slug' => 'form-callback', ...$style('style_2')],
                    ['slug' => 'faq-accordion', ...$style('style_2')],
                ],
            ],
            'facilities' => [
                'title' => 'Facilities',
                'slug' => 'facilities',
                'layout_mode' => 'contained',
                'blocks' => [
                    ['slug' => 'hero-split', ...$style('style_2')],
                    ['slug' => 'gallery-grid', ...$style('style_2')],
                    ['slug' => 'features-grid', ...$style('style_2')],
                    ['slug' => 'video-sidecar', ...$style('style_2')],
                ],
            ],
            'faq' => [
                'title' => 'FAQ',
                'slug' => 'faq',
                'layout_mode' => 'contained',
                'blocks' => [
                    ['slug' => 'faq-accordion', ...$style('style_2')],
                    ['slug' => 'callout-tip', ...$style('style_2')],
                    ['slug' => 'cta-simple', ...$style('style_2')],
                ],
            ],
            'reviews' => [
                'title' => 'Reviews',
                'slug' => 'reviews',
                'layout_mode' => 'contained',
                'blocks' => [
                    ['slug' => 'reviews-bar', ...$style('style_2')],
                    ['slug' => 'testimonials-grid', ...$style('style_2')],
                    ['slug' => 'reviews-grid', ...$style('style_2')],
                ],
            ],
            'contact' => [
                'title' => 'Contact',
                'slug' => 'contact',
                'layout_mode' => 'contained',
                'blocks' => [
                    ['slug' => 'hero-contact', ...$style('style_2')],
                    ['slug' => 'contact-split', ...$style('style_2')],
                ],
            ],
        ],
        'landing_pages' => [
            'tour' => [
                'title' => 'Book a Home Tour',
                'slug' => 'book-tour',
                'layout_mode' => 'canvas',
                'blocks' => [
                    ['slug' => 'hero-split', ...$style('style_2')],
                    ['slug' => 'form-callback', ...$style('style_2')],
                    ['slug' => 'cta-sticky', ...$style('style_2')],
                ],
            ],
        ],
    ],

    'real_estate' => [
        'label' => 'Real Estate Pack',
        'industry' => 'real_estate',
        'description' => 'Property marketing: listings, project pages, and lead capture landings.',
        'default_style_pack' => 'modern_purple',
        'default_theme_preset' => 'modern_purple',
        'pack_meta' => [
            'version' => '1.0',
            'cta_strategy' => 'Lead capture via form-callback + cta-split on listings and project pages',
        ],
        'pages' => [
            'home' => [
                'title' => 'Properties',
                'slug' => 'home',
                'layout_mode' => 'canvas',
                'blocks' => [
                    ['slug' => 'hero-split', ...$style('style_3')],
                    ['slug' => 'statistics-row', ...$style('style_3')],
                    ['slug' => 'cards-image-row', ...$style('style_3')],
                    ['slug' => 'trust-bar-icons', ...$style('style_3')],
                    ['slug' => 'cta-split', ...$style('style_3')],
                ],
            ],
            'listings' => [
                'title' => 'Listings',
                'slug' => 'listings',
                'layout_mode' => 'contained',
                'blocks' => [
                    ['slug' => 'hero-centered', ...$style('style_3')],
                    ['slug' => 'services-block-grid', ...$style('style_3')],
                    ['slug' => 'cards-icon-row', ...$style('style_3')],
                    ['slug' => 'form-callback', ...$style('style_3')],
                ],
            ],
            'about' => [
                'title' => 'About',
                'slug' => 'about',
                'layout_mode' => 'contained',
                'blocks' => [
                    ['slug' => 'hero-about', ...$style('style_3')],
                    ['slug' => 'content-split', ...$style('style_3')],
                    ['slug' => 'logos-partners', ...$style('style_3')],
                ],
            ],
            'contact' => [
                'title' => 'Contact',
                'slug' => 'contact',
                'layout_mode' => 'contained',
                'blocks' => [
                    ['slug' => 'contact-split', ...$style('style_3')],
                    ['slug' => 'location-radius', ...$style('style_3')],
                ],
            ],
            'project-sample' => [
                'title' => 'Sample Project',
                'slug' => 'project-sample',
                'layout_mode' => 'contained',
                'blocks' => [
                    ['slug' => 'hero-split', ...$style('style_3')],
                    ['slug' => 'gallery-showcase', ...$style('style_3')],
                    ['slug' => 'features-grid', ...$style('style_3')],
                    ['slug' => 'pricing-table', ...$style('style_3')],
                    ['slug' => 'cta-banner', ...$style('style_3')],
                ],
            ],
        ],
        'landing_pages' => [
            'property-lead' => [
                'title' => 'Property Enquiry',
                'slug' => 'property-enquiry',
                'layout_mode' => 'canvas',
                'blocks' => [
                    ['slug' => 'hero-centered', ...$style('style_3')],
                    ['slug' => 'form-callback', ...$style('style_3')],
                    ['slug' => 'lead-magnet-webinar', ...$style('style_3')],
                    ['slug' => 'cta-sticky', ...$style('style_3')],
                ],
            ],
        ],
    ],

    'cosmetics_clinic' => [
        'label' => 'Beauty & Cosmetics Clinic Pack',
        'industry' => 'cosmetics',
        'description' => 'Clinic funnel: treatments, before/after, pricing, reviews, consultation CTA.',
        'default_style_pack' => 'luxury_black',
        'default_theme_preset' => 'luxury_black',
        'pack_meta' => [
            'version' => '1.0',
            'cta_strategy' => 'Consultation landings use cta-banner + form-callback; pricing on dedicated page',
        ],
        'pages' => [
            'home' => [
                'title' => 'Clinic',
                'slug' => 'home',
                'layout_mode' => 'canvas',
                'blocks' => [
                    ['slug' => 'hero-centered', ...$style('style_5')],
                    ['slug' => 'trust-bar-badges', ...$style('style_5')],
                    ['slug' => 'services-block-carousel', ...$style('style_5')],
                    ['slug' => 'before-after', ...$style('style_5')],
                    ['slug' => 'testimonials-carousel', ...$style('style_5')],
                    ['slug' => 'cta-banner', ...$style('style_5')],
                ],
            ],
            'services' => [
                'title' => 'Treatments',
                'slug' => 'services',
                'layout_mode' => 'contained',
                'blocks' => [
                    ['slug' => 'hero-services', ...$style('style_5')],
                    ['slug' => 'services-grid-full', ...$style('style_5')],
                    ['slug' => 'pricing-tiers', ...$style('style_5')],
                    ['slug' => 'cta-services', ...$style('style_5')],
                ],
            ],
            'before-after' => [
                'title' => 'Before & After',
                'slug' => 'before-after',
                'layout_mode' => 'contained',
                'blocks' => [
                    ['slug' => 'hero-centered', ...$style('style_5')],
                    ['slug' => 'before-after', ...$style('style_5')],
                    ['slug' => 'gallery-grid', ...$style('style_5')],
                    ['slug' => 'callout-highlight', ...$style('style_5')],
                ],
            ],
            'pricing' => [
                'title' => 'Pricing',
                'slug' => 'pricing',
                'layout_mode' => 'contained',
                'blocks' => [
                    ['slug' => 'pricing-tiers', ...$style('style_5')],
                    ['slug' => 'comparison-features', ...$style('style_5')],
                    ['slug' => 'faq-accordion', ...$style('style_5')],
                ],
            ],
            'reviews' => [
                'title' => 'Reviews',
                'slug' => 'reviews',
                'layout_mode' => 'contained',
                'blocks' => [
                    ['slug' => 'reviews-bar', ...$style('style_5')],
                    ['slug' => 'testimonials-grid', ...$style('style_5')],
                    ['slug' => 'reviews-grid', ...$style('style_5')],
                ],
            ],
            'contact' => [
                'title' => 'Book Consultation',
                'slug' => 'contact',
                'layout_mode' => 'contained',
                'blocks' => [
                    ['slug' => 'hero-contact', ...$style('style_5')],
                    ['slug' => 'form-callback', ...$style('style_5')],
                    ['slug' => 'contact-split', ...$style('style_5')],
                ],
            ],
        ],
        'landing_pages' => [
            'consultation' => [
                'title' => 'Free Consultation',
                'slug' => 'free-consultation',
                'layout_mode' => 'canvas',
                'blocks' => [
                    ['slug' => 'hero-split', ...$style('style_5')],
                    ['slug' => 'lead-magnet-guide', ...$style('style_5')],
                    ['slug' => 'form-callback', ...$style('style_5')],
                    ['slug' => 'cta-sticky', ...$style('style_5')],
                ],
            ],
        ],
    ],

];
