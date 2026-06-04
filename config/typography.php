<?php

/**
 * Medca — Appearance & Typography System (authoritative spec).
 *
 * Fonts: Settings → Appearance → Typography (heading_font / body_font).
 * Sizes: per element × breakpoint; scaled by font scale (compact | default | large).
 */
return [

    'defaults' => [
        'heading_font' => 'Plus Jakarta Sans',
        'body_font' => 'Noto Sans',
    ],

    'breakpoints' => [
        'tablet_max' => '1023px',
        'mobile_max' => '767px',
    ],

    'scale_multipliers' => [
        'compact' => 0.9375,
        'default' => 1,
        'large' => 1.125,
    ],

    'body_base_px' => [
        'compact' => 15,
        'default' => 16,
        'large' => 18,
    ],

    /**
     * Minimum font size on the public marketing site (matches hero-home subheadline: text-base / md:text-lg).
     */
    /**
     * Public body readability floors (hero subheadline: text-base mobile, md:text-lg desktop).
     */
    'public_minimum' => [
        'mobile' => 1,
        'desktop' => 1.125,
    ],

    /**
     * @var array<string, array{family: string, desktop: array{size: float, weight: int, line_height: float}, tablet: array{size: float, weight: int, line_height: float}, mobile: array{size: float, weight: int, line_height: float}}>
     */
    'elements' => [
        'h1' => [
            'family' => 'heading',
            'desktop' => ['size' => 2.75, 'weight' => 700, 'line_height' => 1.12],
            'tablet' => ['size' => 2.5, 'weight' => 700, 'line_height' => 1.14],
            'mobile' => ['size' => 2.25, 'weight' => 700, 'line_height' => 1.16],
        ],
        'h2' => [
            'family' => 'heading',
            'desktop' => ['size' => 2.125, 'weight' => 600, 'line_height' => 1.18],
            'tablet' => ['size' => 1.9375, 'weight' => 600, 'line_height' => 1.2],
            'mobile' => ['size' => 1.75, 'weight' => 600, 'line_height' => 1.22],
        ],
        'h3' => [
            'family' => 'heading',
            'desktop' => ['size' => 1.75, 'weight' => 600, 'line_height' => 1.22],
            'tablet' => ['size' => 1.625, 'weight' => 600, 'line_height' => 1.24],
            'mobile' => ['size' => 1.5, 'weight' => 600, 'line_height' => 1.26],
        ],
        'h4' => [
            'family' => 'heading',
            'desktop' => ['size' => 1.375, 'weight' => 600, 'line_height' => 1.28],
            'tablet' => ['size' => 1.25, 'weight' => 600, 'line_height' => 1.3],
            'mobile' => ['size' => 1.125, 'weight' => 600, 'line_height' => 1.32],
        ],
        'h5' => [
            'family' => 'heading',
            'desktop' => ['size' => 1.125, 'weight' => 600, 'line_height' => 1.32],
            'tablet' => ['size' => 1.0625, 'weight' => 600, 'line_height' => 1.34],
            'mobile' => ['size' => 1, 'weight' => 600, 'line_height' => 1.35],
        ],
        'h6' => [
            'family' => 'heading',
            'desktop' => ['size' => 1, 'weight' => 600, 'line_height' => 1.35],
            'tablet' => ['size' => 0.9375, 'weight' => 600, 'line_height' => 1.36],
            'mobile' => ['size' => 0.875, 'weight' => 600, 'line_height' => 1.38],
        ],
        'body_large' => [
            'family' => 'body',
            'desktop' => ['size' => 1.125, 'weight' => 400, 'line_height' => 1.6],
            'tablet' => ['size' => 1.0625, 'weight' => 400, 'line_height' => 1.6],
            'mobile' => ['size' => 1, 'weight' => 400, 'line_height' => 1.55],
        ],
        'body_regular' => [
            'family' => 'body',
            'desktop' => ['size' => 1.125, 'weight' => 400, 'line_height' => 1.5],
            'tablet' => ['size' => 1.0625, 'weight' => 400, 'line_height' => 1.5],
            'mobile' => ['size' => 1, 'weight' => 400, 'line_height' => 1.5],
        ],
        'small' => [
            'family' => 'body',
            'desktop' => ['size' => 1.125, 'weight' => 400, 'line_height' => 1.45],
            'tablet' => ['size' => 1.0625, 'weight' => 400, 'line_height' => 1.45],
            'mobile' => ['size' => 1, 'weight' => 400, 'line_height' => 1.45],
        ],
        'button' => [
            'family' => 'body',
            'desktop' => ['size' => 0.9375, 'weight' => 600, 'line_height' => 1.2],
            'tablet' => ['size' => 0.9375, 'weight' => 600, 'line_height' => 1.2],
            'mobile' => ['size' => 1, 'weight' => 600, 'line_height' => 1.2],
        ],
    ],

];
