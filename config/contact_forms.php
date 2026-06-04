<?php

/**
 * B3 — Contact / lead form ownership (no new tables).
 *
 * - Submission: POST /api/leads (LeadController) + optional module tokens on pages
 * - Placement: pages.content ({{block:…}} / {{module:…}})
 * - Presentation: block Blade views (form-callback, contact-info, hero-contact)
 * - Global Content: business constants only (phone, WhatsApp, address) — not form fields
 */
return [

    'ownership' => [
        'submission' => 'public.leads.store',
        'submission_api' => 'POST /api/leads',
        'placement' => 'pages.content',
        'presentation' => 'blocks',
        'global_content' => 'constants_only',
    ],

    'presentation_blocks' => [
        'form-callback',
        'contact-info',
        'hero-contact',
        'contact-split',
    ],

    'web_route' => '/leads',
    'web_route_name' => 'public.leads.store',
    'api_route' => '/api/leads',
    'api_header' => 'X-API-KEY',
    'component' => 'public.lead-capture-form',

];
