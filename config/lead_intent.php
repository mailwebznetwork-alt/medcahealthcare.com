<?php

return [
    'enabled' => env('LEAD_INTENT_TRACKING_ENABLED', true),

    /** Marketing click event_type => lead intent_type */
    'marketing_event_map' => [
        'form_submit' => 'form_submit',
        'whatsapp_click' => 'whatsapp_click',
        'phone_click' => 'phone_click',
        'gbp_call_click' => 'gbp_call',
        'gbp_website_visit' => 'gbp_website_click',
        'gbp_whatsapp_click' => 'gbp_whatsapp_click',
    ],

    /** Lead source (Lead model) => intent_type for form channel */
    'lead_source_intent_map' => [
        'google_ads' => 'google_ads_form',
        'meta_ads' => 'meta_form',
        'organic' => 'form_submit',
        'whatsapp' => 'form_submit',
        'call' => 'form_submit',
        'gmb' => 'form_submit',
        'direct' => 'form_submit',
        'referral' => 'form_submit',
        'email' => 'form_submit',
        'linkedin' => 'form_submit',
    ],
];
