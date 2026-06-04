<?php

/**
 * Design System — maps block types to style variant keys and CSS modifier classes.
 * Blocks read $blockStyleVariant and $blockStyleClass in Blade (via ContentParser).
 */
return [

    'visual_languages' => [
        'modern', 'minimal', 'premium', 'luxury', 'corporate', 'healthcare',
    ],

    /**
     * Block type (from blocks.block_type) → design family key used in style packs.
     */
    'block_type_families' => [
        'Hero' => 'hero',
        'Service Grid' => 'services',
        'CTA' => 'cta',
        'Sections' => 'services',
        'Text' => 'faq',
        'Listing' => 'blog',
        'Layout' => 'footer',
        'Features' => 'services',
        'Statistics' => 'statistics',
        'Process' => 'services',
        'Testimonials' => 'testimonials',
        'FAQ' => 'faq',
        'Team' => 'team',
        'Gallery' => 'gallery',
        'Video' => 'hero',
        'Forms' => 'faq',
        'Pricing' => 'card',
        'Comparison' => 'card',
        'Trust Bar' => 'services',
        'Logos' => 'services',
        'Cards' => 'card',
        'Timeline' => 'services',
        'Callout' => 'faq',
        'Lead Magnet' => 'cta',
        'Before/After' => 'gallery',
    ],

    /**
     * Style variant → BEM-style modifier class on .medca-block root.
     */
    'variant_classes' => [
        'style_1' => 'medca-block--style-1',
        'style_2' => 'medca-block--style-2',
        'style_3' => 'medca-block--style-3',
        'style_4' => 'medca-block--style-4',
        'style_5' => 'medca-block--style-5',
    ],

    'carousel_families' => [
        'services', 'testimonials', 'team', 'blog', 'gallery', 'reviews',
    ],

    'carousel_variant_classes' => [
        'style_1' => 'medca-carousel--style-1',
        'style_2' => 'medca-carousel--style-2',
        'style_3' => 'medca-carousel--style-3',
        'style_4' => 'medca-carousel--style-4',
        'style_5' => 'medca-carousel--style-5',
    ],

    /**
     * Default block settings schema keys (stored in blocks.settings_json / page overrides).
     */
    'settings_schema' => [
        'style_variant' => 'string',
        'media' => 'array',
        'section' => 'array',
    ],

    'media_slots' => [
        'hero' => ['desktop_image', 'mobile_image', 'video', 'fallback_image'],
        'services' => ['image', 'icon', 'badge'],
        'testimonials' => ['photo', 'company_logo'],
        'gallery' => ['desktop_gallery', 'mobile_gallery'],
    ],

    'section_controls' => [
        'background_color',
        'background_image',
        'gradient',
        'overlay',
        'pattern',
        'shape_divider',
        'spacing',
        'padding',
        'border_radius',
        'shadow',
        'animation',
        'visibility_desktop',
        'visibility_tablet',
        'visibility_mobile',
    ],

];
