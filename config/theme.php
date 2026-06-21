<?php

/**
 * Theme Foundation — dual-shell token registry (Phase 4).
 *
 * CSS source of truth:
 * - Admin (MarkOnMinds): resources/css/markonminds.css `:root` — `--mom-*` namespace
 * - Public (MarkOnMinds marketing): resources/css/public/tokens.css — `--medca-*` on `.medca-public-surface`
 *
 * Tailwind mappings: tailwind.config.js (`mom-gold`, `medca-primary`, etc.)
 *
 * Rules:
 * - Never redefine admin `:root` names on the public body (no `--accent-gold` collision).
 * - Admin hex values in markonminds.css are UI-locked; change only with product owner approval.
 */
return [

    'admin' => [
        'namespace' => 'mom',
        'css_file' => 'resources/css/markonminds.css',
        'scope' => ':root / body.mom-body',
        'tokens' => [
            'gold' => '--mom-gold',
            'gold_soft' => '--mom-gold-soft',
            'gold_border' => '--mom-gold-border',
            'surface' => '--mom-surface',
            'border' => '--mom-border',
            'border_soft' => '--mom-border-soft',
            'text_primary' => '--text-primary',
            'text_secondary' => '--text-secondary',
            'bg_app' => '--bg-app',
            'radius_card' => '--radius-card',
            'radius_chrome' => '--radius-chrome',
            'shadow_surface' => '--shadow-surface',
        ],
        'legacy_aliases' => [
            '--accent-gold' => '--mom-gold',
            '--accent-gold-soft' => '--mom-gold-soft',
            '--accent-gold-border' => '--mom-gold-border',
        ],
    ],

    'public' => [
        'namespace' => 'medca',
        'css_file' => 'resources/css/public/tokens.css',
        'scope' => 'body.medca-public-surface',
        'tokens' => [
            'primary' => '--medca-primary',
            'primary_soft' => '--medca-primary-soft',
            'primary_border' => '--medca-primary-border',
            'primary_hover' => '--medca-primary-hover',
            'navy' => '--medca-navy',
            'navy_mid' => '--medca-navy-mid',
            'navy_border' => '--medca-navy-border',
            'text_primary' => '--medca-text-primary',
            'text_secondary' => '--medca-text-secondary',
            'text_muted' => '--medca-text-muted',
            'surface' => '--medca-surface',
            'surface_muted' => '--medca-surface-muted',
            'surface_elevated' => '--medca-surface-elevated',
            'border' => '--medca-border',
            'border_soft' => '--medca-border-soft',
            'radius_sm' => '--medca-radius-sm',
            'radius_md' => '--medca-radius-md',
            'radius_lg' => '--medca-radius-lg',
            'shadow_surface' => '--medca-shadow-surface',
            'shadow_hover' => '--medca-shadow-hover',
        ],
    ],

];
