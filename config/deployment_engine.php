<?php

/**
 * MarkOnMinds Deployment Engine (Phase 8.5).
 *
 * Extends Theme Management — does not replace Pages, Blocks, or ContentParser.
 */
return [

    'enabled' => env('DEPLOYMENT_ENGINE_ENABLED', true),

    /** Session key for previewing style pack / blueprint before publish */
    'preview_session_keys' => [
        'style_pack' => 'deployment_preview_style_pack',
        'blueprint' => 'deployment_preview_blueprint',
    ],

    /**
     * Default style pack slug when none is set on the page or site configuration.
     */
    'default_style_pack' => 'healthcare career consultancy_professional',

    /**
     * Industries shown in Blueprint Builder (maps to blueprint groups).
     */
    'industries' => [
        'healthcare career consultancy' => 'Healthcare Career Consultancy',
        'care_home' => 'Care Home',
        'construction' => 'Construction',
        'painting' => 'Painting',
        'consultancy' => 'Consultancy',
        'education' => 'Education',
    ],

    /**
     * Roles allowed to generate pages from blueprints.
     */
    'generator_roles' => ['manager', 'admin', 'super_admin'],

    /**
     * Roles allowed to manage block presets (save/import/export).
     */
    'block_preset_roles' => ['editor', 'manager', 'admin', 'super_admin'],

    'storage' => [
        'block_preset_exports' => 'deployment/block-presets',
        'package_exports' => 'deployment/packages',
        'generation_logs' => true,
    ],

    'package_roles' => ['admin', 'super_admin'],

];
