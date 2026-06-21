<?php

return [

    'cache_key' => 'theme.configuration.published',

    'cache_ttl_seconds' => 3600,

    /** Google Fonts families allowed in Appearance → Typography */
    'font_whitelist' => [
        'Plus Jakarta Sans',
        'Noto Sans',
        'Inter',
        'Roboto',
        'Open Sans',
        'Lato',
        'Merriweather',
        'Poppins',
        'Nunito',
        'Source Sans 3',
        'DM Sans',
        'Manrope',
    ],

    'font_scales' => [
        'compact' => ['label' => 'Compact', 'base_size' => '15px'],
        'default' => ['label' => 'Default', 'base_size' => '16px'],
        'large' => ['label' => 'Large', 'base_size' => '18px'],
    ],

    'header_presets' => [
        'classic_healthcare career consultancy' => [
            'label' => 'Classic Healthcare Career',
            'description' => 'Navy top strip + white brand row (current Medca Consultancy default).',
            'class' => 'medca-header-classic',
        ],
        'corporate' => [
            'label' => 'Corporate',
            'description' => 'Flat white header with subtle shadow.',
            'class' => 'medca-header-corporate',
        ],
        'premium' => [
            'label' => 'Premium',
            'description' => 'Dark brand row with gold accent underline.',
            'class' => 'medca-header-premium',
        ],
        'minimal' => [
            'label' => 'Minimal',
            'description' => 'Single-row compact navigation.',
            'class' => 'medca-header-minimal',
        ],
        'modern' => [
            'label' => 'Modern',
            'description' => 'Rounded container header with soft border.',
            'class' => 'medca-header-modern',
        ],
        'mega_menu' => [
            'label' => 'Mega Menu',
            'description' => 'Wide dropdown-ready navigation row.',
            'class' => 'medca-header-mega',
        ],
        'transparent' => [
            'label' => 'Transparent',
            'description' => 'Overlay header for hero-first pages.',
            'class' => 'medca-header-transparent',
        ],
        'sticky_professional' => [
            'label' => 'Sticky Professional',
            'description' => 'Compact sticky bar with shrink on scroll.',
            'class' => 'medca-header-sticky-pro',
        ],
    ],

    /**
     * Header configuration keys (stored in theme branding / draft_branding JSON).
     * Phase 8.5 — preset + configuration architecture (no custom header builder).
     */
    'header_configuration_keys' => [
        'show_top_bar',
        'show_search',
        'show_location_selector',
        'show_branch_selector',
        'show_social_icons',
        'show_secondary_menu',
        'mobile_cta_enabled',
        'mobile_whatsapp_enabled',
        'sticky_behavior',
    ],

    'sticky_behaviors' => [
        'normal' => 'Normal',
        'sticky' => 'Sticky',
        'auto_hide' => 'Auto hide',
        'shrink_on_scroll' => 'Shrink on scroll',
    ],

    'layout_presets' => [
        'contained' => [
            'label' => 'Contained',
            'main_class' => 'mx-auto w-full max-w-6xl px-4 md:px-6 lg:px-8',
            'shell_class' => 'max-w-6xl',
        ],
        'wide' => [
            'label' => 'Wide',
            'main_class' => 'mx-auto w-full max-w-7xl px-4 md:px-6 lg:px-8',
            'shell_class' => 'max-w-7xl',
        ],
        'full' => [
            'label' => 'Full Width',
            'main_class' => 'mx-auto w-full max-w-full px-4 md:px-6',
            'shell_class' => 'max-w-full',
        ],
    ],

    'branding_fields' => [
        'brand_name',
        'tagline',
        'company_legal_name',
        'phone_display',
        'phone_tel',
        'whatsapp_url',
        'contact_email',
        'brand_url',
        'primary_cta_text',
        'logo_path',
        'favicon_path',
    ],

];
