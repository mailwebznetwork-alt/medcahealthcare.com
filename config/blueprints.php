<?php

/**
 * Deployment blueprints — page structure + block order + default style/media scaffolding.
 * Generator writes normal Page records with standard {{block:slug}} tokens.
 *
 * Industry packs: config/blueprint_packs.php (healthcare career consultancy, care home, real estate, cosmetics).
 */
return array_merge(

    [

        'construction' => [
            'label' => 'Construction',
            'industry' => 'construction',
            'description' => 'Industrial services grid with wide layout and corporate header.',
            'default_style_pack' => 'construction_industrial',
            'default_theme_preset' => 'forest_green',
            'pages' => [
                'home' => [
                    'title' => 'Construction',
                    'slug' => 'home',
                    'layout_mode' => 'canvas',
                    'blocks' => [
                        ['slug' => 'hero-home', 'style_variant' => 'style_4'],
                        ['slug' => 'services-block-grid', 'style_variant' => 'style_4'],
                        ['slug' => 'cta-home', 'style_variant' => 'style_1'],
                    ],
                ],
                'services' => [
                    'title' => 'Services',
                    'slug' => 'services',
                    'layout_mode' => 'contained',
                    'blocks' => [
                        ['slug' => 'hero-services', 'style_variant' => 'style_4'],
                        ['slug' => 'services-block-carousel', 'style_variant' => 'style_2'],
                        ['slug' => 'cta-services', 'style_variant' => 'style_1'],
                    ],
                ],
            ],
            'landing_pages' => [],
        ],

        'painting' => [
            'label' => 'Painting',
            'industry' => 'painting',
            'description' => 'Portfolio-led services with gallery-forward blocks.',
            'default_style_pack' => 'minimal_white',
            'default_theme_preset' => 'clinical_blue',
            'pages' => [
                'home' => [
                    'title' => 'Painting Services',
                    'slug' => 'home',
                    'layout_mode' => 'canvas',
                    'blocks' => [
                        ['slug' => 'hero-home', 'style_variant' => 'style_3'],
                        ['slug' => 'services-overview-home', 'style_variant' => 'style_3'],
                        ['slug' => 'cta-home', 'style_variant' => 'style_3'],
                    ],
                ],
            ],
            'landing_pages' => [],
        ],

        'consultancy' => [
            'label' => 'Consultancy',
            'industry' => 'consultancy',
            'description' => 'Corporate trust layout with contained width.',
            'default_style_pack' => 'consultancy_corporate',
            'default_theme_preset' => 'clinical_blue',
            'pages' => [
                'home' => [
                    'title' => 'Consultancy',
                    'slug' => 'home',
                    'layout_mode' => 'contained',
                    'blocks' => [
                        ['slug' => 'hero-home', 'style_variant' => 'style_4'],
                        ['slug' => 'services-overview-home', 'style_variant' => 'style_3'],
                        ['slug' => 'cta-home', 'style_variant' => 'style_4'],
                    ],
                ],
            ],
            'landing_pages' => [],
        ],

        'education' => [
            'label' => 'Education',
            'industry' => 'education',
            'description' => 'Clean academic layout with minimal header.',
            'default_style_pack' => 'education_clean',
            'default_theme_preset' => 'clinical_blue',
            'pages' => [
                'home' => [
                    'title' => 'Education',
                    'slug' => 'home',
                    'layout_mode' => 'contained',
                    'blocks' => [
                        ['slug' => 'hero-home', 'style_variant' => 'style_3'],
                        ['slug' => 'services-overview-home', 'style_variant' => 'style_1'],
                        ['slug' => 'cta-home', 'style_variant' => 'style_3'],
                    ],
                ],
            ],
            'landing_pages' => [],
        ],

    ],

    require __DIR__.'/blueprint_packs.php',
);
