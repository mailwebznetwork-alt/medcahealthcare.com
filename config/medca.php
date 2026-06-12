<?php

/**
 * Medca branding and public marketing shell (guest-facing routes).
 *
 * Sticky header height is not set as a single min-height in CSS. It stacks the navy top strip and
 * the white brand/nav row (see resources/views/global/header.blade.php). Total height follows
 * padding (strip `py-2`, row `py-[15px]` / `md:py-[18px]`) and logo (`h-7` / `md:h-8`). Rough range
 * ~118–132px depending on breakpoint and whether the location link wraps. Use
 * `marketing_sticky_header_approx_px` as the documentation default for anchor scroll-margin math.
 */
return [

    'top_bar_claim' => '#1 Home Healthcare in Bengaluru',

    /** PWA / mobile browser chrome — matches --medca-navy in public/tokens.css */
    'theme_color' => env('MEDCA_THEME_COLOR', '#001f5c'),

    /**
     * Approximate total height (px) of the sticky public marketing header (strip + brand row).
     * Documentation only unless you wire it (e.g. inline scroll-margin); CSS height remains content-driven.
     */
    'marketing_sticky_header_approx_px' => (int) env('MEDCA_MARKETING_HEADER_APPROX_PX', 124),

    'location_display' => env('MEDCA_LOCATION', 'Arekere Gate, Bengaluru'),

    /** Public site & company display name (never MarkOnMinds on the frontend). */
    'brand_name' => env('MEDCA_BRAND_NAME', 'Medca Health Care'),

    /** Legal entity line for public footer / compliance copy. */
    'company_legal_name' => env('MEDCA_COMPANY_LEGAL_NAME', 'Medca Healthcare Pvt Ltd.'),

    'tagline' => 'Care You Can Trust',

    'whatsapp_url' => env('MEDCA_WHATSAPP_URL', 'https://wa.me/918000000000'),

    'phone_display' => env('MEDCA_PHONE_DISPLAY', '+91 80 0000 0000'),

    'phone_tel' => env('MEDCA_PHONE_TEL', '+918000000000'),

    /** Optional Google Business Profile URL for the header location pill (Medca marketing shell). */
    'public_profile_url' => env('MEDCA_PUBLIC_PROFILE_URL', ''),

    /**
     * Hide visible breadcrumb nav on public pages while keeping JSON-LD / schema hierarchy.
     * Set MEDCA_HIDE_VISUAL_BREADCRUMBS=false to restore the UI trail.
     */
    'hide_visual_breadcrumbs' => (bool) env('MEDCA_HIDE_VISUAL_BREADCRUMBS', true),

    /**
     * Hide “About {area} healthcare coverage” (nearby areas, hospitals, landmarks, emergency).
     */
    'hide_location_coverage_panel' => (bool) env('MEDCA_HIDE_LOCATION_COVERAGE_PANEL', true),

    /**
     * Hide “Book care in your neighbourhood” lead CTA block on location pages.
     */
    'hide_location_neighbourhood_cta' => (bool) env('MEDCA_HIDE_LOCATION_NEIGHBOURHOOD_CTA', true),

    /**
     * Dev/staging hosts rewritten by `medca:normalize-site-urls` toward APP_URL (or --to=).
     *
     * @var list<string>
     */
    'legacy_url_hosts' => [
        'markonmindsplus.test',
        'markonminds.test',
        'medcahealthcare.test',
        'medcaeducation.in',
        'www.medcaeducation.in',
        'localhost',
        '127.0.0.1',
    ],

];
