<?php

/**
 * Medca branding and public marketing shell (guest-facing routes).
 *
 * Sticky header height is not set as a single min-height in CSS. It stacks the navy top strip and
 * the white brand/nav row (see resources/views/global/header.blade.php). Total height follows
 * padding (strip `py-2`, row `py-[15px]` / `md:py-[18px]`) and logo (`h-7` / `md:h-8`). Rough range
 * ~96–128px depending on breakpoint and whether the location link wraps. Use
 * `marketing_sticky_header_approx_px` as the documentation default for anchor scroll-margin math.
 */
return [

    'top_bar_claim' => '#1 Home Healthcare in Bengaluru',

    /**
     * Approximate total height (px) of the sticky public marketing header (strip + brand row).
     * Documentation only unless you wire it (e.g. inline scroll-margin); CSS height remains content-driven.
     */
    'marketing_sticky_header_approx_px' => (int) env('MEDCA_MARKETING_HEADER_APPROX_PX', 112),

    'location_display' => env('MEDCA_LOCATION', 'Arekere Gate, Bengaluru'),

    'brand_name' => 'Medca Health Care',

    'tagline' => 'Care You Can Trust',

    'whatsapp_url' => env('MEDCA_WHATSAPP_URL', 'https://wa.me/918000000000'),

    'phone_display' => env('MEDCA_PHONE_DISPLAY', '+91 80 0000 0000'),

    'phone_tel' => env('MEDCA_PHONE_TEL', '+918000000000'),

    /** Optional Google Business Profile URL for the header location pill (Medca marketing shell). */
    'public_profile_url' => env('MEDCA_PUBLIC_PROFILE_URL', ''),

];
