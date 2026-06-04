<?php

/**
 * Page Builder — human-friendly section labels for the visual picker (slugs stay internal).
 */
return [

    'picker_categories' => [
        'Hero',
        'Features',
        'Services',
        'Statistics',
        'Process',
        'Testimonials',
        'Reviews',
        'FAQ',
        'Gallery',
        'Team',
        'CTA',
        'Contact Form',
        'Locations',
        'Pricing',
        'Trust',
        'Video',
        'Comparison',
        'Listing',
        'Layout',
        'Other',
    ],

    'block_type_to_category' => [
        'Hero' => 'Hero',
        'CTA' => 'CTA',
        'FAQ' => 'FAQ',
        'Features' => 'Features',
        'Statistics' => 'Statistics',
        'Process' => 'Process',
        'Testimonials' => 'Testimonials',
        'Reviews' => 'Reviews',
        'Gallery' => 'Gallery',
        'Team' => 'Doctors',
        'Contact' => 'Contact Form',
        'Forms' => 'Contact Form',
        'Service Grid' => 'Services',
        'Sections' => 'Locations',
        'Text' => 'Features',
        'Listing' => 'Listing',
        'Layout' => 'Layout',
        'Pricing' => 'Pricing',
        'Trust Bar' => 'Trust',
        'Video' => 'Video',
        'Comparison' => 'Comparison',
    ],

    /**
     * Optional per-slug overrides (display_name, description, picker_category, preview_key).
     */
    'overrides' => [
        'hero-home' => [
            'display_name' => 'Home Hero',
            'picker_category' => 'Hero',
            'description' => 'Main headline, subheadline, and call-to-action for the home page.',
            'preview_key' => 'hero',
        ],
        'hero-healthcare' => [
            'display_name' => 'Healthcare Hero',
            'picker_category' => 'Hero',
            'description' => 'Premium healthcare hero with call and WhatsApp actions.',
            'preview_key' => 'hero',
        ],
        'near-you-home' => [
            'display_name' => 'Services Near You',
            'picker_category' => 'Locations',
            'description' => 'Pincode-based local service cards (data from Operations → Services).',
            'preview_key' => 'locations',
        ],
        'near-you-locations' => [
            'display_name' => 'Services Near You',
            'picker_category' => 'Locations',
            'description' => 'Pincode-based service list on the Locations page.',
            'preview_key' => 'locations',
        ],
        'locations-coverage' => [
            'display_name' => 'Areas We Cover',
            'picker_category' => 'Locations',
            'description' => 'Pin code / locality grid for coverage areas.',
            'preview_key' => 'map',
        ],
        'form-callback' => [
            'display_name' => 'Contact Form',
            'picker_category' => 'Contact Form',
            'description' => 'Lead capture / callback request form.',
            'preview_key' => 'form',
        ],
        'faq-accordion' => [
            'display_name' => 'FAQ Accordion',
            'picker_category' => 'FAQ',
            'description' => 'Expandable frequently asked questions.',
            'preview_key' => 'faq',
        ],
        'testimonials-grid' => [
            'display_name' => 'Testimonials Grid',
            'picker_category' => 'Testimonials',
            'description' => 'Patient or family testimonial cards.',
            'preview_key' => 'testimonials',
        ],
        'team-grid' => [
            'display_name' => 'Doctors & Team',
            'picker_category' => 'Doctors',
            'description' => 'Clinical team or leadership grid.',
            'preview_key' => 'doctors',
        ],
        'statistics-row' => [
            'display_name' => 'Statistics Row',
            'picker_category' => 'Statistics',
            'description' => 'Key numbers and trust metrics in a row.',
            'preview_key' => 'statistics',
        ],
        'process-steps' => [
            'display_name' => 'Process Steps',
            'picker_category' => 'Process',
            'description' => 'Step-by-step how-it-works flow.',
            'preview_key' => 'process',
        ],
        'before-after' => [
            'display_name' => 'Before & After Gallery',
            'picker_category' => 'Gallery',
            'description' => 'Visual before/after comparison strip.',
            'preview_key' => 'gallery',
        ],
        'cta-banner' => [
            'display_name' => 'CTA Banner',
            'picker_category' => 'CTA',
            'description' => 'Full-width call-to-action band.',
            'preview_key' => 'cta',
        ],
        'cta-split' => [
            'display_name' => 'CTA Split',
            'picker_category' => 'CTA',
            'description' => 'Headline on one side, action on the other.',
            'preview_key' => 'cta',
        ],
    ],

    'recommended_for_page' => [
        'home' => [
            'hero-home',
            'services-overview-home',
            'near-you-home',
            'locations-overview-home',
            'cta-home',
        ],
        'about-us' => ['hero-about', 'body-about'],
        'services' => ['hero-services', 'services-block-carousel', 'cta-services'],
        'locations' => ['hero-locations', 'near-you-locations', 'locations-coverage'],
        'contact' => ['hero-contact', 'contact-info', 'form-callback'],
        'careers' => ['hero-careers', 'careers-open-roles'],
    ],

];
