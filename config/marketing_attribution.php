<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Marketing attribution session layer (Phase 1+)
    |--------------------------------------------------------------------------
    */
    'enabled' => env('MARKETING_ATTRIBUTION_SESSIONS_ENABLED', true),

    'session_id_key' => 'marketing.attribution_session_id',

    /*
    | Look back window for stitching phone clicks to leads (minutes).
    */
    'click_stitch_window_minutes' => (int) env('MARKETING_CLICK_STITCH_WINDOW', 120),

    /*
    | Intent types considered phone-call attribution for click stitching.
    */
    'phone_click_event_types' => [
        'phone_click',
        'whatsapp_click',
    ],

];
