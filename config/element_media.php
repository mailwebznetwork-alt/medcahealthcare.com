<?php

/**
 * Element-level image controls for blocks (stored in settings_json.section.image_*).
 */
return [

    'positions' => ['left', 'right', 'top', 'bottom', 'center', 'background'],

    'alignments' => ['left', 'center', 'right'],

    'size_modes' => ['auto', 'cover', 'contain', 'custom'],

    'styles' => ['default', 'rounded', 'circle', 'card', 'shadow', 'floating', 'glass', 'hero'],

    'defaults' => [
        'position' => 'center',
        'alignment_desktop' => 'center',
        'alignment_tablet' => 'center',
        'alignment_mobile' => 'center',
        'size_mode' => 'auto',
        'style' => 'default',
        'border_radius' => null,
        'opacity' => 100,
        'overlay_opacity' => 40,
        'parallax' => false,
        'fixed_background' => false,
    ],

    /**
     * Healthcare layout presets — merge into block section settings via Block Presets UI.
     */
    'layout_presets' => [
        'hero-banner' => [
            'label' => 'Hero Banner',
            'position' => 'background',
            'style' => 'hero',
            'size_mode' => 'cover',
            'overlay_opacity' => 55,
        ],
        'left-image-right-content' => [
            'label' => 'Left Image / Right Content',
            'position' => 'left',
            'style' => 'card',
            'size_mode' => 'contain',
            'alignment_desktop' => 'left',
        ],
        'right-image-left-content' => [
            'label' => 'Right Image / Left Content',
            'position' => 'right',
            'style' => 'card',
            'size_mode' => 'contain',
            'alignment_desktop' => 'right',
        ],
        'doctor-profile' => [
            'label' => 'Doctor Profile',
            'position' => 'left',
            'style' => 'circle',
            'size_mode' => 'auto',
        ],
        'service-feature' => [
            'label' => 'Service Feature',
            'position' => 'top',
            'style' => 'card',
            'size_mode' => 'cover',
        ],
        'gallery-grid' => [
            'label' => 'Gallery Grid',
            'position' => 'center',
            'style' => 'rounded',
            'size_mode' => 'cover',
        ],
        'full-width-healthcare-banner' => [
            'label' => 'Full Width Healthcare Banner',
            'position' => 'background',
            'style' => 'glass',
            'size_mode' => 'cover',
            'overlay_opacity' => 60,
            'fixed_background' => false,
        ],
    ],

    'healthcare_presets' => [
        'hero-nurse' => [
            'label' => 'Hero with nurse image',
            'position' => 'right',
            'style' => 'hero',
            'size_mode' => 'cover',
        ],
        'split-content-image' => [
            'label' => 'Split content + image',
            'position' => 'left',
            'style' => 'card',
            'size_mode' => 'contain',
        ],
        'doctor-profile' => [
            'label' => 'Doctor profile',
            'position' => 'left',
            'style' => 'circle',
            'size_mode' => 'auto',
        ],
        'caregiver-profile' => [
            'label' => 'Caregiver profile',
            'position' => 'left',
            'style' => 'rounded',
            'size_mode' => 'auto',
        ],
        'service-card-image' => [
            'label' => 'Service card with image',
            'position' => 'top',
            'style' => 'card',
            'size_mode' => 'cover',
        ],
        'benefits-grid' => [
            'label' => 'Benefits grid',
            'position' => 'top',
            'style' => 'default',
        ],
        'trust-section' => [
            'label' => 'Trust section',
            'position' => 'background',
            'style' => 'glass',
            'overlay_opacity' => 55,
        ],
        'process-section' => [
            'label' => 'Process section',
            'position' => 'top',
            'style' => 'default',
        ],
        'testimonials-photos' => [
            'label' => 'Testimonials with photos',
            'position' => 'left',
            'style' => 'circle',
        ],
        'faq-section' => [
            'label' => 'FAQ section',
            'position' => 'center',
            'style' => 'default',
        ],
        'gallery-section' => [
            'label' => 'Gallery section',
            'position' => 'center',
            'style' => 'rounded',
            'size_mode' => 'cover',
        ],
    ],

];
